(function () {
  "use strict";

  var form = document.querySelector("[data-payment-settings-form]");
  if (!form) return;

  var provider = form.querySelector("[data-payment-provider]");
  var panels = form.querySelectorAll("[data-payment-provider-panel]");
  if (!provider || panels.length === 0) return;

  function activePanel(value) {
    if (value === "square" || value === "manual") return value;
    return "generic";
  }

  function updatePanels() {
    var selected = activePanel(provider.value);
    panels.forEach(function (panel) {
      var visible = panel.getAttribute("data-payment-provider-panel") === selected;
      panel.hidden = !visible;
      panel.setAttribute("aria-hidden", visible ? "false" : "true");
    });
  }

  provider.addEventListener("change", updatePanels);
  updatePanels();
})();
