/* TheFarmConcept — Form helpers: prevent double submit, basic validation, upload preview */
(function () {
  /* Prevent double submit on mock forms */
  document.querySelectorAll('form[data-mock-submit]').forEach(function (form) {
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      var submitBtn = form.querySelector('[type="submit"]');
      if (!submitBtn || submitBtn.classList.contains('btn-loading')) return;

      submitBtn.classList.add('btn-loading');
      submitBtn.disabled = true;

      setTimeout(function () {
        submitBtn.classList.remove('btn-loading');
        submitBtn.disabled = false;
        if (window.TFC && window.TFC.showToast) {
          window.TFC.showToast(form.getAttribute('data-mock-submit') || 'บันทึกข้อมูลสำเร็จ', 'success');
        }

        var overlay = form.closest('.modal-overlay, .drawer-overlay');
        if (overlay && window.TFC && window.TFC.closeModal) {
          window.TFC.closeModal(overlay.id);
        }

        var redirect = form.getAttribute('data-mock-redirect');
        if (redirect) window.location.href = redirect;
      }, 900);
    });
  });

  /* Basic required-field validation display */
  document.querySelectorAll('[data-validate]').forEach(function (input) {
    input.addEventListener('blur', function () {
      var group = input.closest('.form-group');
      if (!group) return;
      var errorEl = group.querySelector('.form-error-message');
      if (input.hasAttribute('required') && !input.value.trim()) {
        input.classList.add('is-invalid');
        if (errorEl) errorEl.classList.remove('hidden');
      } else {
        input.classList.remove('is-invalid');
        if (errorEl) errorEl.classList.add('hidden');
      }
    });
  });

  /* Upload zone: click-to-browse + file name preview */
  document.querySelectorAll('[data-upload-zone]').forEach(function (zone) {
    var fileInput = zone.querySelector('input[type="file"]');
    var listId = zone.getAttribute('data-upload-list');
    var list = listId ? document.getElementById(listId) : null;
    if (!fileInput) return;

    zone.addEventListener('click', function () { fileInput.click(); });

    ['dragover', 'dragenter'].forEach(function (evt) {
      zone.addEventListener(evt, function (e) {
        e.preventDefault();
        zone.classList.add('is-dragover');
      });
    });
    ['dragleave', 'drop'].forEach(function (evt) {
      zone.addEventListener(evt, function () { zone.classList.remove('is-dragover'); });
    });
    zone.addEventListener('drop', function (e) {
      e.preventDefault();
      if (e.dataTransfer.files.length) {
        fileInput.files = e.dataTransfer.files;
        renderFileList();
      }
    });

    fileInput.addEventListener('change', renderFileList);
    fileInput.addEventListener('click', function (e) { e.stopPropagation(); });

    function renderFileList() {
      if (!list) return;
      list.innerHTML = '';
      Array.prototype.forEach.call(fileInput.files, function (file) {
        var item = document.createElement('div');
        item.className = 'upload-file-item';
        item.innerHTML =
          '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><path d="M14 2v6h6"/></svg>' +
          '<span class="file-name"></span>' +
          '<button type="button" class="upload-file-remove" aria-label="ลบไฟล์">' +
          '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg></button>';
        item.querySelector('.file-name').textContent = file.name;
        item.querySelector('.upload-file-remove').addEventListener('click', function () {
          fileInput.value = '';
          item.remove();
        });
        list.appendChild(item);
      });
    }
  });
})();
