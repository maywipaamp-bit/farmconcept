<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>เข้าสู่ระบบ | The Farm Concept</title>
<link rel="icon" type="image/png" href="{{ asset('assets/images/favicon.png') }}">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
{{-- URL เดียวกับใน standard/tokens.css และ layouts/admin.blade.php --}}
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@400;500;600&display=swap">
<!-- หน้านี้ยึดมาตรฐานเป็นหลักตามสเปก จึงโหลดเฉพาะ assets/css/standard/*
     (base.css มี reset, :focus-visible และ keyframes om-pop-in ให้แล้ว) -->
<link rel="stylesheet" href="@assetv('assets/css/standard/tokens.css')">
<link rel="stylesheet" href="@assetv('assets/css/standard/base.css')">
{{-- สเปก typography กลางของทุกหน้าจอ — คงไว้ตามเดิม ไม่ถอดมาตรฐานร่วมออกจากหน้านี้
     ไฟล์นั้นตั้งค่า .login-tagline / .login-footer ไว้ด้วย แต่กฎในหน้านี้เจาะจงเท่ากัน
     และประกาศทีหลัง จึงชนะ — ค่าที่แสดงจริงคือค่าจาก handoff ของหน้านี้ --}}
<link rel="stylesheet" href="@assetv('assets/css/typography-spec.css')">
<style>
  /* ==========================================================================
     หน้าเข้าสู่ระบบ — ค่าทั้งหมดมาจาก handoff ของหน้านี้ (design_handoff_login)

     สีชุดนี้ต่างจาก token กลางของระบบ (เขียวเข้ม #2f6b36 ไม่ใช่ #16A34A ·
     พื้นฟิลด์ #f6f9f4 ไม่ใช่ขาว) จึงประกาศเป็นตัวแปรของหน้านี้ไว้ที่บล็อกเดียว
     แล้วอ้างอิงตัวแปรทุกจุด — ไม่มีค่าสีลอย ๆ ในกฎใดเลย แก้สีทั้งหน้าที่นี่ที่เดียว

     ประกาศที่ :root ได้เพราะหน้านี้เป็นเอกสารเดี่ยว ไม่ได้ใช้ layout ร่วมกับหน้าอื่น
     และไม่ได้โหลด CSS ก้อนของระบบหลัง จึงไม่มีอะไรให้ชนกัน
     ========================================================================== */
  :root {
    /* พื้นหลังสามชั้นแบบ "กรอบบาง" */
    --lg-page: #f4f8f2;
    --lg-glow:
      radial-gradient(56% 46% at 50% 0%, rgba(104, 170, 112, .20), transparent 70%),
      radial-gradient(50% 40% at 50% 100%, rgba(206, 226, 140, .34), transparent 70%);

    --lg-card: #ffffff;
    --lg-divider: #f0f3ee;

    /* ฟิลด์ */
    --lg-field-bg: #f6f9f4;
    --lg-field-border: #e6ebe3;
    --lg-field-focus: #7fbb82;
    --lg-field-ring: 0 0 0 3px rgba(127, 187, 130, .18);

    /* ปุ่มหลัก / ลิงก์ */
    --lg-accent: #2f6b36;
    --lg-accent-hover: #245429;
    --lg-accent-soft: #f0f5ee;   /* พื้น hover ของปุ่มแสดง/ซ่อน */
    --lg-check: #4f9455;

    /* ข้อความ */
    --lg-text: #1f2c20;
    --lg-label: #55624f;
    --lg-body: #4a564a;
    --lg-muted: #7d8a7c;
    --lg-placeholder: #b8c2b6;
    --lg-on-accent: #ffffff;
    /* ท้ายหน้าอยู่บนพื้นสี ไม่ใช่พื้นขาว — ต้องเข้มกว่าเทาปกติถึงจะผ่านคอนทราสต์
       ห้ามเปลี่ยนเป็น --lg-muted (#7d8a7c) หรือ #9aa599 ตามที่ handoff เตือนไว้ */
    --lg-footer: #45543f;
    --lg-error: #c0504f;

    --lg-radius-card: 20px;
    --lg-radius-field: 11px;
    --lg-radius-reveal: 9px;
    --lg-field-h: 46px;
    --lg-dur: .15s;
  }

  /* ---------- โครงหน้า ----------
     สามชั้นของพื้นหลังทับกัน: พื้นที่ body · ชั้นแสง · เส้นกรอบ
     ทุกชั้นที่เป็นงานตกแต่ง pointer-events: none กันไปบังการคลิกในการ์ด */
  /* สเปกให้ overflow: hidden — คงไว้เฉพาะแนวนอน ซึ่งเป็นแกนที่ต้องกันจริง
     (ชั้นแสงกับเส้นกรอบต้องไม่ทำให้เกิดแถบเลื่อนซ้ายขวา)
     แนวตั้งปล่อยให้เลื่อนได้ ไม่งั้นบนมือถือเตี้ยหรือตอนเปิดคีย์บอร์ดขึ้นมา
     ปุ่มเข้าสู่ระบบจะถูกตัดหายแล้วล็อกอินไม่ได้เลย
     จอที่สูงพอไม่มีแถบเลื่อนขึ้นมาอยู่แล้วเพราะเนื้อหาสั้น

     ประกาศที่ html ไม่ใช่ body — ถ้าตั้ง overflow ที่ body ตัว body จะกลายเป็น
     กล่องเลื่อนของตัวเอง แล้วกันที่ไว้ให้แถบเลื่อนจนการ์ดแคบลงไปสองสามพิกเซล */
  html { overflow-x: hidden; }

  body {
    position: relative;
    /* dvh = ความสูงที่มองเห็นจริงบนมือถือ ซึ่งหักแถบ URL ออกแล้ว
       100vh บนมือถือนับรวมพื้นที่ใต้แถบ URL ทำให้เนื้อหาส่วนล่างถูกดันตกจอ
       ประกาศ vh ไว้บรรทัดก่อนเป็นค่าสำรองให้เบราว์เซอร์เก่าที่ยังไม่รู้จัก dvh */
    min-height: 100vh;
    min-height: 100dvh;
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 16px 20px 24px;
    background: var(--lg-page);
    color: var(--lg-text);
  }

  /* ชั้น 2 — แสงเขียวจากขอบบน + แสงเหลืองอมเขียวจากขอบล่าง
     ชั้น 3 — เส้นบางรอบจอ เว้นขอบ 22px ทุกด้าน

     ทั้งสองชั้นเป็น fixed ไม่ใช่ absolute เพราะเป็นฉากหลังของ "จอ" ไม่ใช่ของเนื้อหา
     ถ้าเป็น absolute แล้วหน้าเลื่อนได้ กรอบจะยืดยาวไปตามความสูงของเนื้อหาทั้งก้อน
     กลายเป็นกล่องยาวเลื่อนตามแทนที่จะเป็นกรอบรอบจอ */
  .login-glow {
    position: fixed;
    pointer-events: none;
  }

  .login-glow {
    inset: 0;
    background: var(--lg-glow);
  }

  /* การ์ดวางทับกรอบตรงกลางได้เลย ไม่ต้องเว้นระยะพิเศษ

     margin: auto จัดกึ่งกลางแนวตั้งแทน justify-content: center
     เพราะเมื่อเนื้อหาสูงกว่าจอ justify-content: center จะดันส่วนบนขึ้นไปเหนือขอบ
     จนเลื่อนขึ้นไปดูไม่ได้ ส่วน margin: auto ยุบเหลือ 0 เองเมื่อที่ไม่พอ */
  .login-col {
    position: relative;
    z-index: 1;
    margin: auto;
    width: 100%;
    max-width: 380px;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 14px;
  }

  /* ---------- การ์ด ---------- */
  .login-card {
    width: 100%;
    background: var(--lg-card);
    border-radius: var(--lg-radius-card);
    padding: 26px 30px 24px;
    box-shadow: 0 1px 2px rgba(30, 60, 32, .04), 0 26px 56px -30px rgba(24, 64, 30, .42);
    /* both = คงสถานะเฟรมแรกไว้ก่อนเริ่ม จึงไม่กระพริบตอนโหลด */
    animation: om-pop-in var(--dur-modal) var(--ease-out) both;
  }

  /* ---------- 1. โลโก้ + ชื่อระบบ ----------
     ไม่มีหัวข้อ "เข้าสู่ระบบ" ในการ์ดตามสเปก — โลโก้ทำหน้าที่นั้นแล้ว
     ไม่มีเส้นคั่นใต้ส่วนหัว (สเปกมี แต่ทีมให้ตัดออก) เว้นระยะ 20px แทน
     ระยะเท่านี้ยังแยกส่วนหัวออกจากฟอร์มได้ ไม่ต้องพึ่งเส้น */
  .login-brand {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    margin-bottom: 20px;
  }

  /* ใช้ PNG โปร่งใสของโปรเจกต์ จึงไม่ต้องมี mix-blend-mode: multiply
     แบบที่ต้นแบบต้องใช้เพราะไฟล์ต้นทางเป็น JPG พื้นขาว

     background: transparent จำเป็น — standard/base.css ตั้ง
     :where(img, video) { background: var(--bg-subtle) } ไว้กันแฟลชขาวตอนโหลด
     กฎนั้นทำให้เห็นพื้นเทาทะลุส่วนโปร่งใสของโลโก้ กลายเป็นกล่องเทารอบตัวอักษร

     ระบุ width/height ในแท็กด้วย เพื่อจองพื้นที่ไว้ก่อนรูปมาถึง ไม่ให้การ์ดกระโดด */
  .login-logo { width: 168px; height: auto; background: transparent; }

  .login-tagline {
    margin: 0;
    font-size: 13px;
    line-height: 1.55;
    font-weight: 400;
    color: var(--lg-muted);
    text-align: center;
    text-wrap: pretty;
  }

  /* ---------- 2. ฟอร์ม ---------- */
  .login-form { display: flex; flex-direction: column; gap: 12px; }
  .login-field { display: flex; flex-direction: column; gap: 7px; }

  .login-label {
    font-size: 13px;
    font-weight: 600;
    letter-spacing: .02em;
    color: var(--lg-label);
  }

  .login-input {
    width: 100%;
    height: var(--lg-field-h);
    padding: 0 14px;
    font-family: inherit;
    font-size: 14px;
    color: var(--lg-text);
    background: var(--lg-field-bg);
    border: 1px solid var(--lg-field-border);
    border-radius: var(--lg-radius-field);
    outline: none;
    transition: border-color var(--lg-dur), box-shadow var(--lg-dur), background var(--lg-dur);
  }

  .login-input::placeholder { color: var(--lg-placeholder); }

  /* เบราว์เซอร์เติมค่าที่จำไว้ให้เอง แล้วทับพื้นช่องด้วยสีของตัวเอง (Chrome เป็นม่วงอ่อน)
     สีช่องจึงเพี้ยนจากที่ออกแบบไว้ทุกครั้งที่มีรหัสที่จำไว้
     box-shadow inset หนา ๆ เป็นวิธีเดียวที่เขียนทับพื้นของ autofill ได้ (background ปกติแพ้)
     ใส่ทั้งชื่อมาตรฐานและชื่อแบบ -webkit- เพราะแต่ละเบราว์เซอร์รองรับไม่เท่ากัน */
  .login-input:-webkit-autofill,
  .login-input:-webkit-autofill:hover,
  .login-input:autofill {
    -webkit-text-fill-color: var(--lg-text);
    -webkit-box-shadow: 0 0 0 100px var(--lg-field-bg) inset;
    box-shadow: 0 0 0 100px var(--lg-field-bg) inset;
    caret-color: var(--lg-text);
  }

  /* ตอนโฟกัสพื้นช่องเป็นขาว ต้องคง ring เขียวไว้ด้วย ไม่ให้ box-shadow ด้านบนกลืนหายไป */
  .login-input:-webkit-autofill:focus,
  .login-input:autofill:focus {
    -webkit-box-shadow: 0 0 0 100px var(--lg-card) inset, var(--lg-field-ring);
    box-shadow: 0 0 0 100px var(--lg-card) inset, var(--lg-field-ring);
  }

  /* ถอด outline ของเบราว์เซอร์ได้ก็เพราะมี ring แทนแล้ว — ห้ามถอดโดยไม่มีตัวแทน
     ใส่ทั้ง :focus และ :focus-visible เพราะช่องกรอกต้องเห็นสถานะแม้คลิกด้วยเมาส์ */
  .login-input:focus,
  .login-input:focus-visible {
    border-color: var(--lg-field-focus);
    background: var(--lg-card);
    box-shadow: var(--lg-field-ring);
  }

  /* กล่องนี้ครอบ "เฉพาะช่องกรอก" ไม่รวม label
     ปุ่มแสดง/ซ่อนวางด้วย top: 5px ซึ่งวัดจากขอบบนของช่อง ถ้าครอบ label ไปด้วยปุ่มจะไปอยู่แถว label */
  .login-input-wrap { position: relative; display: flex; }

  /* เว้นที่ให้ปุ่มแสดง/ซ่อน ข้อความจึงไม่ลอดไปใต้ปุ่ม */
  .login-input-wrap .login-input { padding-right: 64px; }

  .login-reveal {
    position: absolute;
    right: 6px;
    top: 5px;
    height: 36px;
    padding: 0 11px;
    display: flex;
    align-items: center;
    border: 0;
    background: transparent;
    border-radius: var(--lg-radius-reveal);
    font-family: inherit;
    font-size: 12.5px;
    color: var(--lg-muted);
    cursor: pointer;
    transition: background var(--lg-dur), color var(--lg-dur);
  }

  .login-reveal:hover { background: var(--lg-accent-soft); color: var(--lg-accent); }
  .login-reveal:focus-visible { outline: none; box-shadow: var(--lg-field-ring); }

  /* ---------- 3. จำการเข้าสู่ระบบ ---------- */
  .login-remember {
    display: flex;
    align-items: center;
    gap: 9px;
    padding-top: 2px;
    font-size: 13px;
    color: var(--lg-body);
    cursor: pointer;
    user-select: none;
  }

  .login-remember input {
    width: 17px;
    height: 17px;
    margin: 0;
    accent-color: var(--lg-check);
    cursor: pointer;
  }

  .login-remember input:focus-visible { outline: none; box-shadow: var(--lg-field-ring); border-radius: 4px; }

  /* ---------- 4. ข้อความผิดพลาด ----------
     ข้อความเดียวกันทุกกรณี ทั้งชื่อผู้ใช้ไม่มีจริงและรหัสผ่านผิด (ดู LoginRequest)
     จึงไม่ผูกกับช่องใดช่องหนึ่ง วางเป็นแถบรวมของฟอร์ม */
  .login-error {
    display: flex;
    align-items: flex-start;
    gap: 8px;
    font-size: 12.5px;
    line-height: 1.55;
    color: var(--lg-error);
    animation: om-slide-in var(--dur-slow) var(--ease-out) both;
  }

  .login-error[hidden] { display: none; }
  .login-error svg { flex: none; width: 16px; height: 16px; margin-top: 2px; stroke-width: 1.6; }

  /* ---------- 5. ปุ่มเข้าสู่ระบบ ---------- */
  .login-submit {
    height: var(--lg-field-h);
    margin-top: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    border: 0;
    border-radius: var(--lg-radius-field);
    background: var(--lg-accent);
    color: var(--lg-on-accent);
    font-family: inherit;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: background var(--lg-dur);
  }

  .login-submit:hover { background: var(--lg-accent-hover); }
  .login-submit:focus-visible { outline: none; box-shadow: var(--lg-field-ring); }
  .login-submit:active { transform: scale(.985); }

  /* ยังกรอกไม่ครบ = กดไม่ได้ แต่ต้องไม่ดูเป็นปุ่มที่ถูกปิดถาวร
     คงพื้นเขียวเข้มไว้ เปลี่ยนแค่ cursor ให้รู้ว่ายังกดไม่ได้ */
  .login-submit:disabled { cursor: not-allowed; }
  .login-submit:disabled:hover { background: var(--lg-accent); }

  /* กำลังส่ง = จางลงเล็กน้อยพร้อม spinner ให้เห็นว่าระบบรับคำสั่งแล้ว */
  .login-submit.is-loading { opacity: .82; cursor: progress; }

  .login-spinner {
    width: 15px;
    height: 15px;
    flex: none;
    border: 2px solid rgba(255, 255, 255, .5);
    border-top-color: var(--lg-on-accent);
    border-radius: 50%;
    animation: login-spin .7s linear infinite;
  }

  @keyframes login-spin { to { transform: rotate(360deg); } }

  /* ---------- 6. ท้ายการ์ด ---------- */
  .login-card-foot {
    margin-top: 16px;
    padding-top: 14px;
    border-top: 1px solid var(--lg-divider);
    display: flex;
    justify-content: center;
    /* สองลิงก์ขึ้นบรรทัดเดียวกันเมื่อพอ ไม่พอก็ซ้อนกันเอง */
    flex-wrap: wrap;
    gap: 6px 16px;
  }

  .login-card-foot a {
    font-size: 13.5px;
    font-weight: 500;
    color: var(--lg-accent);
    text-decoration: none;
  }

  .login-card-foot a:hover { color: var(--lg-accent-hover); text-decoration: none; }

  /* ทางเข้าหน้าตรวจงาน — อยู่นอกการ์ด เพราะเป็นคนละเรื่องกับการเข้าสู่ระบบ */
  .login-review-link {
    display: block;
    text-align: center;
    font-size: 13.5px;
    font-weight: 500;
    color: var(--lg-accent);
    text-decoration: none;
  }

  .login-review-link:hover { color: var(--lg-accent-hover); text-decoration: none; }

  /* ---------- 7. บรรทัดท้ายหน้า (นอกการ์ด) ---------- */
  .login-footer {
    margin: 0;
    text-align: center;
    font-size: 12px;
    color: var(--lg-footer);
  }

  .login-footer a { color: var(--lg-footer); text-decoration: none; }
  .login-footer a:hover { text-decoration: underline; }
  .login-footer a.is-accent { color: var(--lg-accent); font-weight: 500; }

  /* ---------- มือถือ ----------
     เป้าหมายคือ "พอดีจอโดยไม่ต้องเลื่อน" บนมือถือทั่วไป
     บีบระยะแนวตั้งลงทุกจุดเท่าที่ทำได้ก่อน แล้วค่อยยอมให้เลื่อนถ้ายังไม่พอ

     การ์ดกว้าง 100% อยู่แล้ว (max-width 380px) จึงไม่ต้องมี breakpoint ของความกว้าง
     ที่ต้องลดคือขอบและระยะภายใน เพราะบนจอ 360px ขอบ 20px สองข้างกินไป 40px
     และย่อกรอบเข้ามาไม่ให้เส้นเบียดการ์ด */
  @media (max-width: 480px) {
    body { padding: 12px 14px 16px; }
    .login-card { padding: 22px 20px 20px; }
    .login-card-foot { margin-top: 14px; padding-top: 12px; }
    .login-brand { gap: 6px; margin-bottom: 16px; }
    .login-logo { width: 150px; }
    .login-col { gap: 12px; }
  }

  /* จอเตี้ยมาก (มือถือแนวนอน · จอ 640px ลงมา · เปิดคีย์บอร์ดขึ้นมาบังครึ่งจอ)
     ยุบระยะแนวตั้งอีกขั้นและย่อโลโก้ ให้ยังเห็นปุ่มเข้าสู่ระบบได้โดยไม่ต้องเลื่อน */
  @media (max-height: 640px) {
    .login-brand { margin-bottom: 14px; }
    .login-logo { width: 132px; }
    .login-card { padding: 18px 20px 18px; }
    .login-col { gap: 10px; }
  }
