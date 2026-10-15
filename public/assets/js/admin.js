(function () {
  "use strict";

  var sidebar = document.getElementById("adminSidebar");
  var scrim = document.querySelector(".sidebar-scrim");

  function setSidebar(open) {
    if (!sidebar || !scrim) return;
    sidebar.classList.toggle("is-open", open);
    scrim.classList.toggle("is-open", open);
  }

  document.addEventListener("click", function (event) {
    var openButton = event.target.closest("[data-sidebar-open]");
    var closeButton = event.target.closest("[data-sidebar-close]");

    if (openButton) setSidebar(true);
    if (closeButton) setSidebar(false);
  });

  document.addEventListener("keydown", function (event) {
    if (event.key === "Escape") setSidebar(false);
  });
})();

(function () {
  "use strict";

  function initTables() {
    if (typeof window.jQuery === "undefined" || typeof window.jQuery.fn.DataTable === "undefined") return;
    window.jQuery("table.js-datatable").each(function () {
      if (window.jQuery.fn.dataTable.isDataTable(this)) return;
      window.jQuery(this).DataTable({
        paging: true,
        pageLength: 10,
        lengthChange: true,
        lengthMenu: [10, 25, 50, 100],
        info: true,
        searching: true,
        ordering: true,
        order: [],
        language: {
          search: "",
          searchPlaceholder: "Search this table…",
          info: "Showing _START_ to _END_ of _TOTAL_",
          infoEmpty: "No entries to show",
          infoFiltered: "(filtered from _MAX_ total)",
          emptyTable: "No matching records found",
          lengthMenu: "Show _MENU_"
        },
        columnDefs: [{ targets: "_all", defaultContent: "" }]
      });
    });
  }

  if (document.readyState === "loading") document.addEventListener("DOMContentLoaded", initTables, { once: true });
  else initTables();
})();

(function () {
  "use strict";

  var form = document.getElementById("bulkSelectForm");
  if (!form) return;

  var selectAll = document.getElementById("selectAllBookings");
  if (selectAll) {
    selectAll.addEventListener("change", function () {
      document.querySelectorAll(".js-booking-select").forEach(function (box) {
        box.checked = selectAll.checked;
      });
    });
  }

  form.addEventListener("submit", function (event) {
    if (form.dataset.confirmed === "true") return;
    if (document.querySelector(".js-booking-select:checked")) return;

    event.preventDefault();
    event.stopImmediatePropagation();
    if (window.epicNotify) window.epicNotify("warning", "Select at least one booking first.");
  });
})();

(function () {
  "use strict";

  document.addEventListener("click", function (event) {
    var row = event.target.closest("tr[data-href]");
    if (!row) return;
    if (event.target.closest("a, button, input, label, select")) return;

    window.location.href = row.dataset.href;
  });
})();
