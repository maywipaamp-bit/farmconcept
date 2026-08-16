{{-- ป้ายกรอกแทน — ต้องค้างอยู่ทุกหน้าของรอบนั้นจนกว่าจะเลิก
     ถ้าหายไปกลางทาง ผู้กรอกจะเผลอคิดว่ากำลังตอบของตัวเองแล้วคำตอบลงผิดคน --}}
<div class="tr-proxy-banner">
    <span>กำลังกรอกแทน {{ $proxyFor->name }}</span>
    <form method="POST" action="{{ route('public.tracking-round-qr.proxy.stop') }}">
        @csrf
        <button type="submit" class="tr-link-button">เลิกกรอกแทน</button>
    </form>
</div>
