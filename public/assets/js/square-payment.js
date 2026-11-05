(function () {
  "use strict";

  var configNode = document.getElementById("squarePaymentConfig");
  var form = document.querySelector("[data-square-payment-form]");
  var submitButton = document.querySelector("[data-payment-submit]");
  var statusNode = document.querySelector("[data-payment-status]");
  if (!configNode || !form || !submitButton || !statusNode) return;

  var config;
  try {
    config = JSON.parse(configNode.textContent || "{}");
  } catch (error) {
    statusNode.textContent = "The payment page could not be initialized. Please refresh and try again.";
    statusNode.classList.add("is-error");
    return;
  }

  function setStatus(message, type) {
    statusNode.textContent = message || "";
    statusNode.classList.toggle("is-error", type === "error");
    statusNode.classList.toggle("is-success", type === "success");
  }

  function setLoading(loading) {
    submitButton.disabled = loading;
    submitButton.textContent = loading ? "Processing securely…" : "Pay " + config.currency + " $" + config.amount;
    form.setAttribute("aria-busy", loading ? "true" : "false");
  }

  async function initialize() {
    if (!window.Square || typeof window.Square.payments !== "function") {
      throw new Error("Square's secure payment form did not load. Check your connection and refresh the page.");
    }

    var payments = window.Square.payments(config.applicationId, config.locationId);
    var card = await payments.card();
    await card.attach("#square-card-container");
    submitButton.disabled = false;

    var busy = false;

    function authorizationAccepted() {
      if (form.checkValidity()) return true;
      form.classList.add("was-validated");
      setStatus("Please accept the payment authorization before continuing.", "error");
      return false;
    }

    function tokenError(tokenResult, fallback) {
      return tokenResult.errors && tokenResult.errors.length
        ? tokenResult.errors.map(function (item) { return item.message || item.detail || fallback; }).join(" ")
        : fallback;
    }

    // One submit routine for card, Apple Pay and Google Pay: the server charges whichever token it gets.
    async function payWith(getToken, verifyingMessage) {
      if (busy || !authorizationAccepted()) return;
      busy = true;
      setLoading(true);
      setStatus(verifyingMessage, "");
      try {
        var token = await getToken();
        var response = await fetch(config.endpoint, {
          method: "POST",
          credentials: "same-origin",
          headers: {
            "Content-Type": "application/json",
            "Accept": "application/json",
            [config.csrfHeader]: config.csrfToken
          },
          body: JSON.stringify({ source_id: token, payment_key: config.paymentKey })
        });
        var payload;
        try {
          payload = await response.json();
        } catch (error) {
          throw new Error("The payment server returned an unexpected response. Check your booking before retrying.");
        }
        if (!response.ok || payload.status !== "success") {
          if (payload.reload) {
            setStatus((payload.message || "Payment was not completed.") + " Refresh this page before trying again.", "error");
            submitButton.textContent = "Refresh to try again";
            submitButton.disabled = true;
            return;
          }
          throw new Error(payload.message || "Payment was not completed.");
        }

        setStatus(payload.message || "Payment completed successfully.", "success");
        await window.epicNotify("success", payload.message || "Payment completed successfully.", { modal: true, timer: 1800 });
        window.location.assign(payload.redirect || config.successUrl);
      } catch (error) {
        setStatus(error instanceof Error ? error.message : "Payment was not completed.", "error");
        setLoading(false);
        busy = false;
      }
    }

    form.addEventListener("submit", function (event) {
      event.preventDefault();
      payWith(async function () {
        var tokenResult = await card.tokenize({
          amount: config.amount,
          currencyCode: config.currency,
          intent: "CHARGE",
          customerInitiated: true,
          sellerKeyedIn: false,
          billingContact: config.billingContact
        });
        if (tokenResult.status !== "OK" || !tokenResult.token) {
          throw new Error(tokenError(tokenResult, "Square could not verify the card details."));
        }
        return tokenResult.token;
      }, "Securely verifying your card with Square…");
    });

    setUpWallets(payments, payWith, tokenError);
  }

  // Apple Pay and Google Pay appear only when Square says this device and account support them.
  // Apple Pay also needs the site's domain registered in the Square Developer Dashboard.
  async function setUpWallets(payments, payWith, tokenError) {
    var container = document.querySelector("[data-wallet-buttons]");
    if (!container || typeof payments.paymentRequest !== "function") return;

    var paymentRequest;
    try {
      paymentRequest = payments.paymentRequest({
        countryCode: "US",
        currencyCode: config.currency,
        total: { amount: config.amount, label: "EPIC Storage booking" }
      });
    } catch (error) {
      return;
    }

    function walletToken(wallet, name) {
      return async function () {
        var tokenResult = await wallet.tokenize();
        if (tokenResult.status !== "OK" || !tokenResult.token) {
          throw new Error(tokenError(tokenResult, name + " did not complete. Try again or pay by card."));
        }
        return tokenResult.token;
      };
    }

    var shown = false;

    var appleButton = document.getElementById("apple-pay-button");
    if (appleButton && typeof payments.applePay === "function") {
      try {
        var applePay = await payments.applePay(paymentRequest);
        var payWithApple = function () { payWith(walletToken(applePay, "Apple Pay"), "Waiting for Apple Pay…"); };
        appleButton.addEventListener("click", payWithApple);
        appleButton.addEventListener("keydown", function (event) {
          if (event.key === "Enter" || event.key === " ") {
            event.preventDefault();
            payWithApple();
          }
        });
        appleButton.hidden = false;
        shown = true;
      } catch (error) {
        // Not supported on this device, or the domain is not registered with Square.
      }
    }

    var googleButton = document.getElementById("google-pay-button");
    if (googleButton && typeof payments.googlePay === "function") {
      try {
        var googlePay = await payments.googlePay(paymentRequest);
        googleButton.hidden = false;
        await googlePay.attach("#google-pay-button", { buttonColor: "black", buttonSizeMode: "fill", buttonType: "pay" });
        googleButton.addEventListener("click", function () {
          payWith(walletToken(googlePay, "Google Pay"), "Waiting for Google Pay…");
        });
        shown = true;
      } catch (error) {
        googleButton.hidden = true;
      }
    }

    container.hidden = !shown;
  }

  initialize().catch(function (error) {
    setStatus(error instanceof Error ? error.message : "Square's payment form could not be initialized.", "error");
    submitButton.disabled = true;
  });
})();
