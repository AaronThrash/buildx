(function () {
  // Build URL params from the form (keeps multiple values per key)
  function qs(form) {
    const fd = new FormData(form);
    const params = new URLSearchParams();
    for (const [k, v] of fd.entries()) {
      if (v !== "") params.append(k, v);
    }
    return params.toString();
  }

      // Update URL without reloading the page
  function updateURL(params, mode = "replace") {
    const url = new URL(window.location.href);

    // Normalize path: strip trailing "/page/{n}/" so everything
    // lives under the base Learning Center slug.
    url.pathname = url.pathname.replace(/\/page\/\d+\/?$/i, "/");

    // Apply updated query string (?paged=…, filters, etc.)
    url.search = params;

    if (mode === "push") {
      window.history.pushState({ params }, "", url.toString());
    } else {
      window.history.replaceState({ params }, "", url.toString());
    }
  }


    async function run(params) {
    // Always re-acquire the grid (the old node may have been replaced)

    const grid = document.getElementById("lr-grid");
    if (!grid || !window.buildxLr) return;

        // Do NOT preserve old pager; rely on server to return correct nav for this result
    grid.setAttribute("aria-busy", "true");

    grid.classList.add("is-loading");
    try {
      // DEBUG: uncomment if you want to see the outgoing params
      // console.log("LR params →", params);

      const res = await fetch(buildxLr.endpoint + "?" + params, {
        credentials: "same-origin",
      });
      const data = await res.json();
      if (data && data.html) {
        grid.outerHTML = data.html; // replace whole grid + pagination
      }
    } catch (e) {
      console.warn("LR AJAX failed", e);
        } finally {
    const newGrid = document.getElementById("lr-grid");
    if (newGrid) {
      newGrid.removeAttribute("aria-busy");
      newGrid.classList.remove("is-loading");

      // Ensure the pager sits at the very bottom of the grid
      const nav = newGrid.querySelector(".lr-pagination");
      if (nav && nav !== newGrid.lastElementChild) {
        newGrid.appendChild(nav);
      }
    }

    // Only bind the pager that exists inside #lr-grid; do not clone/move/delete others
    bindPagination();        // rebind after replace (scoped to #lr-grid)

    setupVideoLightbox();    // idempotent guard prevents double-wiring
  }


  }

  function wire() {
    const form = document.getElementById("lr-filters");
    const grid = document.getElementById('lr-grid');
    if (!form || !window.buildxLr) return;

    let t;
    const trigger = () => {
      clearTimeout(t);
            t = setTimeout(() => {
        const usp = new URLSearchParams(qs(form));
        usp.delete("paged");                // reset to page 1 on any filter/search change
        const params = usp.toString();
        updateURL(params);
        run(params);
      }, 150);

    };

    // Auto-apply on any change + "All" exclusivity in Content
    form.addEventListener("change", (e) => {
      const el = e.target;

      // Content ("format") behavior: "All" is exclusive
      if (el && el.name === "format[]") {
        const allCb = form.querySelector('input[name="format[]"][value="all"]');
        if (allCb) {
          if (el.value.toLowerCase() === "all" && el.checked) {
            form.querySelectorAll('input[name="format[]"]').forEach((cb) => {
              if (cb !== allCb) cb.checked = false;
            });
          } else if (el.checked && allCb.checked) {
            allCb.checked = false;
          }
        }
      }

      trigger(); // apply immediately
    });

    // Debounced live search
    form.addEventListener("input", (e) => {
      if (e.target.matches('input[type="search"], input[type="text"]')) {
        trigger();
      }
    });

    // Keep submit as a no-JS fallback (prevent reload when JS is on)
    form.addEventListener("submit", (e) => {
      e.preventDefault();
      trigger();
    });

    bindPagination(); // first bind
    document.documentElement.classList.add("js");
  }

    // Ensure exactly one pager INSIDE #lr-grid; move a nearby one in if needed; then bind it.
    function bindPagination() {
    const grid = document.getElementById("lr-grid");
    if (!grid) return;

    // Use the pager INSIDE the grid; if not there, only move an adjacent sibling
    let nav = grid.querySelector(".lr-pagination");
    if (!nav) {
      const next = grid.nextElementSibling;
      const prev = grid.previousElementSibling;
      if (next && next.matches(".lr-pagination")) { grid.appendChild(next); nav = next; }
      else if (prev && prev.matches(".lr-pagination")) { grid.appendChild(prev); nav = prev; }
    }
    if (!nav) return;

    if (nav.dataset.wired === "1") return;   // guard against double-binding
    nav.dataset.wired = "1";

    // Bind clicks (delegated) to fetch next page into #lr-grid
    nav.addEventListener("click", (e) => {

      const a = e.target.closest("a");
      if (!a) return;
      e.preventDefault();
        window.scrollTo(0, 0);
      const link = new URL(a.href, window.location.origin);

      // Accept both querystyle (?paged=2) and pretty permalinks (/page/2/)
      let page =
        link.searchParams.get("paged") ||
        link.searchParams.get("page") ||
        (link.pathname.match(/\/page\/(\d+)(?:\/|$)/) || [])[1];

      if (!page) page = "1";

      // Start from current form filters, then set/override paged
      const form = document.getElementById("lr-filters");
      const merged = new URLSearchParams(form ? qs(form) : "");
      merged.set("paged", page);
      merged.delete("page"); // keep canonical param

      const out = merged.toString();
      updateURL(out, "push");
      run(out);
    });


  }

  // When the user hits Back/Forward, re-run the query based on the URL.
  window.addEventListener("popstate", () => {
    const search = window.location.search.slice(1); // strip "?"
    // Empty string means "no filters, page 1" – backend handles that.
    run(search);
  });

  document.addEventListener("DOMContentLoaded", () => {
    // Delay 'wire' slightly. This resolves timing conflicts where the form might exist
    // but is not fully visible/ready for listeners to attach.
    setTimeout(wire, 50);
  });


})();

