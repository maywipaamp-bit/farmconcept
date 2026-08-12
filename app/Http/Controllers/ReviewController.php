<?php

namespace App\Http\Controllers;

use App\Models\ReviewComment;
use App\Models\ReviewItem;
use App\Models\ReviewRound;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

/**
 * หน้าส่งงานให้ลูกค้าตรวจ
 *
 * เปิดได้โดยไม่ต้องเข้าสู่ระบบ ลูกค้าจึงคอมเมนต์ได้ทันทีโดยไม่ต้องมีบัญชี
 * ส่วนที่แก้ข้อมูลงาน (สถานะ · วันครบกำหนด) ทำได้เฉพาะคนที่ล็อกอินแล้ว
 * เพราะเป็นข้อมูลของทีมพัฒนา ไม่ใช่ของผู้ตรวจ
 */
class ReviewController extends Controller
{
    public function index(): View
    {
        $round = ReviewRound::current();

        return view('review.index', [
            'round' => $round,
            'items' => $round ? $this->rows($round) : collect(),
            'statuses' => array_keys(ReviewItem::STATUSES),
            'canManage' => auth()->check(),
            'sentLabel' => $this->thaiDate($round?->sent_at),
            'dueLabel' => $this->thaiDate($round?->due_at),
        ]);
    }

