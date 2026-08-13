/* TheFarmConcept — Popup "โปรไฟล์ของฉัน" (Preview / Edit ในหน้าต่างเดียว) */
(function () {
  var MODAL_ID = 'profile-modal';
  var state = { mode: 'preview', avatarFile: null, avatarPreview: '' };

  function esc(value) {
    var text = value == null ? '' : String(value);
    return window.TFC && window.TFC.escapeHtml ? window.TFC.escapeHtml(text) : text;
  }

  function currentUser() {
    return (window.TFC_MOCK && window.TFC_MOCK.currentUser) || {};
  }

  function initialsOf(name) {
    return String(name || '').trim().split(/\s+/).slice(0, 2).map(function (part) {
      return part.charAt(0);
    }).join('');
  }

  function avatarHtml(user, id) {
    return '<span class="profile-avatar"' + (id ? ' id="' + id + '"' : '') + '>' +
      esc(user.initials || initialsOf(user.name)) +
      (user.avatar ? '<img src="' + esc(user.avatar) + '" alt="รูปโปรไฟล์">' : '') +
      '</span>';
  }

  function buildModal() {
    if (document.getElementById(MODAL_ID)) return;

    var html =
      '<div class="modal-overlay" id="' + MODAL_ID + '">' +
        '<section class="modal profile-modal" role="dialog" aria-modal="true" aria-labelledby="profile-modal-title">' +
          '<div class="modal-header">' +
            '<div class="modal-heading">' +
              '<h3 class="modal-title" id="profile-modal-title">โปรไฟล์ของฉัน</h3>' +
            '</div>' +
            '<button type="button" class="modal-close" data-close-modal aria-label="ปิดหน้าต่าง">' +
              '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>' +
            '</button>' +
          '</div>' +
          '<div id="profile-modal-content"></div>' +
        '</section>' +
      '</div>';

    document.body.insertAdjacentHTML('beforeend', html);
    wireUp();
    renderPreview();
  }

  function previewRow(icon, label, value, className) {
    return '<div class="profile-detail-row">' +
      '<span class="profile-detail-icon" aria-hidden="true">' + icon + '</span>' +
      '<span class="profile-detail-label">' + esc(label) + '</span>' +
      '<span class="profile-detail-value' + (className ? ' ' + className : '') + '">' + esc(value || '-') + '</span>' +
      '</div>';
  }

  function editRow(icon, label, input) {
    return '<div class="profile-detail-row is-editing">' +
      '<span class="profile-detail-icon" aria-hidden="true">' + icon + '</span>' +
      '<label class="profile-detail-label" for="' + esc(input.id) + '">' + esc(label) +
        (input.required ? '<span class="form-required">*</span>' : '') + '</label>' +
      '<input class="profile-inline-input" id="' + esc(input.id) + '" name="' + esc(input.name) + '"' +
        ' type="' + esc(input.type || 'text') + '" value="' + esc(input.value || '') + '"' +
        (input.placeholder ? ' placeholder="' + esc(input.placeholder) + '"' : '') +
        (input.maxlength ? ' maxlength="' + esc(input.maxlength) + '"' : '') +
        (input.autocomplete ? ' autocomplete="' + esc(input.autocomplete) + '"' : '') +
        (input.inputmode ? ' inputmode="' + esc(input.inputmode) + '"' : '') +
        (input.required ? ' required' : '') + '>' +
      '</div>';
  }

  function renderPreview() {
    var user = currentUser();
    var content = document.getElementById('profile-modal-content');
    if (!content) return;

    state.mode = 'preview';
    state.avatarFile = null;
    state.avatarPreview = '';
    content.innerHTML =
      '<div class="modal-body profile-preview">' +
        '<div class="profile-avatar-center">' +
          avatarHtml(user, '') +
        '</div>' +
        '<div class="profile-detail-list">' +
          previewRow('<svg viewBox="0 0 24 24"><path d="M20 21a8 8 0 0 0-16 0M12 13a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z"/></svg>', 'ชื่อ-นามสกุล', user.name) +
          previewRow('<svg viewBox="0 0 24 24"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.4 19.4 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .4 2 .7 2.8a2 2 0 0 1-.4 2.1L8.1 9.9a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.4c.9.3 1.8.6 2.8.7A2 2 0 0 1 22 16.9Z"/></svg>', 'เบอร์โทร', user.phone) +
          previewRow('<svg viewBox="0 0 24 24"><path d="M4 4h16v16H4zM8 9h8M8 13h5"/></svg>', 'Username', user.username) +
          previewRow('<svg viewBox="0 0 24 24"><rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg>', 'Password', '••••••••', 'is-password') +
          previewRow('<svg viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z"/></svg>', 'สิทธิ์', user.role, 'is-role') +
        '</div>' +
      '</div>' +
      '<div class="modal-footer">' +
        '<button type="button" class="btn btn-outline" data-close-modal>ปิด</button>' +
        '<button type="button" class="btn btn-primary" id="profile-edit">' +
          '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L8 18l-4 1 1-4Z"/></svg>' +
          'แก้ไข' +
        '</button>' +
      '</div>';
  }

  function renderEdit() {
    var user = currentUser();
    var content = document.getElementById('profile-modal-content');
    if (!content) return;

    state.mode = 'edit';
    state.avatarFile = null;
    state.avatarPreview = user.avatar || '';
    content.innerHTML =
      '<form id="profile-form" novalidate>' +
        '<div class="modal-body profile-edit">' +
          '<div class="profile-avatar-center is-editing">' +
            avatarHtml(user, 'profile-avatar-preview') +
            '<label class="profile-avatar-change" for="profile-avatar-input">เปลี่ยนรูปภาพ</label>' +
            '<input type="file" id="profile-avatar-input" name="avatar" accept="image/jpeg,image/png" class="sr-only">' +
            '<span class="caption text-secondary">JPG หรือ PNG ไม่เกิน 5 MB</span>' +
          '</div>' +
          '<div class="profile-detail-list">' +
            editRow('<svg viewBox="0 0 24 24"><path d="M20 21a8 8 0 0 0-16 0M12 13a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z"/></svg>', 'ชื่อ-นามสกุล', {
              id: 'profile-name', name: 'name', value: user.name, maxlength: 255, required: true
            }) +
            editRow('<svg viewBox="0 0 24 24"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.4 19.4 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .4 2 .7 2.8a2 2 0 0 1-.4 2.1L8.1 9.9a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.4c.9.3 1.8.6 2.8.7A2 2 0 0 1 22 16.9Z"/></svg>', 'เบอร์โทร', {
              id: 'profile-phone', name: 'phone', value: user.phone, maxlength: 30, inputmode: 'tel', required: true
            }) +
            editRow('<svg viewBox="0 0 24 24"><path d="M4 4h16v16H4zM8 9h8M8 13h5"/></svg>', 'Username', {
              id: 'profile-username', name: 'username', value: user.username, maxlength: 60, autocomplete: 'username', required: true
            }) +
            editRow('<svg viewBox="0 0 24 24"><rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg>', 'Password', {
              id: 'profile-password', name: 'password', type: 'password', placeholder: 'เว้นว่างหากไม่เปลี่ยน', maxlength: 255, autocomplete: 'new-password'
            }) +
            previewRow('<svg viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z"/></svg>', 'สิทธิ์', user.role, 'is-role') +
          '</div>' +
        '</div>' +
        '<div class="modal-footer">' +
          '<button type="button" class="btn btn-outline" id="profile-cancel">ยกเลิก</button>' +
          '<button type="submit" class="btn btn-primary" id="profile-save">บันทึก</button>' +
        '</div>' +
      '</form>';

    document.getElementById('profile-name').focus();
  }

  function showError(message, input) {
    if (input) {
      input.classList.add('is-invalid');
      input.focus();
    }
    if (window.TFC && window.TFC.showToast) window.TFC.showToast(message, 'danger');
  }

  function validateAvatar(file) {
    if (!file) return true;
    if (['image/jpeg', 'image/png'].indexOf(file.type) < 0) {
      showError('รองรับเฉพาะไฟล์ JPG และ PNG');
      return false;
    }
    if (file.size > 5 * 1024 * 1024) {
      showError('ขนาดรูปภาพต้องไม่เกิน 5 MB');
      return false;
    }
    return true;
  }

  function previewAvatar(file) {
    if (!validateAvatar(file)) return;
    state.avatarFile = file;
    var reader = new FileReader();
    reader.onload = function (event) {
      state.avatarPreview = event.target.result;
      var preview = document.getElementById('profile-avatar-preview');
      if (!preview) return;
      var image = preview.querySelector('img') || document.createElement('img');
      image.alt = 'รูปโปรไฟล์ที่เลือก';
      image.src = state.avatarPreview;
      if (!image.parentNode) preview.appendChild(image);
    };
    reader.readAsDataURL(file);
  }

  function updateVisibleUser(user) {
    var stored = currentUser();
    Object.keys(user || {}).forEach(function (key) { stored[key] = user[key]; });

    document.querySelectorAll('.rail-menu-name, .sidebar-profile-name').forEach(function (node) {
      node.textContent = stored.name || '';
    });

    document.querySelectorAll('#rail-avatar, .sidebar-profile .avatar').forEach(function (avatar) {
      avatar.textContent = stored.initials || initialsOf(stored.name);
      if (stored.avatar) {
        var image = document.createElement('img');
        image.src = stored.avatar;
        image.alt = '';
        avatar.appendChild(image);
      }
    });
  }

  function submitProfile(form) {
    var name = document.getElementById('profile-name');
    var phone = document.getElementById('profile-phone');
    var username = document.getElementById('profile-username');
    var password = document.getElementById('profile-password');
    var save = document.getElementById('profile-save');

    [name, phone, username, password].forEach(function (input) { input.classList.remove('is-invalid'); });
    if (!name.value.trim()) return showError('กรุณากรอกชื่อ-นามสกุล', name);
    if (!phone.value.trim()) return showError('กรุณากรอกเบอร์โทร', phone);
    if (!username.value.trim()) return showError('กรุณากรอก Username', username);
    if (password.value && password.value.length < 4) return showError('รหัสผ่านต้องมีอย่างน้อย 4 ตัวอักษร', password);

    var body = new FormData();
    body.append('name', name.value.trim());
    body.append('phone', phone.value.trim());
    body.append('username', username.value.trim());
    if (password.value) body.append('password', password.value);
    if (state.avatarFile) body.append('avatar', state.avatarFile);

    save.disabled = true;
    save.textContent = 'กำลังบันทึก…';

    fetch('/admin/profile', {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        'Accept': 'application/json'
      },
      body: body
    }).then(function (response) {
      return response.json().then(function (data) {
        if (!response.ok) throw data;
        return data;
      });
    }).then(function (data) {
      updateVisibleUser(data.data || {});
      renderPreview();
      if (window.TFC && window.TFC.showToast) window.TFC.showToast(data.message || 'บันทึกโปรไฟล์เรียบร้อยแล้ว', 'success');
    }).catch(function (error) {
      var errors = error && error.errors ? error.errors : {};
      var firstKey = Object.keys(errors)[0];
      var message = firstKey ? errors[firstKey][0] : (error.message || 'ไม่สามารถบันทึกโปรไฟล์ได้');
      var field = firstKey ? document.querySelector('[name="' + firstKey + '"]') : null;
      showError(message, field);
    }).finally(function () {
      if (!document.body.contains(save)) return;
      save.disabled = false;
      save.textContent = 'บันทึก';
    });
  }

  function wireUp() {
    var modal = document.getElementById(MODAL_ID);

    modal.addEventListener('click', function (event) {
      if (event.target.closest('#profile-edit')) renderEdit();
      if (event.target.closest('#profile-cancel')) renderPreview();
    });

    modal.addEventListener('change', function (event) {
      if (event.target.id === 'profile-avatar-input') previewAvatar(event.target.files[0]);
    });

    modal.addEventListener('submit', function (event) {
      if (event.target.id !== 'profile-form') return;
      event.preventDefault();
      submitProfile(event.target);
    });

    document.addEventListener('click', function (event) {
      if (event.target.closest('[data-open-modal="' + MODAL_ID + '"]')) renderPreview();
    });
  }

  function attachLegacyTriggers() {
    document.querySelectorAll('a[href$="profile.html"]').forEach(function (link) {
      link.setAttribute('href', '#');
      link.setAttribute('data-open-modal', MODAL_ID);
    });
  }

  function init() {
    buildModal();
    attachLegacyTriggers();
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
  else init();
})();
