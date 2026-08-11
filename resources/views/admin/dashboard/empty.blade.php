{{--
    Empty State ของแผงกราฟ — ใช้เมื่อ "ยังไม่มีข้อมูลในระบบ" ไม่ใช่เมื่อกำลังโหลด
    บอกสาเหตุและบอกว่าต้องทำอะไรต่อ ไม่ใช่แค่ "ไม่มีข้อมูล" ลอย ๆ
    อยู่ในตำแหน่งเดิมของกราฟและมีความสูงขั้นต่ำ หน้าจึงไม่ยุบเมื่อบางแผงว่าง
--}}
<div class="dbo-empty">
  @include('admin.dashboard.icon', ['name' => 'chart', 'size' => 26])
  <span class="dbo-empty-title">{{ $title }}</span>
  @isset($note)
    <span class="dbo-empty-note">{{ $note }}</span>
  @endisset
</div>
