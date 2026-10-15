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

    form.addEventListener("submit", async function (event) {
      event.preventDefault();
      if (!form.checkValidity()) {
        form.classList.add("was-validated");
        setStatus("Please accept the payment authorization before continuing.", "error");
        return;
      }

      setLoading(true);
      setStatus("Securely verifying your card with Square…", "");
      try {
        var verificationDetails = {
          amount: config.amount,
          currencyCode: config.currency,
          intent: "CHARGE",
          customerInitiated: true,
          sellerKeyedIn: false,
          billingContact: config.billingContact
        };
        var tokenResult = await card.tokenize(verificationDetails);
        if (tokenResult.status !== "OK" || !tokenResult.token) {
          var tokenMessage = tokenResult.errors && tokenResult.errors.length
            ? tokenResult.errors.map(function (item) { return item.message || item.detail || "Card verification failed."; }).join(" ")
            : "Square could not verify the card details.";
          throw new Error(tokenMessage);
        }

        var response = await fetch(config.endpoint, {
          method: "POST",
          credentials: "same-origin",
          headers: {
            "Content-Type": "application/json",
            "Accept": "application/json",
            [config.csrfHeader]: config.csrfToken
          },
          body: JSON.stringify({ source_id: tokenResult.token, payment_key: config.paymentKey })
        });
        var payload;
        try {
          payload = await response.json();
        } catch (error) {
          throw new Error("The payment server returned an unexpected response. Check your booking before retrying.");
        }
        if (!response.ok || payload.status !== "success") {
          if (payload.reload) {
            setStatus((payload.message || "Payment was not completed.") + " Refresh this page before trying another card.", "error");
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
      }
    });
  }

  initialize().catch(function (error) {
    setStatus(error instanceof Error ? error.message : "Square's payment form could not be initialized.", "error");
    submitButton.disabled = true;
  });
})();
