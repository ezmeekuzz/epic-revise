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
  }

  initialize().catch(function (error) {
    // Card form unavailable: fall back to saving the order and paying on the next page.
    var later = wrap.querySelector('input[name="payment_option"][value="later"]');
    if (later) { later.checked = true; syncOption(); }
    setStatus(error instanceof Error ? error.message : "The card form could not be loaded.", true);
  });

  // Capture on document so we run before the booking form's own submit handlers.
  document.addEventListener("submit", async function (event) {
    if (event.target !== form || !payNow() || tokenInput.value || !card) return;

    event.preventDefault();
    event.stopImmediatePropagation();
    if (busy) return;
    if (!form.checkValidity()) {
      form.classList.add("was-validated");
      form.reportValidity();
      return;
    }

    busy = true;
    setStatus("Securely verifying your card…", false);
    try {
      var details = { intent: "CHARGE", customerInitiated: true, sellerKeyedIn: false };
      var amount = estimatedTotal();
      if (amount) { details.amount = amount; details.currencyCode = config.currency; }
      var name = form.querySelector('[name="customer_name"]');
      var email = form.querySelector('[name="customer_email"]');
      details.billingContact = { givenName: name ? name.value : "", email: email ? email.value : "" };
      var result = await card.tokenize(details);
      if (result.status !== "OK" || !result.token) {
        var message = result.errors && result.errors.length
          ? result.errors.map(function (item) { return item.message || item.detail; }).join(" ")
          : "Square could not verify the card details.";
        throw new Error(message);
      }
      tokenInput.value = result.token;
      setStatus("Placing your order…", false);
      form.requestSubmit(form.querySelector("[data-checkout-submit]"));
    } catch (error) {
      setStatus(error instanceof Error ? error.message : "Card could not be verified.", true);
      busy = false;
    }
  }, true);
})();