function setupVideoLightbox(){
  if (window.__lrLightboxWired) return;        // <-- guard
  window.__lrLightboxWired = true;

  const toEmbed = (url) => {
    try{
      const u = new URL(url);
      const host = u.hostname;
      if (host.includes('youtu')) {
        const id = u.searchParams.get('v') || u.pathname.split('/').filter(Boolean).pop();
        return id ? `https://www.youtube.com/embed/${id}?autoplay=1&rel=0` : null;
      }
      if (host.includes('vimeo.com')) {
        const id = u.pathname.split('/').filter(Boolean).pop();
        return id ? `https://player.vimeo.com/video/${id}?autoplay=1` : null;
      }
    }catch(e){}
    return null;
  };

  const open = (embed) => {
    const modal  = document.getElementById('lr-video-modal');  // re-query
    if (!modal) return;
    const player = modal.querySelector('.lr-modal__player');
    player.innerHTML = `<iframe src="${embed}" allow="autoplay; encrypted-media" allowfullscreen loading="lazy"></iframe>`;
    modal.hidden = false;
    document.documentElement.classList.add('lr-no-scroll');
  };

  const close = () => {
    const modal  = document.getElementById('lr-video-modal');  // re-query
    if (!modal) return;
    const player = modal.querySelector('.lr-modal__player');
    if (player) player.innerHTML = '';
    modal.hidden = true;
    document.documentElement.classList.remove('lr-no-scroll');
  };

  // One global delegate for play buttons (survives grid replacements)
  document.addEventListener('click', (e) => {
    const btn = e.target.closest('.lr-play');
    if (!btn || !btn.closest('#lr-grid')) return;
    e.preventDefault();
    e.stopPropagation();
    const embed = toEmbed(btn.dataset.video);
    if (embed) open(embed);
  });

  // Close handlers
  document.addEventListener('click', (e) => {
    if (e.target.matches('#lr-video-modal [data-close], #lr-video-modal .lr-modal__backdrop')) close();
  });
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') close();
  });

  console.debug('LC: video lightbox wired');
}



document.addEventListener('DOMContentLoaded', () => {
  setupVideoLightbox();
});

