// ra-admin (clean light-only)

(function ($) {
  "use strict";

  // 04. Sidebar toggle
  $(document).on("click", ".header-toggle", function () {
    $("nav").toggleClass("semi-nav");
  });

  $(document).on("click", ".toggle-semi-nav", function () {
    $("nav").removeClass("semi-nav");
  });

  // 05. Responsive semi-nav (solo si querés ese comportamiento)
  function applyResponsiveNav() {
    const $nav = $("nav");
    $nav.removeClass("semi-nav");

    // Semi-nav automático solo en pantallas medianas
    if (window.innerWidth >= 768 && window.innerWidth < 1199) {
      $nav.addClass("semi-nav");
    }
  }
  $(applyResponsiveNav);
  window.addEventListener("resize", applyResponsiveNav);

  // 06. SimpleBar (sidebar scroll)
  $(function () {
    const el = document.getElementById("app-simple-bar");
    if (el && window.SimpleBar) {
      new SimpleBar(el, { autoHide: true });
    }
  });

  // 06.1 Active menu por URL (abre el collapse correspondiente)
  $(function () {
    const currentPath = window.location.pathname.replace(/\/+$/, "");

    $(".main-nav a[href]").each(function () {
      const href = $(this).attr("href");
      if (!href || href.startsWith("#") || href.startsWith("javascript:")) return;

      let linkPath;
      try {
        linkPath = new URL(href, window.location.origin).pathname.replace(/\/+$/, "");
      } catch (e) {
        return;
      }

      if (linkPath === currentPath) {
        const $li = $(this).closest("li");
        $li.addClass("active");

        const $collapse = $(this).closest("ul.collapse");
        if ($collapse.length) {
          $collapse.addClass("show");
          $collapse.prev("a")
            .attr("aria-expanded", "true")
            .removeClass("collapsed");
        }
      }
    });
  });

  // 13. Searchbar (si usás .search-filter)
  $(document).on("keyup", ".search-filter", function () {
    const search = ($(this).val() || "").toLowerCase();

    $(".search-list").each(function () {
      $(this).find(".search-list-content h6").each(function () {
        const $h6 = $(this);
        const original = ($h6.text() || "").toLowerCase();

        if (original.includes(search)) {
          $h6.closest(".search-list-item").show();
        } else {
          $h6.closest(".search-list-item").hide();
        }
      });
    });
  });

  // 14. CloseCollapse (solo uno abierto, sin “parpadeo”)
  document.querySelectorAll('.main-nav a[data-bs-toggle="collapse"]').forEach((trigger) => {
    trigger.addEventListener("click", function () {
      const sel = this.getAttribute("data-bs-target") || this.getAttribute("href");
      const target = sel ? document.querySelector(sel) : null;

      document.querySelectorAll(".main-nav ul.collapse").forEach((c) => {
        if (target && c === target) return;
        c.classList.remove("show");

        const t = c.previousElementSibling;
        if (t) {
          t.setAttribute("aria-expanded", "false");
          t.classList.add("collapsed");
        }
      });
    });
  });

})(jQuery);