</style>
</head>
<body>

  {{-- พื้นหลังสองชั้นบนของ "กรอบบาง" (ชั้นพื้นอยู่ที่ body)
       aria-hidden เพราะเป็นงานตกแต่งล้วน ไม่มีความหมายให้โปรแกรมอ่านหน้าจออ่าน --}}
  <div class="login-glow" aria-hidden="true"></div>
  <div class="login-col">
    <main class="login-card">

      {{-- 1. โลโก้ + ชื่อระบบ --}}
      <div class="login-brand">
        <img class="login-logo" src="{{ asset('assets/images/logo-farm.png') }}"
             alt="The Farm Concept" width="168" height="55">
        <p class="login-tagline">ระบบติดตามและประเมินผลการเปลี่ยนแปลงสุขภาพ</p>
      </div>

      <form class="login-form" id="login-form" method="POST" action="{{ route('login.attempt') }}" novalidate>
        @csrf

        {{-- 2. ชื่อผู้ใช้งาน --}}
        <div class="login-field">
          <label class="login-label" for="login-username">ชื่อผู้ใช้งาน</label>
          <input class="login-input" id="login-username" name="username" type="text"
                 value="{{ old('username') }}"
                 placeholder="เช่น admin01" autocomplete="username" autofocus>
        </div>

        {{-- 3. รหัสผ่าน --}}
        <div class="login-field">
          <label class="login-label" for="login-password">รหัสผ่าน</label>
          <div class="login-input-wrap">
            <input class="login-input" id="login-password" name="password" type="password"
                   placeholder="••••••••" autocomplete="current-password">
            {{-- type="button" บังคับ ไม่งั้นการกดจะไปส่งฟอร์มแทนการสลับการมองเห็น --}}
            <button type="button" class="login-reveal" id="login-reveal"
                    aria-controls="login-password" aria-label="แสดงรหัสผ่าน">แสดง</button>
          </div>
        </div>

        {{-- 4. ข้อความผิดพลาดจากเซิร์ฟเวอร์ (ไม่ใช่การตรวจฝั่งเบราว์เซอร์) --}}
        <div class="login-error" id="login-error" role="alert" @if (! $errors->any()) hidden @endif>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 8v5M12 16h.01"/></svg>
          <span>{{ $errors->first() }}</span>
        </div>

        {{-- 5. จำการเข้าสู่ระบบ — ต่ออายุ session ผ่าน Auth::attempt($credentials, remember)
               ไม่เก็บรหัสผ่านไว้ที่ใดทั้งฝั่งเบราว์เซอร์และฐานข้อมูล --}}
        <label class="login-remember" for="login-remember">
          <input type="checkbox" id="login-remember" name="remember" value="1" @checked(old('remember'))>
          <span>จำการเข้าสู่ระบบไว้</span>
        </label>

        {{-- 6. ปุ่มเข้าสู่ระบบ --}}
        <button type="submit" class="login-submit" id="login-submit" disabled>เข้าสู่ระบบ</button>
      </form>

      {{-- 7. ทางออกรอง — ดูกิจกรรมได้โดยไม่ต้องล็อกอิน · ไม่มีลิงก์ลืมรหัสผ่านตามสเปก --}}
      <div class="login-card-foot">
        <a href="{{ url('/activities.html') }}">ดูกิจกรรมทั้งหมด โดยไม่ต้องเข้าสู่ระบบ</a>
      </div>
    </main>

    {{-- หน้าส่งงานให้ตรวจเป็นทางเข้าคนละกลุ่มกับการเข้าสู่ระบบ — ผู้ตรวจไม่มีบัญชีและไม่ต้องมี
         จึงวางไว้นอกการ์ด ไม่ปนกับฟอร์มล็อกอิน --}}
    <a class="login-review-link" href="{{ route('review.index') }}">ตรวจงานและส่งคอมเมนต์</a>

    <p class="login-footer">
      เวอร์ชัน 1.0.0 · <a href="#">ติดต่อผู้ดูแลระบบ</a> · <a class="is-accent" href="#">นโยบายความเป็นส่วนตัว</a>
    </p>
  </div>