    /** "12 ส.ค. 2569" — ฝั่งหน้าจอมี formatThaiDate อยู่แล้ว แต่หัวหน้าถูก render จากเซิร์ฟเวอร์ */
    private function thaiDate(?Carbon $date): ?string
    {
        if (! $date) {
            return null;
        }

        $months = ['', 'ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.',
            'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];

        return $date->day.' '.$months[$date->month].' '.($date->year + 543);
    }

    /**
     * คอมเมนต์ของหน้าจอหนึ่ง — ใช้ตอนเปิดแผงด้านข้าง
     *
     * ดึงตอนกดเปิดแทนที่จะส่งมาพร้อมหน้า เพราะคอมเมนต์ยาวได้ไม่จำกัด
     * และผู้ตรวจเปิดดูทีละหน้าจออยู่แล้ว
     */
    public function comments(ReviewItem $item): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $item->comments->map(fn (ReviewComment $c) => $this->toCommentRow($c))->values(),
        ]);
    }

    public function storeComment(Request $request, ReviewItem $item): JsonResponse
    {
        /* ผู้ที่ล็อกอินแล้วถือเป็นฝั่งทีมงาน ชื่อจึงมาจากบัญชีโดยตรง
           ผู้ตรวจที่ไม่ได้ล็อกอินต้องพิมพ์ชื่อเอง จะได้รู้ว่าใครขอให้แก้ */
        $data = $request->validate(
            [
                'name' => [auth()->check() ? 'nullable' : 'required', 'string', 'max:120'],
                'body' => ['required', 'string', 'max:2000'],
            ],
            [],
            ['name' => 'ชื่อผู้คอมเมนต์', 'body' => 'ข้อความ']
        );

        $comment = $item->comments()->create([
            'author_name' => auth()->check() ? auth()->user()->name : trim($data['name']),
            'author_side' => auth()->check() ? 'team' : 'customer',
            'body' => trim($data['body']),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'ส่งคอมเมนต์แล้ว',
            'data' => $this->toCommentRow($comment),
            'item' => $this->toRow($item->fresh()->load('comments')),
        ]);
    }

    /**
     * แก้สถานะและวันครบกำหนด
     *
     * เปิดให้ทุกคนที่เข้าถึงลิงก์นี้แก้ได้ ตามที่เจ้าของระบบกำหนด — ทั้งทีมงานและผู้ตรวจ
     * ใช้ร่วมกันระหว่างรอบส่งงาน จึงไม่ต้องสลับบัญชีไปมา
     *
     * ค่าที่รับยังถูกตรวจตามปกติ: สถานะต้องเป็นค่าที่มีอยู่จริง และวันที่ต้องเป็นวันที่
     */
    public function updateItem(Request $request, ReviewItem $item): JsonResponse
    {
        $data = $request->validate(
            [
                'status' => ['required', Rule::in(array_keys(ReviewItem::STATUSES))],
                'dueDate' => ['nullable', 'date'],
            ],
            [],
            ['status' => 'สถานะ', 'dueDate' => 'วันครบกำหนด']
        );

        $item->update([
            'status' => $data['status'],
            'due_date' => $data['dueDate'] ?: null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'บันทึกแล้ว',
            'data' => $this->toRow($item->load('comments')),
        ]);
    }

    /** @return Collection<int, array<string, mixed>> */
    private function rows(ReviewRound $round)
    {
        return $round->items()->with('comments')->get()->map(fn (ReviewItem $i) => $this->toRow($i));
    }

    /** @return array<string, mixed> */
    private function toRow(ReviewItem $item): array
    {
        $last = $item->comments->last();
        $menu = $this->menu($item->menu_key);

        return [
            'id' => $item->id,
            'menuLabel' => $menu['label'],

            /* 1 = เมนูหลัก · 2 = เมนูย่อย — หน้าจอใช้จัดย่อหน้าให้เห็นลำดับชั้น */
            'level' => $menu['level'],

            /* หมวดที่มีเมนูย่อยไม่ใช่หน้าจอ จึงไม่มีสถานะ วันครบกำหนด หรือปุ่มเปิดดู */
            'isGroup' => $menu['isGroup'],

            'screen' => $item->screen,
            'note' => $item->note,
            'url' => $item->url,
            'status' => $item->status,
            'open' => $item->isOpenForReview(),
            'dueDate' => $item->due_date?->toDateString(),
            'commentCount' => $item->comments->count(),

            /* ใช้ตัดสินว่ามีคอมเมนต์ใหม่ที่ผู้ตรวจยังไม่ได้เปิดอ่านหรือยัง
               ฝั่งหน้าจอเทียบกับเลขที่จำไว้ในเครื่อง เพราะหน้านี้ไม่มีระบบล็อกอิน */
            'lastCommentId' => $last?->id,
            'lastComment' => $last ? [
                'author' => $last->author_name,
                'side' => $last->author_side,
                'body' => $last->body,
                'at' => $last->created_at?->toIso8601String(),
                'date' => $last->created_at?->toDateString(),
                'time' => $last->created_at?->format('H:i'),
            ] : null,
        ];
    }

    /** @return array<string, mixed> */
    private function toCommentRow(ReviewComment $comment): array
    {
        return [
            'id' => $comment->id,
            'author' => $comment->author_name,
            'side' => $comment->author_side,
            'body' => $comment->body,
            'resolved' => $comment->is_resolved,
            'date' => $comment->created_at?->toDateString(),
            'time' => $comment->created_at?->format('H:i'),
        ];
    }

    /**
     * ชื่อและระดับของเมนูตาม config/menu.php
     *
     * อ่านจาก config ทุกครั้ง ไม่เก็บชื่อซ้ำไว้ในตาราง
     * เปลี่ยนชื่อเมนูแล้วหน้านี้เปลี่ยนตามทันที
     *
     * @return array{label: string, level: int, isGroup: bool}
     */
    private function menu(string $key): array
    {
        foreach (config('menu.items', []) as $item) {
            $children = $item['children'] ?? [];

            if (($item['key'] ?? null) === $key) {
                return ['label' => $item['label'], 'level' => 1, 'isGroup' => (bool) $children];
            }

            foreach ($children as $child) {
                if (($child['key'] ?? null) === $key) {
                    return ['label' => $child['label'], 'level' => 2, 'isGroup' => false];
                }
            }
        }

        /* คีย์ที่ไม่มีในเมนูแล้ว (เมนูถูกลบทีหลัง) แสดงคีย์ไปตรง ๆ ดีกว่าเว้นว่าง */
        return ['label' => $key, 'level' => 1, 'isGroup' => false];
    }
}
