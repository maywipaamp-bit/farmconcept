/* TheFarmConcept — Data Service: the ONE place every screen's fetch/save/delete goes through.
   Right now every method reads/writes window.TFC_MOCK[entityKey] in memory and resolves a Promise
   on a short timeout to behave like a real network call. When a real backend is ready, swap the
   inside of these five functions for fetch() calls against the real API — no page that calls
   TFC.dataService(...) needs to change.

   Usage:
     var svc = TFC.dataService('areas');       // entityKey = window.TFC_MOCK.<entityKey> array
     svc.list().then(function (rows) { ... });
     svc.get('AREA-001').then(function (row) { ... });
     svc.create({ name: '...' }).then(function (row) { ... });   // assigns an id if missing
     svc.update('AREA-001', { status: 'ปิดใช้งาน' }).then(function (row) { ... });
     svc.remove('AREA-001').then(function () { ... });

   idField defaults to 'id' — pass a second argument for entities keyed by another field. */
window.TFC = window.TFC || {};

window.TFC.dataService = function (entityKey, idField) {
  idField = idField || 'id';
  var LATENCY = 250;

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

  function list() {
    return new Promise(function (resolve) {
      setTimeout(function () { resolve(store().slice()); }, LATENCY);
    });
  }

  function get(id) {
    return new Promise(function (resolve, reject) {
      setTimeout(function () {
        var row = store().filter(function (r) { return r[idField] === id; })[0];
        if (row) resolve(row); else reject(new Error(entityKey + ' not found: ' + id));
      }, LATENCY);
    });
  }

  function create(record) {
    return new Promise(function (resolve) {
      setTimeout(function () {
        var row = Object.assign({}, record);
        if (!row[idField]) row[idField] = nextId();
        row.updatedAt = today();
        store().push(row);
        resolve(row);
      }, LATENCY);
    });
  }

  function update(id, patch) {
    return new Promise(function (resolve, reject) {
      setTimeout(function () {
        var arr = store();
        var idx = arr.findIndex(function (r) { return r[idField] === id; });
        if (idx === -1) { reject(new Error(entityKey + ' not found: ' + id)); return; }
        arr[idx] = Object.assign({}, arr[idx], patch, { updatedAt: today() });
        resolve(arr[idx]);
      }, LATENCY);
    });
  }

  function remove(id) {
    return new Promise(function (resolve) {
      setTimeout(function () {
        var arr = store();
        var idx = arr.findIndex(function (r) { return r[idField] === id; });
        if (idx > -1) arr.splice(idx, 1);
        resolve(true);
      }, LATENCY);
    });
  }

  return { list: list, get: get, create: create, update: update, remove: remove };
};
