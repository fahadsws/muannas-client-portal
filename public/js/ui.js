/* Shared UI enhancers: Select2 on every <select>, flatpickr range pickers on input[data-range].
   Both are picked up automatically when new HTML is rendered, so pages just output plain markup. */
(() => {
  const $j = window.jQuery;
  if (!$j || !$j.fn.select2) return;

  function enhance(root) {
    $j(root).find('select:not(.nos2):not(.swal2-select)').addBack('select:not(.nos2):not(.swal2-select)').each(function () {
      if (this.dataset.s2) return;
      this.dataset.s2 = '1';
      const el = this, $s = $j(el), modal = $s.closest('.modal');
      $s.select2({
        width: el.dataset.w || '100%',
        minimumResultsForSearch: +(el.dataset.search || 8),
        dropdownParent: modal.length ? modal : $j(document.body),
        placeholder: el.dataset.ph || undefined,
        allowClear: !!el.dataset.clear,
      });
      $s.on('select2:select select2:clear', () => el.dispatchEvent(new Event('change', {bubbles: true})));
    });

    if (window.flatpickr && root.querySelectorAll) {
      const inputs = root.matches && root.matches('input[data-range]') ? [root] : root.querySelectorAll('input[data-range]');
      inputs.forEach((el) => {
        if (el._flatpickr) return;
        window.flatpickr(el, {
          mode: 'range', dateFormat: 'Y-m-d', altInput: true, altFormat: 'd M Y', altInputClass: 'range-input',
          rangeSeparator: '  →  ', allowInput: false, showMonths: window.innerWidth > 700 ? 2 : 1, disableMobile: true,
          onClose: () => el.dispatchEvent(new Event('change', {bubbles: true})),
        });
      });
    }
  }

  /** Programmatic helpers other scripts can call after changing a value from code. */
  window.UI = {
    setSelect(el, value) { if (!el || el.value === String(value)) return; el.value = value; $j(el).trigger('change.select2'); },
    setRange(el, from, to) {
      if (!el || !el._flatpickr) return;
      if (from) el._flatpickr.setDate(to && to !== from ? [from, to] : [from], false); else el._flatpickr.clear(false);
    },
    getRange(el) {
      const fp = el && el._flatpickr; if (!fp || !fp.selectedDates.length) return {from: '', to: ''};
      const a = fp.selectedDates[0], b = fp.selectedDates[1] || a;
      return {from: fp.formatDate(a, 'Y-m-d'), to: fp.formatDate(b, 'Y-m-d')};
    },
  };

  new MutationObserver((muts) => muts.forEach((m) => m.addedNodes.forEach((n) => { if (n.nodeType === 1 && !n.classList.contains('select2-container') && !n.classList.contains('select2-dropdown')) enhance(n); })))
    .observe(document.body, {childList: true, subtree: true});
  enhance(document.body);
})();
