/* TheFarmConcept — พฤติกรรมของหน้าแดชบอร์ดภาพรวม (admin/dashboard)

   ค่าและกราฟทั้งหมดถูก render มาจากเซิร์ฟเวอร์แล้ว ไฟล์นี้ทำแค่ 4 อย่าง
   1. tooltip ตามเมาส์         — ย้ายด้วย style.left/top ตรง ๆ ไม่แตะ DOM อื่น
   2. ไฮไลต์ชิ้นที่ชี้ ชิ้นอื่นจาง — เทียบด้วย data-dbo-key
   3. สลับช่วงเวลา              — ขอเฉพาะเนื้อในมาแทน ไม่โหลดหน้าใหม่
   4. จัดเส้นฐานกราฟแท่ง        — ให้เท่าความสูงจริงของรายการช่วงอายุ

   ข้อสำคัญตาม handoff: ห้าม re-render อะไรตอน mousemove
   ถ้าอัปเดต state ทุกครั้งที่เมาส์ขยับ ทั้งหน้าจะวาดใหม่แล้วกราฟกระพริบ
   ที่นี่ mousemove เขียนแค่ style สองค่าของกล่อง tooltip เท่านั้น

   โหลดเป็นสคริปต์ท้ายสุด (@push('page-script')) จึงมี DOM ครบแล้วตอนทำงาน */
