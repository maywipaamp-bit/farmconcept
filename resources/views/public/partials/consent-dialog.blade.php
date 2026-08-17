{{-- popup อ่านเอกสารความยินยอม — ใบเดียวใช้ได้ทุกฉบับ JS เปลี่ยนหัวและเนื้อหาตามลิงก์ที่กด
     ใช้ร่วมกันระหว่างแบบประเมินหลังกิจกรรมกับแบบประเมินติดตาม จะได้ไม่มีสองสำนวน

     $consentDocs = ['CNS-001' => ['title' => …, 'version' => …, 'content' => …], …] --}}
@if(!empty($consentDocs))
    <dialog class="tr-dialog" id="tr-consent-dialog">
        <div class="tr-dialog-body">
            <h2 class="tr-dialog-title" id="tr-consent-title"></h2>
            <p class="tr-dialog-text" id="tr-consent-version"></p>

            <div class="tr-consent-doc" id="tr-consent-content"></div>

            <div class="tr-dialog-actions">
                <button type="button" class="tr-primary-button" id="tr-consent-close">ปิด</button>
            </div>
        </div>
    </dialog>

    @push('page-script')
        <script>
        (function () {
            /* เนื้อหามาจาก master data ฝั่งแอดมิน — ใส่เป็น textContent ไม่ใช่ innerHTML
               เพราะเป็นข้อความที่คนพิมพ์เข้ามา ไม่ใช่มาร์กอัปที่ระบบสร้าง */
            var docs = @json($consentDocs);
            var dialog = document.getElementById('tr-consent-dialog');
            var title = document.getElementById('tr-consent-title');
            var version = document.getElementById('tr-consent-version');
            var content = document.getElementById('tr-consent-content');

            document.addEventListener('click', function (event) {
                var link = event.target.closest('[data-consent-doc]');
                if (!link) return;

                event.preventDefault();
                /* ลิงก์อยู่ใน <label> ของช่องติ๊ก — กันไม่ให้การกดอ่านไปติ๊กยอมรับให้เอง
                   ยินยอมต้องเป็นการกดที่ตั้งใจ ไม่ใช่ผลข้างเคียงของการกดอ่าน */
                event.stopPropagation();

                var doc = docs[link.dataset.consentDoc];
                if (!doc) return;

                title.textContent = doc.title;
                version.textContent = doc.version ? 'ฉบับ ' + doc.version : '';
                version.hidden = !doc.version;
                content.textContent = doc.content || 'ยังไม่มีเนื้อหาในเอกสารฉบับนี้';
                dialog.showModal();
                content.scrollTop = 0;
            });

            document.getElementById('tr-consent-close').addEventListener('click', function () {
                dialog.close();
            });

            /* กดพื้นหลังนอกกล่องแล้วปิด — ปุ่มปิดอยู่ล่างสุดของเนื้อหาที่ยาว ต้องมีทางออกที่มือถึงเสมอ */
            dialog.addEventListener('click', function (event) {
                if (event.target === dialog) dialog.close();
            });
        })();
        </script>
    @endpush
@endif
