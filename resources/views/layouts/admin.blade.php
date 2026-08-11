<!DOCTYPE html>
<html lang="th" class="is-preload">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>@yield('title') | TheFarmConcept</title>
<link rel="icon" type="image/png" href="{{ asset('assets/images/favicon.png') }}">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
{{-- โหลดฟอนต์ตรงนี้ ไม่รอ @import ใน standard/tokens.css
     @import ถูกเจอหลัง tokens.css ดาวน์โหลดเสร็จ กลายเป็นคำขอต่อแถวสองชั้น
     ทำให้เห็นฟอนต์สำรองแวบหนึ่งก่อนของจริงจะมา (อาการ "ฟอนต์เพี้ยน" ตอนเปิดหน้า)
     URL ต้องตรงกับใน tokens.css เป๊ะ เบราว์เซอร์จะได้ยุบเป็นคำขอเดียว --}}
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@400;500;600&display=swap">
{{--
    ลำดับการโหลด CSS ต้องตรงกับหน้าเดิมทุกบรรทัด — standard/* มาก่อน แล้วค่อย CSS เดิมของ repo
    ตามที่ CLAUDE.md อธิบายไว้ว่าตั้งใจให้ CSS เดิม override มาตรฐานใหม่ระหว่างการย้ายทีละหน้า
--}}
{{-- CSS รวมเป็นก้อนเดียวโดย Vite (ดู resources/css/app.css) ลำดับการรวมอยู่ในไฟล์ entry ห้ามสลับ

     JS ยัง "ไม่" รวม bundle — เมื่อรวมเป็นโมดูลเดียว สคริปต์ที่พังตัวเดียวจะหยุดตัวที่เหลือทั้งหมด
     ต่างจากเดิมที่เป็นสคริปต์แยก 11 ไฟล์ ซึ่งพังตัวหนึ่งตัวอื่นยังทำงานต่อได้
     ทำให้หน้าขาวทั้งหน้าเพราะ TFC.renderPageHeader ไม่ถูกประกาศ
     จะกลับมารวมอีกครั้งเมื่อหาและแก้ตัวที่พังได้แล้ว — resources/js/app.js ยังอยู่ครบ --}}
@vite(['resources/css/app.css'])
@stack('styles')
</head>
<body>

<div class="app-shell">
{{-- อ่านสถานะย่อแผงก่อนเบราว์เซอร์วาดเฟรมแรก ไม่งั้นแผงจะกระพริบจากกว้างเป็นหายทุกครั้งที่เปลี่ยนหน้า --}}
<script>(function(){try{if(localStorage.getItem('tfc-subnav-collapsed')==='1'){document.currentScript.parentElement.classList.add('is-subnav-collapsed');}}catch(e){}})();</script>

  {{-- เมนูสองชั้น (แถบไอคอน + แผงเมนูย่อย) สร้างจาก window.TFC_MENU ที่กรองสิทธิ์มาแล้ว
       data-nav-base เป็น "/" เพราะ Laravel เสิร์ฟจาก docroot เดียว ไม่ใช่ path สัมพัทธ์แบบหน้า HTML เดิม --}}
  <div id="sidebar-shell" data-nav-base="/"></div>

  {{-- โหลดตรงนี้ ไม่ใช่ท้าย body — ตอนนี้ mount ของเมนูมีแล้วแต่เบราว์เซอร์ยังไม่วาด
       เมนูจึงขึ้นครบตั้งแต่เฟรมแรก ถ้าย้ายไปท้าย body เมนูจะกระพริบว่างแล้วค่อยเต็มทุกครั้ง --}}
  <script src="@assetv('assets/js/mock-data.js')"></script>
  @include('partials.client-context')
  <script src="@assetv('assets/js/sidebar-render.js')"></script>

  <div class="app-main">
    {{-- บางหน้าต้องการคลาสเพิ่มบน main เช่นฟอร์มที่ต้องเว้นที่ให้แถบปุ่มล่าง --}}
    <main class="content @yield('main-class')">
      @yield('content')
    </main>

    {{-- ของที่ต้องอยู่นอก .content แต่ยังอยู่ใน .app-main เช่นแถบปุ่มล่างแบบติดขอบจอ --}}
    @yield('after-content')
  </div>
</div>

@yield('modals')

<script src="@assetv('assets/js/data-service.js')"></script>
<script src="@assetv('assets/js/navigation.js')"></script>
<script src="@assetv('assets/js/modal.js')"></script>
<script src="@assetv('assets/js/profile-modal.js')"></script>
<script src="@assetv('assets/js/action-menu.js')"></script>
<script src="@assetv('assets/js/index-layout.js')"></script>
<script src="@assetv('assets/js/toast.js')"></script>
<script src="@assetv('assets/js/form.js')"></script>
<script src="@assetv('assets/js/smart-select.js')"></script>
<script src="@assetv('assets/js/search-popover.js')"></script>
@stack('scripts')
<script src="@assetv('assets/js/app.js')"></script>

{{-- สคริปต์ของหน้าต้อง "ทำงานหลัง" bundle เพราะเรียกใช้ TFC.renderPageHeader / renderPagination
     ที่มาจาก bundle — ประกาศเป็น type="module" เพื่อให้ถูก defer ไปทำงานตามหลัง
     ถ้าเขียนเป็นสคริปต์ธรรมดาจะทำงานตอน parse แล้วหาฟังก์ชันเหล่านั้นไม่เจอ --}}
@stack('page-script')
</body>
</html>