<script>
(function () {
  var form     = document.getElementById('login-form');
  var username = document.getElementById('login-username');
  var password = document.getElementById('login-password');
  var reveal   = document.getElementById('login-reveal');
  var submit   = document.getElementById('login-submit');
  var errorBox = document.getElementById('login-error');

  /* ปุ่มเข้าสู่ระบบกดได้ต่อเมื่อกรอกครบทั้งสองช่อง — ทั้งสองช่องจำเป็นตามสเปก
     ตรวจซ้ำที่เซิร์ฟเวอร์อีกชั้นอยู่แล้ว (LoginRequest) ตรงนี้แค่บอกผู้ใช้ก่อนกด */
  function syncSubmitState() {
    submit.disabled = !username.value.trim() || !password.value.trim();
  }

  username.addEventListener('input', syncSubmitState);
  password.addEventListener('input', syncSubmitState);
  syncSubmitState();

  /* สลับ type ระหว่าง password / text แล้วคืนโฟกัสให้ช่องเดิม
     ถ้าไม่คืนโฟกัส คนที่ใช้คีย์บอร์ดจะต้อง Shift+Tab กลับมาเองทุกครั้ง */
  reveal.addEventListener('click', function () {
    var showing = password.type === 'text';

    password.type = showing ? 'password' : 'text';
    reveal.textContent = showing ? 'แสดง' : 'ซ่อน';
    reveal.setAttribute('aria-label', showing ? 'แสดงรหัสผ่าน' : 'ซ่อนรหัสผ่าน');

    /* คืนตำแหน่งเคอร์เซอร์ไปท้ายข้อความ ไม่ใช่ต้นข้อความแบบที่ focus() เปล่า ๆ ทำ */
    var end = password.value.length;
    password.focus();
    password.setSelectionRange(end, end);
  });

  /* ส่งฟอร์มจริงไปเซิร์ฟเวอร์ — การตรวจรหัสผ่านและการนับครั้งที่กรอกผิด
     ทำที่ฝั่งเซิร์ฟเวอร์ทั้งหมด ตรงนี้ทำแค่ให้ปุ่มเข้าสถานะกำลังทำงานและกดซ้ำไม่ได้ */
  form.addEventListener('submit', function () {
    if (submit.disabled) return;

    submit.disabled = true;
    submit.classList.add('is-loading');
    submit.innerHTML = '<span class="login-spinner" aria-hidden="true"></span>กำลังเข้าสู่ระบบ…';
  });

  /* มีข้อความผิดพลาดค้างอยู่ = เพิ่งกรอกผิดมา ย้ายโฟกัสไปช่องรหัสผ่านให้แก้ได้ทันที */
  if (!errorBox.hidden) {
    password.focus();
  }
})();
</script>
</body>
</html>
