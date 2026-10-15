/* EPIC shared password controls. */
(function () {
  "use strict";

  function wirePasswordToggles() {
    document.addEventListener("click", function (event) {
      var button = event.target.closest("[data-password-toggle]");
      if (!button) return;

      var input = document.getElementById(button.dataset.passwordToggle);
      if (!input) return;

      var showing = input.type === "text";
      input.type = showing ? "password" : "text";
      button.textContent = showing ? "Show" : "Hide";
      button.setAttribute("aria-label", showing ? "Show password" : "Hide password");
    });
  }

  document.addEventListener("DOMContentLoaded", function () {
    wirePasswordToggles();
  });
})();