(function () {
  var body = document.getElementById('dbo-body');
  var tip = document.getElementById('dbo-tip');
  var filters = document.getElementById('dbo-filters');
  var stamp = document.getElementById('dbo-stamp');

  if (!body || !tip) return;

  var tipDot = tip.querySelector('[data-tip-dot]');
  var tipTitle = tip.querySelector('[data-tip-title]');
  var tipLines = tip.querySelector('[data-tip-lines]');

  /* activeKey = กลุ่มที่กำลังสว่าง (ใช้คุมการจาง) — หลายชิ้นใช้คีย์เดียวกันได้
     activeEl  = ชิ้นที่เมาส์อยู่จริง ใช้ตัดสินว่าต้องวาดเนื้อ tooltip ใหม่ไหม
     ต้องแยกกันเพราะจุดบนกราฟเส้นทุกจุดในเส้นเดียวใช้คีย์เดียวกัน (สว่างทั้งเส้น)
     แต่เนื้อ tooltip ของแต่ละจุดเป็นหัวข้อคนละหัวข้อ */
  var activeKey = null;
  var activeEl = null;

  /* ---------------------------------------------------------------
     1) ตำแหน่ง tooltip
     เว้นจากปลายเคอร์เซอร์ 14px ตามต้นแบบ แล้วหนีขอบจอถ้าจะล้น
     --------------------------------------------------------------- */
  var CURSOR_GAP = 14;
  var EDGE = 8;

  function moveTip(event) {
    if (!tip.classList.contains('is-open')) return;

    var half = tip.offsetWidth / 2;
    var x = event.clientX;
    var y = event.clientY - CURSOR_GAP;

    /* กล่องถูกจัดกึ่งกลางแนวนอนด้วย translate(-50%) — หนีขอบจึงบีบที่จุดกึ่งกลาง */
    x = Math.min(Math.max(x, half + EDGE), window.innerWidth - half - EDGE);

    /* ชิดขอบบนแล้วไม่มีที่วางเหนือเคอร์เซอร์ ให้พลิกไปอยู่ใต้เคอร์เซอร์แทน */
    if (y - tip.offsetHeight < EDGE) {
      y = event.clientY + CURSOR_GAP + tip.offsetHeight;
    }

    tip.style.left = x + 'px';
    tip.style.top = y + 'px';
  }

  /* ---------------------------------------------------------------
     2) เนื้อหาและการไฮไลต์
     --------------------------------------------------------------- */
  function readTip(el) {
    var raw = el.getAttribute('data-dbo-tip');
    if (!raw) return null;
    try {
      return JSON.parse(raw);
    } catch (e) {
      return null;
    }
  }

  function group(key) {
    return body.querySelectorAll('[data-dbo-key="' + key.replace(/"/g, '\\"') + '"]');
  }

  function markGroup(key, on) {
    var nodes = group(key);
    for (var i = 0; i < nodes.length; i++) {
      nodes[i].classList.toggle('is-on', on);
    }
  }

  /* สีของกลุ่ม — อ่านจากชิ้นแรกในกลุ่มที่มี --dbo-c ตั้งไว้
     แถวในตารางไม่ได้ถือสีของตัวเอง สีอยู่ที่แท่งกราฟที่คู่กัน จึงต้องกวาดหาทั้งกลุ่ม
     ไม่ใช่อ่านจากชิ้นที่เมาส์อยู่เท่านั้น */
  function groupColor(key) {
    var nodes = group(key);
    for (var i = 0; i < nodes.length; i++) {
      var value = getComputedStyle(nodes[i]).getPropertyValue('--dbo-c').trim();
      if (value) return value;
    }
    return '';
  }

  function openTip(el, key) {
    var content = readTip(el);

    /* ชิ้นที่ไม่มีเนื้อ tooltip (เช่นเส้นในกราฟ) ยังทำให้ชิ้นอื่นจางได้
       แต่ต้องไม่ทิ้งกล่องเนื้อหาของชิ้นก่อนหน้าค้างไว้ให้อ่านผิด */
    if (!content) {
      hideTip();
      return;
    }

    tipTitle.textContent = content.title;
    tipLines.innerHTML = '';

    (content.lines || []).forEach(function (line) {
      var row = document.createElement('div');
      row.className = 'dbo-tip-line';

      var k = document.createElement('span');
      k.className = 'dbo-tip-key';
      k.textContent = line[0];

      var v = document.createElement('span');
      v.className = 'dbo-tip-val dbo-num';
      v.textContent = line[1];

      row.appendChild(k);
      row.appendChild(v);
      tipLines.appendChild(row);
    });

    /* สีจุดนำมาจาก --dbo-c ของกลุ่มที่ชี้อยู่ ค่าสีจึงอยู่ใน CSS ที่เดียวเหมือนกราฟ */
    tipDot.style.background = groupColor(key) || 'currentColor';

    tip.classList.add('is-open');
    tip.setAttribute('aria-hidden', 'false');
  }

  function hideTip() {
    tip.classList.remove('is-open');
    tip.setAttribute('aria-hidden', 'true');
  }

  function closeTip() {
    if (activeKey) markGroup(activeKey, false);
    activeKey = null;
    activeEl = null;
    body.classList.remove('is-focused');
    hideTip();
  }

  /* ใช้ event delegation ตัวเดียวทั้งหน้า — ชิ้นกราฟมีเป็นร้อย ผูก listener ทีละชิ้น
     จะต้องผูกใหม่ทุกครั้งที่สลับช่วงเวลาเพราะ DOM ถูกแทน */
  body.addEventListener('mouseover', function (event) {
    var keyed = event.target.closest('[data-dbo-key]');

    if (!keyed) {
      if (activeKey) closeTip();
      return;
    }

    /* คีย์อยู่ที่ชิ้นในสุด (แท่ง/ชิ้นโดนัท) แต่เนื้อ tooltip อยู่ที่แถวที่ครอบมันอยู่
       จึงหาสองอย่างแยกกัน ไม่ใช่คาดว่าจะอยู่ที่ element เดียวกัน */
    var target = event.target.closest('[data-dbo-tip]') || keyed;

    if (target === activeEl) return;

    var key = keyed.getAttribute('data-dbo-key');

    /* ย้ายไปชิ้นอื่นในกลุ่มเดิม (จุดถัดไปบนเส้นเดียวกัน) ไม่ต้องคิดการจางใหม่
       แต่ยังต้องเปลี่ยนเนื้อ tooltip ให้เป็นของชิ้นที่ชี้อยู่จริง */
    if (key !== activeKey) {
      if (activeKey) markGroup(activeKey, false);
      activeKey = key;
      markGroup(key, true);
      body.classList.add('is-focused');
    }

    activeEl = target;
    openTip(target, key);
    moveTip(event);
  });

  body.addEventListener('mousemove', moveTip);
  body.addEventListener('mouseleave', closeTip);

  /* เลื่อนหน้าแล้วเคอร์เซอร์ไม่ได้อยู่บนชิ้นเดิมอีก — ปิดทิ้งไม่ให้กล่องค้างลอยผิดที่ */
  window.addEventListener('scroll', function () {
    if (activeKey) closeTip();
  }, { passive: true });

  /* ---------------------------------------------------------------
     3) เส้นฐานของกราฟแท่งหลักสูตร
     ผูกความสูงกับความสูงจริงของรายการช่วงอายุที่อยู่แผงข้าง ๆ
     ให้เส้นฐานสองกราฟอยู่ระดับเดียวกันตามที่ handoff ระบุ
     --------------------------------------------------------------- */
  var observer = null;

  function syncBaseline() {
    var source = body.querySelector('[data-dbo-baseline-source]');
    var bars = body.querySelector('.dbo-bars');
    if (!source || !bars) return;

    /* จอแคบที่สองแผงตกบรรทัดคนละบรรทัดแล้ว การล็อกความสูงไม่มีความหมาย
       ปล่อยให้กราฟใช้ความสูงของตัวเองตาม CSS */
    if (source.getBoundingClientRect().top !== bars.getBoundingClientRect().top) {
      bars.style.removeProperty('--dbo-chart-h');
      return;
    }

    var height = Math.round(source.getBoundingClientRect().height);
    if (height > 40) bars.style.setProperty('--dbo-chart-h', height + 'px');
  }

  function watchBaseline() {
    var source = body.querySelector('[data-dbo-baseline-source]');
    if (observer) observer.disconnect();
    if (!source || typeof ResizeObserver === 'undefined') return;

    observer = new ResizeObserver(syncBaseline);
    observer.observe(source);
  }

  /* ---------------------------------------------------------------
     4) กล่อง treemap ที่เตี้ยเกินกว่าจะใส่ชื่อได้
     เซิร์ฟเวอร์เดาไว้จากสัดส่วนความสูงแล้ว ที่นี่วัดพิกเซลจริงแล้วแก้ให้ตรง
     --------------------------------------------------------------- */
  var COMPACT_HEIGHT = 62;

  function syncTiles() {
    var tiles = body.querySelectorAll('.dbo-tile');
    for (var i = 0; i < tiles.length; i++) {
      tiles[i].classList.toggle('is-compact', tiles[i].getBoundingClientRect().height < COMPACT_HEIGHT);
    }
  }

  /* ---------------------------------------------------------------
     5) สลับช่วงเวลา
     ขอ HTML ของเนื้อในมาแทนทั้งก้อน ไม่ส่ง JSON มาให้ JS วาดกราฟใหม่
     สูตรวาดกราฟจึงมีชุดเดียวอยู่ฝั่งเซิร์ฟเวอร์
     --------------------------------------------------------------- */
  var pending = null;

  function setBusy(on) {
    /* ไม่ถอดเนื้อหาเดิมออกตอนโหลด — จางลงแต่ยังอยู่ที่เดิม ตามข้อ 1 ของมาตรฐาน Motion
       ถ้าเปลี่ยนเป็น spinner แทนทั้งก้อน หน้าจะกระพริบและความสูงกระโดด */
    body.classList.toggle('is-refreshing', on);
    body.setAttribute('aria-busy', on ? 'true' : 'false');
    if (filters) filters.classList.toggle('is-busy', on);
  }

  function selectPill(range) {
    if (!filters) return;
    var pills = filters.querySelectorAll('[data-range]');
    for (var i = 0; i < pills.length; i++) {
      var on = pills[i].getAttribute('data-range') === range;
      pills[i].classList.toggle('is-active', on);
      if (on) pills[i].setAttribute('aria-current', 'true');
      else pills[i].removeAttribute('aria-current');
    }
  }

  function load(range, push) {
    if (pending) pending.abort();
    pending = new AbortController();

    /* ชิปเปลี่ยนสีทันทีที่กด ไม่รอเซิร์ฟเวอร์ — ผู้ใช้ต้องเห็นว่าคลิกติดแล้ว */
    selectPill(range);
    setBusy(true);
    closeTip();

    fetch('/admin/dashboard/fragment?range=' + encodeURIComponent(range), {
      headers: { Accept: 'application/json' },
      signal: pending.signal
    })
      .then(function (res) {
        if (!res.ok) throw new Error('โหลดข้อมูลไม่สำเร็จ');
        return res.json();
      })
      .then(function (payload) {
        body.innerHTML = payload.html;
        watchBaseline();
        syncBaseline();
        syncTiles();

        if (push) {
          history.pushState({ range: payload.range }, '', '/admin/dashboard?range=' + payload.range);
        }
      })
      .catch(function (err) {
        if (err.name === 'AbortError') return;
        if (window.TFC && window.TFC.showToast) {
          window.TFC.showToast(err.message, 'danger');
        }
        /* ล้มเหลวแล้วต้องกลับไปชี้ที่ช่วงเดิมที่ข้อมูลบนจอเป็นของจริง
           ไม่ปล่อยให้ชิปบอกช่วงหนึ่งแต่ตัวเลขเป็นอีกช่วง */
        selectPill(currentRange());
      })
      .finally(function () {
        pending = null;
        setBusy(false);
      });
  }

  function currentRange() {
    var active = filters && filters.querySelector('.dbo-pill.is-active');
    return active ? active.getAttribute('data-range') : 'all';
  }

  if (filters) {
    filters.addEventListener('click', function (event) {
      var pill = event.target.closest('[data-range]');
      if (!pill) return;

      /* คลิกกลาง/คลิกพร้อม Ctrl ต้องเปิดแท็บใหม่ได้ตามปกติของลิงก์ */
      if (event.metaKey || event.ctrlKey || event.shiftKey || event.button !== 0) return;

      event.preventDefault();

      var range = pill.getAttribute('data-range');
      if (range === currentRange() && !body.classList.contains('is-refreshing')) return;

      load(range, true);
    });
  }

  /* ปุ่มย้อนกลับของเบราว์เซอร์ต้องพากลับไปช่วงก่อนหน้า ไม่ใช่ออกจากหน้าไปเลย */
  window.addEventListener('popstate', function (event) {
    var range = (event.state && event.state.range) || 'all';
    if (range !== currentRange()) load(range, false);
  });

  /* ---------------------------------------------------------------
     เริ่มทำงาน
     --------------------------------------------------------------- */
  watchBaseline();
  syncBaseline();
  syncTiles();
  window.addEventListener('resize', function () {
    syncBaseline();
    syncTiles();
  });

  /* ฟอนต์มาถึงช้ากว่า HTML ความสูงของรายการช่วงอายุจึงเปลี่ยนหลังฟอนต์โหลดเสร็จ
     วัดใหม่อีกครั้งตอนนั้น ไม่งั้นเส้นฐานสองกราฟจะเหลื่อมกันอยู่ไม่กี่พิกเซล */
  if (document.fonts && document.fonts.ready) {
    document.fonts.ready.then(function () {
      syncBaseline();
      syncTiles();
    });
  }

  /* ตั้ง state ตั้งต้นให้ popstate มีที่กลับ */
  history.replaceState({ range: currentRange() }, '', location.pathname + location.search);

  if (stamp) stamp.setAttribute('title', 'ข้อมูลถูกคำนวณตอนเปิดหน้า');
})();
