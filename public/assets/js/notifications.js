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

  function safeType(type) {
    return allowedTypes.indexOf(type) !== -1 ? type : "info";
  }

  function swalAvailable() {
    return window.Swal && typeof window.Swal.fire === "function";
  }

  window.epicNotify = function (type, message, options) {
    var normalizedType = safeType(type);
    var settings = options || {};

    if (!swalAvailable()) {
      window.alert(String(message));
      return Promise.resolve({ isConfirmed: true });
    }

    var defaults = {
      icon: normalizedType,
      title: titles[normalizedType],
      text: String(message),
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
    };

    if (normalizedType === "success" && !settings.modal) {
      defaults.toast = true;
      defaults.position = "top-end";
      defaults.showConfirmButton = false;
      defaults.timer = 3500;
      defaults.timerProgressBar = true;
    }

    delete settings.modal;
    return window.Swal.fire(Object.assign(defaults, settings));
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

    if (swalAvailable()) {
      window.Swal.fire({
        title: title,
        text: "Please wait a moment.",
        allowOutsideClick: false,
        allowEscapeKey: false,
        showConfirmButton: false,
        heightAuto: false,
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

      window.epicNotify("warning", "Please complete the required fields and correct the highlighted information.");
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
    var notification = document.getElementById("epicNotification");

    if (notification && notification.dataset.message) {
      window.epicNotify(notification.dataset.type || "info", notification.dataset.message);
    }
  });
})();
