@extends('layouts.admin')

@section('title', 'ผลตอบรายคน')

@section('content')
  <nav class="breadcrumb" aria-label="Breadcrumb">
    <a href="{{ route('admin.dashboard') }}">แดชบอร์ด</a> <span>/</span>
    <span class="is-current">ผลตอบรายคน</span>
  </nav>

  <div class="rl-head">
    <div>
      <h1 class="rl-title">ผลตอบรายคน</h1>
      <p class="pr-subtitle">เลือกคนเพื่อดูคำตอบทุกรอบเทียบกัน ว่าแต่ละข้อเปลี่ยนไปทางไหน</p>
    </div>
  </div>

  <div class="rl-toolbar">
    <span class="rl-count">ตอบแล้ว {{ $people->count() }} คน</span>
    <div class="rl-toolbar-right">
      <input type="text" class="input pr-search" id="pr-q"
             placeholder="ค้นหารหัสบุคคล" autocomplete="off">
    </div>
  </div>

  <div class="card rl-card">
    <div class="rl-scroll">
      <div class="rl-table pr-people">
        <div class="rl-row rl-th">
          <div>#</div>
          <div>รหัสบุคคล</div>
          <div>แบบประเมิน</div>
          <div>ตอบแล้ว</div>
          <div>รอบล่าสุด</div>
          <div></div>
        </div>
        <div id="pr-rows">
          @forelse($people as $i => $person)
            {{-- แสดงเฉพาะรหัสบุคคล ไม่แสดงชื่อ (คำสั่งทีม) — กลุ่มตัวอย่างเป็นข้อมูลนิรนาม
                 ค้นหากรองฝั่งเบราว์เซอร์จาก data-search — รายชื่อทั้งโครงการมีหลักร้อย ส่งมาทั้งหมดได้ --}}
            <div class="rl-row" data-search="{{ mb_strtolower($person['pid']) }}">
              <div class="rl-no">{{ $i + 1 }}</div>
              <div class="rl-person">
                <span class="rl-name">{{ $person['pid'] }}</span>
              </div>
              <div class="rl-cell">{{ $person['forms'] }}</div>
              <div class="rl-cell"><span class="num">{{ $person['rounds'] }}</span> รอบ</div>
              <div class="rl-cell">{{ $person['latestRound'] }}</div>
              <div class="text-right">
                <a class="btn btn-outline btn-sm"
                   href="{{ route('admin.evaluations.person-results.show', $person['id']) }}">ดูทุกรอบ</a>
              </div>
            </div>
          @empty
            <div class="fb-empty"><span class="fb-empty-title">ยังไม่มีใครส่งคำตอบเข้ามา</span></div>
          @endforelse
        </div>
        <div class="fb-empty" id="pr-empty" hidden>
          <span class="fb-empty-title">ไม่พบคนที่ตรงกับคำค้นหา</span>
        </div>
      </div>
    </div>
  </div>
@endsection

@push('page-script')
<script>
(function () {
  var q = document.getElementById('pr-q');
  var rows = Array.prototype.slice.call(document.querySelectorAll('#pr-rows [data-search]'));

  q.addEventListener('input', function () {
    var term = q.value.trim().toLowerCase();
    var visible = 0;

    rows.forEach(function (row) {
      var hit = !term || row.getAttribute('data-search').indexOf(term) > -1;
      row.hidden = !hit;
      if (hit) visible++;
    });

    document.getElementById('pr-empty').hidden = visible > 0 || rows.length === 0;
  });
})();
</script>
@endpush
