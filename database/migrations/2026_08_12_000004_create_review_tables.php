<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 | ระบบส่งงานให้ลูกค้าตรวจ
 |
 | หน้าตรวจงานเปิดได้โดยไม่ต้องเข้าสู่ระบบ ลูกค้าจึงคอมเมนต์ได้เลยไม่ต้องมีบัญชี
 | สามตารางคือ รอบการส่ง → หน้าจอที่ส่งตรวจ → คอมเมนต์ของแต่ละหน้าจอ
 |
 | menu_key ผูกกับ config/menu.php เพื่อให้คอลัมน์ "เมนู" แสดงลำดับชั้นเดียวกับเมนูจริงของระบบ
 | ไม่เก็บชื่อเมนูซ้ำลงมา เปลี่ยนชื่อเมนูแล้วหน้านี้เปลี่ยนตามทันที
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rev_review_rounds', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('round_no');
            $table->string('sender', 120)->default('TheFarmConcept');
            $table->date('sent_at')->nullable();
            $table->date('due_at')->nullable();

            /* รอบที่เปิดอยู่มีได้รอบเดียว — หน้าตรวจงานหยิบรอบนั้นมาแสดง */
            $table->boolean('is_open')->default(true);
            $table->timestamps();
        });

        Schema::create('rev_review_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('round_id')->constrained('rev_review_rounds')->cascadeOnDelete();

            $table->string('menu_key', 60);
            $table->string('screen', 160);
            $table->string('note', 255)->nullable();

            /* ลิงก์ที่ปุ่ม "เปิดดู" พาไป — ว่างได้ถ้าหน้านั้นยังไม่มีของให้ดู */
            $table->string('url', 255)->nullable();

            $table->string('status', 30)->default('รอพัฒนา');
            $table->date('due_date')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['round_id', 'sort_order']);
        });

        Schema::create('rev_review_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained('rev_review_items')->cascadeOnDelete();

            $table->string('author_name', 120);

            /* customer = ฝั่งลูกค้า · team = ฝั่งทีมพัฒนา — ใช้แยกสีและป้ายในหน้าจอ */
            $table->string('author_side', 20)->default('customer');
            $table->text('body');

            /* ทีมพัฒนาติ๊กว่าแก้แล้ว เพื่อให้ลูกค้าเห็นว่าคอมเมนต์ไหนถูกจัดการไปแล้ว */
            $table->boolean('is_resolved')->default(false);
            $table->timestamps();

            $table->index(['item_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rev_review_comments');
        Schema::dropIfExists('rev_review_items');
        Schema::dropIfExists('rev_review_rounds');
    }
};
