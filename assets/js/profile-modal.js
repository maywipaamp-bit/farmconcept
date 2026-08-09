/* TheFarmConcept — Popup "โปรไฟล์ของฉัน"
   ============================================================================
   ทำไมเป็นสคริปต์กลาง ไม่ใช่หน้าจอ
   เมนูนี้อยู่ในดรอปดาวน์มุมขวาบนของหน้า admin ทุกหน้า (26 หน้า) ถ้าเขียน markup
   ไว้ในไฟล์ HTML จะต้องก็อป 26 ชุดและแก้ 26 ที่ทุกครั้ง สคริปต์นี้จึงฉีด Popup
   เข้าไปให้เอง แล้วเปลี่ยนลิงก์ <a href=".../profile.html"> เดิมให้เป็นตัวเปิด Popup
   (ลิงก์เดิมชี้ไปไฟล์ที่ไม่เคยมีอยู่จริง — เป็นลิงก์ตายมาตั้งแต่ต้น)

   สิทธิ์การแก้ไขตามที่กำหนด
   - แก้ได้:      รูปภาพ · ชื่อ · เบอร์โทร · Password
   - อ่านอย่างเดียว: Username · สิทธิ์
   ============================================================================ */
(function () {
  var MODAL_ID = 'profile-modal';

  function esc(s) {
    return window.TFC && window.TFC.escapeHtml ? window.TFC.escapeHtml(s == null ? '' : s) : String(s == null ? '' : s);
  }

  function currentUser() {
    return (window.TFC_MOCK && window.TFC_MOCK.currentUser) || {};
  }

  function buildModal() {
    if (document.getElementById(MODAL_ID)) return;
    var u = currentUser();

    var html =
      '<div class="modal-overlay" id="' + MODAL_ID + '">' +
        '<div class="modal modal-lg">' +
          '<div class="modal-header">' +
            '<div class="modal-heading">' +
              '<h3 class="modal-title">โปรไฟล์ของฉัน</h3>' +
              '<p class="modal-subtitle">แก้ไขได้เฉพาะรูปภาพ ชื่อ เบอร์โทร และรหัสผ่าน</p>' +
            '</div>' +
            '<button type="button" class="modal-close" data-close-modal aria-label="ปิดหน้าต่าง">' +
              '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>' +
            '</button>' +
          '</div>' +

          '<form id="profile-form">' +
            '<div class="modal-body">' +

              '<div class="profile-avatar-row">' +
                '<span class="profile-avatar" id="profile-avatar-preview">' + esc(u.initials || '') +
                  (u.avatar ? '<img src="' + esc(u.avatar) + '" alt="">' : '') +
                '</span>' +
                '<div class="profile-avatar-actions">' +
                  '<label class="btn btn-outline" for="profile-avatar-input">เปลี่ยนรูปภาพ</label>' +
                  '<input type="file" id="profile-avatar-input" accept="image/*" class="sr-only">' +
                  '<p class="caption text-secondary">ไฟล์ .jpg หรือ .png ขนาดไม่เกิน 2 MB</p>' +
                '</div>' +
              '</div>' +

              '<div class="form-row">' +
                '<div class="form-group mb-0">' +
                  '<label class="form-label" for="profile-name">ชื่อ–นามสกุล<span class="form-required">*</span></label>' +
                  '<input class="input" id="profile-name" value="' + esc(u.name) + '" required>' +
                '</div>' +
                '<div class="form-group mb-0">' +
                  '<label class="form-label" for="profile-phone">เบอร์โทร<span class="form-required">*</span></label>' +
                  '<input class="input" id="profile-phone" value="' + esc(u.phone) + '" required>' +
                '</div>' +
              '</div>' +

              '<div class="form-row">' +
                '<div class="form-group mb-0">' +
                  '<label class="form-label" for="profile-username">Username</label>' +
                  '<input class="input is-readonly" id="profile-username" value="' + esc(u.username) + '" readonly>' +
                  '<p class="caption text-secondary">แก้ไขไม่ได้ ติดต่อผู้ดูแลระบบหากต้องการเปลี่ยน</p>' +
                '</div>' +
                '<div class="form-group mb-0">' +
                  '<label class="form-label" for="profile-password">Password</label>' +
                  '<input class="input" id="profile-password" type="password" value="" placeholder="เว้นว่างไว้ถ้าไม่เปลี่ยน" autocomplete="new-password">' +
                '</div>' +
              '</div>' +

              '<div class="form-group mb-0">' +
                '<label class="form-label" for="profile-role">สิทธิ์</label>' +
                '<input class="input is-readonly" id="profile-role" value="' + esc(u.role) + '" readonly>' +
              '</div>' +

            '</div>' +
            '<div class="modal-footer">' +
              '<button type="button" class="btn btn-outline" data-close-modal>ยกเลิก</button>' +
              '<button type="submit" class="btn btn-primary" id="profile-save">บันทึก</button>' +
            '</div>' +
          '</form>' +
        '</div>' +
      '</div>';

    document.body.insertAdjacentHTML('beforeend', html);
    wireUp();
  }

  function wireUp() {
    var input = document.getElementById('profile-avatar-input');
    var preview = document.getElementById('profile-avatar-preview');
    var form = document.getElementById('profile-form');

    /* พรีวิวรูปทันทีที่เลือก ไม่ต้องกดบันทึกก่อน (ตัวช่วยกลางอยู่ใน app.js) */
    if (input && preview) window.TFC.attachAvatarPicker(preview, input, { maxMB: 2 });

    if (form) {
      form.addEventListener('submit', function (e) {
        e.preventDefault();
        var save = document.getElementById('profile-save');
        var name = document.getElementById('profile-name');
        var phone = document.getElementById('profile-phone');

        if (!name.value.trim()) { name.focus(); return; }
        if (!phone.value.trim()) { phone.focus(); return; }

        /* กันกดซ้ำระหว่างบันทึก ตามมาตรฐาน Motion ข้อ 4 */
        save.disabled = true;
        save.textContent = 'กำลังบันทึก…';

        setTimeout(function () {
          var u = currentUser();
          u.name = name.value.trim();
          u.phone = phone.value.trim();
          var img = document.querySelector('#profile-avatar-preview img');
          if (img) u.avatar = img.src;

          /* อัปเดตชื่อบน topbar ทันที ไม่ต้องรีเฟรชหน้า */
          var topbarName = document.querySelector('.topbar-profile-name');
          if (topbarName) topbarName.textContent = u.name;

          save.disabled = false;
          save.textContent = 'บันทึก';
          document.getElementById(MODAL_ID).classList.remove('is-open');
          document.body.style.overflow = '';
          document.getElementById('profile-password').value = '';
          if (window.TFC && window.TFC.showToast) window.TFC.showToast('บันทึกโปรไฟล์เรียบร้อยแล้ว', 'success');
        }, 400);
      });
    }
  }

  /* เปลี่ยนลิงก์ "โปรไฟล์ของฉัน" เดิมให้เปิด Popup แทนการพาไปหน้าที่ไม่มีอยู่จริง */
  function attachTrigger() {
    var links = document.querySelectorAll('a[href$="profile.html"]');
    for (var i = 0; i < links.length; i++) {
      links[i].setAttribute('href', '#');
      links[i].setAttribute('data-open-modal', MODAL_ID);
    }
  }

  /* ==========================================================================
     ทำให้ topbar เป็นค่าคงที่ทุกหน้า
     ปัญหาเดิม: แถบบนถูกเขียน markup ไว้ในไฟล์ HTML ทั้ง 17 หน้า จึงเพี้ยนกันเอง
       - ชื่อ/บทบาท/ตัวย่อ ถูก hardcode ไว้ ไม่ตรงกับผู้ใช้ที่ล็อกอินจริงใน TFC_MOCK.currentUser
       - มีแค่ 3 หน้าที่มีช่องค้นหาและกระดิ่งแจ้งเตือน อีก 14 หน้าไม่มี
     ที่นี่จึงบังคับให้ทุกหน้าเหลือโครงเดียวกันคือ breadcrumb (ซ้าย) + โปรไฟล์ (ขวา)
     ตามหน้าอ้างอิงที่ผ่านการตรวจแล้ว และดึงข้อมูลผู้ใช้จากแหล่งเดียว
     ========================================================================== */
  function syncTopbar() {
    var u = currentUser();

    var nameEl = document.querySelector('.topbar-profile-name');
    if (nameEl) nameEl.textContent = u.name || '';

    var roleEl = document.querySelector('.topbar-profile-role');
    if (roleEl) roleEl.textContent = u.role || '';

    var avatar = document.querySelector('.topbar-profile .avatar');
    if (avatar) {
      var img = avatar.querySelector('img');
      /* เขียนตัวย่อใหม่โดยไม่ลบ <img> ที่อยู่ข้างใน */
      Array.prototype.slice.call(avatar.childNodes).forEach(function (n) {
        if (n.nodeType === 3) n.remove();
      });
      avatar.insertBefore(document.createTextNode(u.initials || ''), avatar.firstChild);
      if (u.avatar && img) img.src = u.avatar;
    }

    /* ตัดส่วนที่มีแค่บางหน้าออก เพื่อให้แถบบนเหมือนกันหมด */
    var search = document.querySelector('.topbar-search');
    if (search) search.remove();

    var bell = document.querySelector('[data-notification-badge]');
    if (bell) {
      var dd = bell.closest('.dropdown');
      if (dd) dd.remove();
    }
  }

  function init() {
    buildModal();
    attachTrigger();
    syncTopbar();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
