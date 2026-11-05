/*
 * EPIC notifications.
 *
 * Toastr (epicToast)       - non-blocking feedback: saves, updates, sends, sign-in/out,
 *                            recoverable validation problems.
 * SweetAlert2 (epicAlert)  - blocking dialogs: things the user must read or decide on,
 *                            such as lockouts, failed payments/emails, milestones.
 * SweetAlert2 (epicConfirm) - confirmations before destructive or important actions.
 * SweetAlert2 loading       - shown while a POST form submits.
 *
 * epicNotify(type, message, options) is kept for existing callers: it uses a toast,
 * or a modal when options.modal is true.
 */
(function () {
  "use strict";

  var allowedTypes = ["success", "error", "warning", "info", "question"];
  var titles = {
    success: "Success",
    error: "Something went wrong",
    warning: "Please check",
    info: "Notice",
    question: "Please confirm"
  };
  var toastTimeouts = { success: 4000, info: 5000, warning: 6500, error: 8000 };

  function safeType(type) {
    return allowedTypes.indexOf(type) !== -1 ? type : "info";
  }

  function swalAvailable() {
    return window.Swal && typeof window.Swal.fire === "function";
  }

  function toastrAvailable() {
    return window.toastr && typeof window.toastr.success === "function";
  }

  function escapeHtml(value) {
    return String(value)
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&#39;");
  }

  // Builds safe HTML from plain text and an optional list of plain-text items.
  function buildBody(message, items) {
    var html = message ? "<p class=\"epic-notify-text\">" + escapeHtml(message) + "</p>" : "";
    if (items && items.length) {
      html += "<ul class=\"epic-notify-list\">" + items.map(function (item) {
        return "<li>" + escapeHtml(item) + "</li>";
      }).join("") + "</ul>";
    }
    return html;
  }

  function plainText(message, items) {
    return [message].concat(items || []).filter(Boolean).join("\n");
  }

  if (toastrAvailable()) {
    window.toastr.options = {
      closeButton: true,
      newestOnTop: true,
      progressBar: true,
      positionClass: "toast-top-right",
      preventDuplicates: true,
      showDuration: 200,
      hideDuration: 200,
      extendedTimeOut: 2000,
      escapeHtml: false, // bodies are escaped by buildBody()
      toastClass: "toast epic-toast"
    };
  }

  window.epicAlert = function (type, message, options) {
    var normalizedType = safeType(type);
    var settings = Object.assign({}, options || {});
    var items = settings.items;
    delete settings.items;

    if (!swalAvailable()) {
      window.alert(plainText(message, items));
      return Promise.resolve({ isConfirmed: true });
    }

    return window.Swal.fire(Object.assign({
      icon: normalizedType,
      title: settings.title || titles[normalizedType],
      html: buildBody(message, items),
      confirmButtonText: "OK",
      confirmButtonColor: "#005aab",
      cancelButtonColor: "#687583",
      heightAuto: false,
      reverseButtons: true,
      customClass: {
        popup: "epic-swal-popup",
        confirmButton: "epic-swal-confirm",
        cancelButton: "epic-swal-cancel"
      }
    }, settings, { title: settings.title || titles[normalizedType] }));
  };

  window.epicToast = function (type, message, options) {
    var normalizedType = safeType(type);
    if (normalizedType === "question") normalizedType = "info";
    var settings = options || {};
    var body = buildBody(message, settings.items);
    var title = settings.title ? escapeHtml(settings.title) : undefined;

    if (toastrAvailable()) {
      window.toastr[normalizedType](body, title, {
        timeOut: settings.timeout || toastTimeouts[normalizedType]
      });
      return;
    }

    // Fallback: SweetAlert2 toast, then native alert.
    if (swalAvailable()) {
      window.Swal.fire({
        toast: true,
        position: "top-end",
        icon: normalizedType,
        title: settings.title || undefined,
        html: body,
        showConfirmButton: false,
        timer: toastTimeouts[normalizedType],
        timerProgressBar: true
      });
      return;
    }

    window.alert(plainText(message, settings.items));
  };

  window.epicNotify = function (type, message, options) {
    var settings = Object.assign({}, options || {});
    var modal = settings.modal === true;
    delete settings.modal;

    if (modal) return window.epicAlert(type, message, settings);

    window.epicToast(type, message, settings);
    return Promise.resolve({ isConfirmed: true });
  };

  window.epicConfirm = function (message, options) {
    var settings = options || {};

    if (!swalAvailable()) {
      return Promise.resolve({ isConfirmed: window.confirm(String(message)) });
    }

    return window.Swal.fire({
      icon: settings.icon || "question",
      title: settings.title || titles.question,
      text: String(message),
      showCancelButton: true,
      focusCancel: true,
      reverseButtons: true,
      confirmButtonText: settings.confirmButtonText || "Yes, continue",
      cancelButtonText: settings.cancelButtonText || "Cancel",
      confirmButtonColor: settings.danger ? "#bd2d3b" : "#005aab",
      cancelButtonColor: "#687583",
      heightAuto: false,
      customClass: {
        popup: "epic-swal-popup",
        confirmButton: "epic-swal-confirm",
        cancelButton: "epic-swal-cancel"
      }
    });
  };

  function showLoading(form) {
    var title = form.dataset.loadingTitle || "Saving your changes";

    form.setAttribute("aria-busy", "true");
    form.querySelectorAll("button[type='submit'], input[type='submit']").forEach(function (button) {
      button.disabled = true;
    });

    if (window.toastr && typeof window.toastr.clear === "function") window.toastr.clear();

    if (swalAvailable()) {
      window.Swal.fire({
        title: title,
        text: "Please wait a moment.",
        allowOutsideClick: false,
        allowEscapeKey: false,
        showConfirmButton: false,
        heightAuto: false,
        customClass: { popup: "epic-swal-popup" },
        didOpen: function () {
          window.Swal.showLoading();
        }
      });
    }
  }

  function continueSubmission(form, submitter) {
    form.dataset.confirmed = "true";

    if (typeof form.requestSubmit === "function") {
      if (submitter) {
        form.requestSubmit(submitter);
      } else {
        form.requestSubmit();
      }
      return;
    }

    showLoading(form);
    form.submit();
  }

  document.addEventListener("submit", function (event) {
    var form = event.target;

    if (!(form instanceof HTMLFormElement) || form.method.toLowerCase() !== "post") return;

    if (!form.checkValidity()) {
      event.preventDefault();
      form.classList.add("was-validated");

      var invalidField = form.querySelector(":invalid");
      if (invalidField) invalidField.focus();

      window.epicToast("warning", "Please complete the required fields and correct the highlighted information.");
      return;
    }

    if (form.dataset.confirm && form.dataset.confirmed !== "true") {
      event.preventDefault();
      var submitter = event.submitter;

      window.epicConfirm(form.dataset.confirm, {
        title: form.dataset.confirmTitle || "Please confirm",
        confirmButtonText: form.dataset.confirmButton || "Yes, continue",
        danger: form.dataset.confirmDanger === "true"
      }).then(function (result) {
        if (result.isConfirmed) continueSubmission(form, submitter);
      });
      return;
    }

    showLoading(form);
  });

  document.addEventListener("DOMContentLoaded", function () {
    var nodes = document.querySelectorAll("#epicNotification [data-epic-notification]");
    var modals = [];

    nodes.forEach(function (node) {
      var items = [];
      try {
        items = JSON.parse(node.dataset.items || "[]");
      } catch (error) {
        items = [];
      }

      var options = { title: node.dataset.title || "", items: Array.isArray(items) ? items : [] };
      var type = node.dataset.type || "info";
      var message = node.dataset.message || "";

      if (node.dataset.channel === "modal") {
        modals.push([type, message, options]);
      } else {
        window.epicToast(type, message, options);
      }
    });

    // Show modals one after another so none is lost.
    modals.reduce(function (chain, args) {
      return chain.then(function () {
        return window.epicAlert(args[0], args[1], args[2]);
      });
    }, Promise.resolve());
  });
})();
