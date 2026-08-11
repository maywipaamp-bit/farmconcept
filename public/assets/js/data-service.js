/* TheFarmConcept — Data Service: ทางผ่านเดียวของทุกหน้าจอสำหรับอ่าน/บันทึก/ลบข้อมูล

   entity ที่ย้ายขึ้นฐานข้อมูลจริงแล้วจะประกาศ endpoint ไว้ที่ window.TFC_API
   (หน้า Blade เป็นคนใส่ให้) — dataService จะยิง fetch ไปที่นั่น
   entity ที่ยังไม่ได้ย้ายจะทำงานกับ window.TFC_MOCK ในหน่วยความจำเหมือนเดิม
   หน้าจอไม่ต้องรู้ว่าตัวเองอยู่โหมดไหน โค้ดหน้าเดิมจึงใช้ต่อได้โดยไม่ต้องแก้

   วิธีใช้:
     var svc = TFC.dataService('areas');
     svc.list().then(function (rows) { ... });
     svc.get('AREA-001').then(function (row) { ... });
     svc.create({ name: '...' }).then(function (row) { ... });
     svc.update('AREA-001', { active: false }).then(function (row) { ... });
     svc.remove('AREA-001').then(function () { ... });

   idField ค่าเริ่มต้นคือ 'id' — ส่งอาร์กิวเมนต์ที่สองถ้า entity ใช้คีย์อื่น */
window.TFC = window.TFC || {};

/* ทะเบียน endpoint ของ entity ที่ต่อฐานข้อมูลจริงแล้ว — หน้า Blade เติมเข้ามาก่อนไฟล์นี้ทำงาน */
window.TFC_API = window.TFC_API || {};

/* แถวชุดแรกที่ฝังมากับหน้า ใช้แทนคำขอแรกเพื่อไม่ให้ต้องรอ round-trip ตอนเปิดหน้า */
window.TFC_SEED = window.TFC_SEED || {};

window.TFC.dataService = function (entityKey, idField) {
  idField = idField || 'id';
  var LATENCY = 250;
  var base = window.TFC_API[entityKey] || null;

  /* ---------- โหมดฐานข้อมูลจริง ---------- */

  function csrf() {
    var tag = document.querySelector('meta[name="csrf-token"]');
    return tag ? tag.getAttribute('content') : '';
  }

  /* อ่านคำตอบเป็น JSON แล้วโยน error พร้อมข้อความไทยถ้าไม่สำเร็จ
     422 ส่ง error รายฟิลด์มา รวมเป็นข้อความเดียวให้ผู้ใช้เห็นว่าต้องแก้อะไรบ้าง */
  function readJson(res) {
    return res.json().catch(function () { return {}; }).then(function (data) {
      if (res.ok) return data;

      var msg = data.errors
        ? Object.keys(data.errors).map(function (k) { return data.errors[k][0]; }).join(' · ')
        : (data.message || 'ทำรายการไม่สำเร็จ');

      throw new Error(msg);
    });
  }

  function call(path, method, body) {
    var options = {
      method: method,
      headers: { 'X-CSRF-TOKEN': csrf(), 'Accept': 'application/json' },
      credentials: 'same-origin'
    };

    if (body) {
      options.headers['Content-Type'] = 'application/json';
      options.body = JSON.stringify(body);
    }

    return fetch(base + path, options).then(readJson);
  }

  /* ข้อมูลในหน่วยความจำต้องตามให้ทันด้วย เพราะหลายหน้าอ่าน window.TFC_MOCK ตรง ๆ
     เพื่อวาดตัวเลือกใน dropdown โดยไม่ผ่าน dataService */
  function syncMock(rows) {
    if (window.TFC_MOCK) window.TFC_MOCK[entityKey] = rows;
    return rows;
  }

  var remote = {
    list: function () {
      /* แถวชุดแรกถูกฝังมากับหน้าแล้ว (window.TFC_SEED) ใช้ทันทีโดยไม่ต้องยิงคำขอซ้ำ
         dev server ของ PHP บน Windows รับได้ทีละคำขอ การเปิดหน้าแล้วยิง XHR ตามทันที
         ทำให้ทุกหน้ารู้สึกหน่วงหนึ่งจังหวะ — ตัดคำขอนี้ทิ้งไปเลย
         ใช้ได้ครั้งเดียว ครั้งต่อไป (หลังบันทึก/ลบ) ต้องไปเอาของจริงจากเซิร์ฟเวอร์ */
      var seed = window.TFC_SEED && window.TFC_SEED[entityKey];

      if (seed) {
        delete window.TFC_SEED[entityKey];
        return Promise.resolve(syncMock(seed));
      }

      return call('', 'GET').then(function (data) { return syncMock(data.rows || []); });
    },

    get: function (id) {
      return remote.list().then(function (rows) {
        var row = rows.filter(function (r) { return r[idField] === id; })[0];
        if (!row) throw new Error(entityKey + ' not found: ' + id);
        return row;
      });
    },

    create: function (record) {
      return call('', 'POST', record).then(function (data) { return data.row; });
    },

    update: function (id, patch) {
      return call('/' + encodeURIComponent(id), 'PUT', patch).then(function (data) { return data.row; });
    },

    remove: function (id) {
      return call('/' + encodeURIComponent(id), 'DELETE').then(function () { return true; });
    }
  };

  /* ---------- โหมดข้อมูลจำลอง (entity ที่ยังไม่ได้ย้าย) ---------- */

  function store() {
    window.TFC_MOCK[entityKey] = window.TFC_MOCK[entityKey] || [];
    return window.TFC_MOCK[entityKey];
  }

  function nextId() {
    return entityKey.toUpperCase().slice(0, 4) + '-' + Date.now().toString(36).toUpperCase();
  }

  function today() {
    return new Date().toISOString().slice(0, 10);
  }

  var local = {
    list: function () {
      return new Promise(function (resolve) {
        setTimeout(function () { resolve(store().slice()); }, LATENCY);
      });
    },

    get: function (id) {
      return new Promise(function (resolve, reject) {
        setTimeout(function () {
          var row = store().filter(function (r) { return r[idField] === id; })[0];
          if (row) resolve(row); else reject(new Error(entityKey + ' not found: ' + id));
        }, LATENCY);
      });
    },

    create: function (record) {
      return new Promise(function (resolve) {
        setTimeout(function () {
          var row = Object.assign({}, record);
          if (!row[idField]) row[idField] = nextId();
          row.updatedAt = today();
          store().push(row);
          resolve(row);
        }, LATENCY);
      });
    },

    update: function (id, patch) {
      return new Promise(function (resolve, reject) {
        setTimeout(function () {
          var arr = store();
          var idx = arr.findIndex(function (r) { return r[idField] === id; });
          if (idx === -1) { reject(new Error(entityKey + ' not found: ' + id)); return; }
          arr[idx] = Object.assign({}, arr[idx], patch, { updatedAt: today() });
          resolve(arr[idx]);
        }, LATENCY);
      });
    },

    remove: function (id) {
      return new Promise(function (resolve) {
        setTimeout(function () {
          var arr = store();
          var idx = arr.findIndex(function (r) { return r[idField] === id; });
          if (idx > -1) arr.splice(idx, 1);
          resolve(true);
        }, LATENCY);
      });
    }
  };

  return base ? remote : local;
};
