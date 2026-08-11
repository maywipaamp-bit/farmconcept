/* TheFarmConcept — Dynamic Row Repeater: shared add/remove row list for Forms-Popup fields that need
   more than one entry (course list, expertise list, follow-up round config, ...). Pairs with the
   .dynamic-row-list / .dynamic-row / .dynamic-row-remove CSS in components.css.

   Usage:
     var list = TFC.dynamicRowList('program-courses-list', 'program-add-course-btn', function (value) {
       return '<div class="dynamic-row"><span class="dynamic-row-order" data-row-order></span>' +
         '<input class="input" placeholder="ชื่อหลักสูตร" value="' + TFC.escapeHtml(value) + '">' +
         '<button type="button" class="dynamic-row-remove" aria-label="ลบแถว">...svg...</button></div>';
     });
     list.reset(['หลักสูตร A', 'หลักสูตร B']);          // re-seed rows (e.g. when opening the Edit popup)
     list.container.querySelectorAll('.input')          // read current values back out on submit
   rowHtmlFn(value) must return a single root element with class "dynamic-row" containing one element
   with [data-row-order] — the repeater fills that element's text with "1.", "2." etc after every change.
   onRowAdd(rowEl) (optional, 4th arg) runs right after a row is inserted — use it to init widgets that
   only enhance elements present at page load (e.g. TFC.initSmartSelects). */
window.TFC = window.TFC || {};

window.TFC.dynamicRowList = function (containerId, addBtnId, rowHtmlFn, onRowAdd) {
  var container = document.getElementById(containerId);
  var addBtn = addBtnId ? document.getElementById(addBtnId) : null;

  function renumber() {
    Array.from(container.querySelectorAll('[data-row-order]')).forEach(function (el, i) {
      el.textContent = (i + 1) + '.';
    });
  }

  function addRow(value) {
    var wrapper = document.createElement('div');
    wrapper.innerHTML = rowHtmlFn(value || '');
    var rowEl = wrapper.firstElementChild;
    container.appendChild(rowEl);
    renumber();
    /* แถวที่เพิ่งสร้างอาจมี widget ที่ต้อง init เอง (เช่น Smart Dropdown) */
    if (onRowAdd) onRowAdd(rowEl);
    return rowEl;
  }

  container.addEventListener('click', function (e) {
    var removeBtn = e.target.closest('.dynamic-row-remove');
    if (!removeBtn) return;
    var row = removeBtn.closest('.dynamic-row');
    if (row) row.remove();
    renumber();
  });

  if (addBtn) {
    addBtn.addEventListener('click', function () { addRow(''); });
  }

  function reset(values) {
    container.innerHTML = '';
    if (values && values.length) {
      values.forEach(addRow);
    } else {
      addRow('');
    }
  }

  return { addRow: addRow, reset: reset, container: container };
};
