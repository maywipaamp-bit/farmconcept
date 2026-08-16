{{-- คำชี้แจงการใช้ข้อมูล — สามบรรทัดแรกเห็นเสมอ ส่วนคำชี้แจงเต็มกดเปิดอ่านเอง
     ใช้ร่วมกันระหว่างหน้าเข้าสู่ระบบกับหน้าหลัก จะได้ไม่ต้องแก้ข้อความสองที่ --}}
<div class="tr-assurance">
    <ul class="tr-trust-list">
        @foreach($assurances as $line)
            <li><span class="tr-trust-check" aria-hidden="true"></span>{{ $line }}</li>
        @endforeach
    </ul>

    <details class="tr-disclosure" @if($open ?? false) open @endif>
        <summary>
            <span class="tr-disclosure-show">อ่านคำชี้แจง</span>
            <span class="tr-disclosure-hide">ซ่อนคำชี้แจง</span>
        </summary>

        @isset($project)
            <p class="tr-about-project">{{ $project }}</p>
        @endisset

        <ol class="tr-disclosure-list">
            @foreach($disclosures as $paragraph)
                <li>{{ $paragraph }}</li>
            @endforeach
        </ol>
    </details>
</div>
