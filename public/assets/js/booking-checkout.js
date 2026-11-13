(function () {
  "use strict";

  // Single-page checkout: tokenizes the card on the booking page, then submits the booking form
  // with the token. The server saves the order first and charges second, so a declined card
  // never loses the order.
  var configNode = document.getElementById("checkoutSquareConfig");
  var wrap = document.querySelector("[data-checkout-payment]");
  var form = document.querySelector("[data-booking-processing-form]");
  if (!configNode || !wrap || !form) return;

  var config = JSON.parse(configNode.textContent || "{}");
  var tokenInput = wrap.querySelector("[data-checkout-token]");
  var cardWrap = wrap.querySelector("[data-checkout-card-wrap]");
  var statusNode = wrap.querySelector("[data-checkout-status]");
  var options = wrap.querySelectorAll('input[name="payment_option"]');
  var card = null;
  var busy = false;

  function setStatus(message, isError) {
    statusNode.textContent = message || "";
    statusNode.classList.toggle("is-error", !!isError);
  }

  function payNow() {
    var checked = wrap.querySelector('input[name="payment_option"]:checked');
    return !checked || checked.value === "now";
  }

  function estimatedTotal() {
    var node = document.querySelector("[data-estimated-total]");
    var value = node ? parseFloat(node.textContent.replace(/[^0-9.]/g, "")) : NaN;
    return isFinite(value) && value > 0 ? value.toFixed(2) : null;
  }

  function syncOption() {
    cardWrap.hidden = !payNow();
    options.forEach(function (input) {
      var label = input.closest(".service-choice");
      if (label) label.classList.toggle("is-selected", input.checked);
    });
    var submit = form.querySelector("[data-checkout-submit]");
    if (submit) submit.textContent = payNow() ? "Place order and pay" : "Place order";
  }
  options.forEach(function (input) { input.addEventListener("change", syncOption); });
  syncOption();

  async function initialize() {
    if (!window.Square || typeof window.Square.payments !== "function") {
      throw new Error("Secure card form did not load. You can choose Pay later and pay from the emailed link.");
    }
    var payments = window.Square.payments(config.applicationId, config.locationId);
    card = await payments.card();
    await card.attach("#checkout-card-container");
    // Wallets are optional: a failure here must not disable card payment.
    if (window.__checkoutSetUpWallets) window.__checkoutSetUpWallets(payments).catch(function () {});
  }

  initialize().catch(function (error) {
    // Card form unavailable: fall back to saving the order and paying on the next page.
    var later = wrap.querySelector('input[name="payment_option"][value="later"]');
    if (later) { later.checked = true; syncOption(); }
    setStatus(error instanceof Error ? error.message : "The card form could not be loaded.", true);
  });

  function tokenError(result, fallback) {
    return result.errors && result.errors.length
      ? result.errors.map(function (item) { return item.message || item.detail || fallback; }).join(" ")
      : fallback;
  }

  // One routine for card, Apple Pay and Google Pay: validate the form, get a token, submit the booking.
  async function submitWith(getToken, message) {
    if (busy) return;
    if (!form.checkValidity()) {
      form.classList.add("was-validated");
      form.reportValidity();
      return;
    }
    busy = true;
    setStatus(message, false);
    try {
      tokenInput.value = await getToken();
      setStatus("Placing your order…", false);
      form.requestSubmit(form.querySelector("[data-checkout-submit]"));
    } catch (error) {
      setStatus(error instanceof Error ? error.message : "Payment could not be verified.", true);
      busy = false;
    }
  }

  // Capture on document so we run before the booking form's own submit handlers.
  document.addEventListener("submit", function (event) {
    if (event.target !== form || !payNow() || tokenInput.value || !card) return;

    event.preventDefault();
    event.stopImmediatePropagation();
    submitWith(async function () {
      var details = { intent: "CHARGE", customerInitiated: true, sellerKeyedIn: false };
      var amount = estimatedTotal();
      if (amount) { details.amount = amount; details.currencyCode = config.currency; }
      var name = form.querySelector('[name="customer_name"]');
      var email = form.querySelector('[name="customer_email"]');
      details.billingContact = { givenName: name ? name.value : "", email: email ? email.value : "" };
      var result = await card.tokenize(details);
      if (result.status !== "OK" || !result.token) throw new Error(tokenError(result, "Square could not verify the card details."));
      return result.token;
    }, "Securely verifying your card…");
  }, true);

  // Apple Pay / Google Pay appear only when Square says this device and account support them.
  async function setUpWallets(payments) {
    var container = wrap.querySelector("[data-checkout-wallets]");
    if (!container || typeof payments.paymentRequest !== "function") return;

    var paymentRequest;
    try {
      paymentRequest = payments.paymentRequest({
        countryCode: "US",
        currencyCode: config.currency,
        total: { amount: estimatedTotal() || "1.00", label: "EPIC Storage booking" }
      });
    } catch (error) {
      return;
    }

    function walletToken(wallet, name) {
      return async function () {
        // The total changes as the customer edits the order, so refresh it at click time.
        try {
          var amount = estimatedTotal();
          if (amount && typeof paymentRequest.update === "function") {
            paymentRequest.update({ total: { amount: amount, label: "EPIC Storage booking" } });
          }
        } catch (error) { /* keep the amount we started with */ }
        var result = await wallet.tokenize();
        if (result.status !== "OK" || !result.token) throw new Error(tokenError(result, name + " did not complete. Try again or pay by card."));
        return result.token;
      };
    }

    var shown = false;

    var appleButton = document.getElementById("checkout-apple-pay-button");
    if (appleButton && typeof payments.applePay === "function") {
      try {
        var applePay = await payments.applePay(paymentRequest);
        var payWithApple = function () { submitWith(walletToken(applePay, "Apple Pay"), "Waiting for Apple Pay…"); };
        appleButton.addEventListener("click", payWithApple);
        appleButton.addEventListener("keydown", function (event) {
          if (event.key === "Enter" || event.key === " ") { event.preventDefault(); payWithApple(); }
        });
        appleButton.hidden = false;
        shown = true;
      } catch (error) { /* unsupported device or domain not registered with Square */ }
    }

    var googleButton = document.getElementById("checkout-google-pay-button");
    if (googleButton && typeof payments.googlePay === "function") {
      try {
        var googlePay = await payments.googlePay(paymentRequest);
        googleButton.hidden = false;
        await googlePay.attach("#checkout-google-pay-button", { buttonColor: "black", buttonSizeMode: "fill", buttonType: "pay" });
        googleButton.addEventListener("click", function () {
          submitWith(walletToken(googlePay, "Google Pay"), "Waiting for Google Pay…");
        });
        shown = true;
      } catch (error) { googleButton.hidden = true; }
    }

    container.hidden = !shown;
  }

  window.__checkoutSetUpWallets = setUpWallets;
})();
