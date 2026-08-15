<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\EvaluationRequest;
use App\Models\Form;
use App\Services\EvaluationService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EvaluationController extends Controller
{
    public function index(): View
    {
        return view('admin.evaluations.index', [
            'forms' => $this->formsForList(),
        ]);
    }

    public function data(): JsonResponse
    {
        return response()->json(['forms' => $this->formsForList()]);
    }

    public function create(): View
    {
        return view('admin.evaluations.form', [
            'form' => null,
            'formPayload' => null,
        ]);
    }

    public function edit(Form $form): View
    {
        $form->load(['fields', 'questions.options']);

        return view('admin.evaluations.form', [
            'form' => $form,
            'formPayload' => $this->formPayload($form),
        ]);
    }

    /** ลิงก์ต้นแบบเดิมยังเปิดได้ แต่ URL หลักของระบบไม่มี .html */
    public function legacyCreateRedirect(Request $request): RedirectResponse
    {
        $code = trim((string) $request->query('id', ''));

        return $code !== ''
            ? redirect()->route('admin.evaluations.edit', ['form' => $code], 301)
            : redirect()->route('admin.evaluations.create', status: 301);
    }

    public function show(Form $form): JsonResponse
    {
        return response()->json(['form' => $this->formPayload($form->load(['fields', 'questions.options']))]);
    }

    public function store(EvaluationRequest $request, EvaluationService $service): JsonResponse
    {
        $form = $service->create($request->validated(), $request->user());

        return response()->json([
            'message' => 'สร้างแบบประเมิน “'.$form->name.'” เรียบร้อย',
            'form' => $this->formPayload($form),
            'redirect' => route('admin.evaluations.index'),
        ], 201);
    }

    public function update(EvaluationRequest $request, Form $form, EvaluationService $service): JsonResponse
    {
        $form = $service->update($form, $request->validated(), $request->user());

        return response()->json([
            'message' => 'บันทึกแบบประเมิน “'.$form->name.'” เรียบร้อย',
            'form' => $this->formPayload($form),
            'redirect' => route('admin.evaluations.index'),
        ]);
    }

    public function duplicate(Request $request, Form $form, EvaluationService $service): JsonResponse
    {
        $copy = $service->duplicate($form, $request->user());

        return response()->json([
            'message' => 'ทำสำเนาแบบประเมินเรียบร้อย',
            'form' => $this->formPayload($copy),
        ], 201);
    }

    public function changeStatus(Request $request, Form $form, EvaluationService $service): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(Form::STATUSES)],
        ]);

        $service->changeStatus($form, $validated['status'], $request->user());

        return response()->json([
            'message' => 'เปลี่ยนสถานะแบบประเมินเรียบร้อย',
            'form' => $this->listPayload($form->fresh()->loadCount(['questions', 'satisfactionResponses', 'surveyResponses'])),
        ]);
    }

    public function destroy(Form $form, EvaluationService $service): JsonResponse
    {
        $service->destroy($form);

        return response()->json(['message' => 'ลบแบบประเมินเรียบร้อย']);
    }

    /** @return array<string, mixed> */
    private function listPayload(Form $form): array
    {
        $answers = match ($form->type) {
            Form::TYPE_POST_ACTIVITY => (int) ($form->satisfaction_responses_count ?? 0),
            Form::TYPE_HEALTH_FOLLOW_UP => (int) ($form->survey_responses_count ?? 0),
            default => 0,
        };

        return [
            'id' => $form->code,
            'code' => $form->code,
            'name' => $form->name,
            'type' => $form->type,
            'stage' => $this->typeLabel($form->type),
            'status_key' => $form->status,
            'status' => $this->statusLabel($form->status),
            'q' => (int) ($form->questions_count ?? $form->questions->where('question_type', '!=', 'section')->count()),
            'answers' => $answers,
            'updated' => $form->updated_at?->format('d/m/Y H:i') ?? '-',
            'edit_url' => route('admin.evaluations.edit', $form),
            'api_url' => route('admin.evaluations.show', $form),
            'questions' => $form->questions
                ->where('question_type', '!=', 'section')
                ->map(fn ($question) => [
                    'title' => $question->text,
                    'kind' => $question->question_type,
                    'multi' => in_array($question->question_type, ['multi', 'chips'], true),
                    'choices' => $question->options->pluck('label')->values(),
                    'placeholder' => $question->question_type === 'text' ? 'พิมพ์คำตอบ…' : null,
                ])->values(),
        ];
    }

    /** @return array<string, mixed> */
    private function formPayload(Form $form): array
    {
        $form->loadMissing(['fields', 'questions.options']);

        return [
            'id' => $form->code,
            'code' => $form->code,
            'name' => $form->name,
            'description' => $form->description,
            'type' => $form->type,
            'status' => $form->status,
            'has_responses' => $form->hasResponses(),
            'registration_mode' => $form->registration_mode,
            'max_participants' => $form->max_participants,
            'fields' => $form->fields->map(fn ($field) => [
                'key' => $field->field_key,
                'is_enabled' => $field->is_enabled,
                'is_required' => $field->is_required,
                'sort_order' => $field->sort_order,
            ])->values(),
            'questions' => $form->questions->map(fn ($question) => [
                'id' => $question->id,
                'type' => $question->question_type,
                'text' => $question->text,
                'dimension' => $question->dimension,
                'is_required' => $question->is_required,
                'sort_order' => $question->sort_order,
                'options' => $question->options->map(fn ($option) => [
                    'label' => $option->label,
                    'is_other' => $option->is_other,
                ])->values(),
            ])->values(),
        ];
    }

    /** @return \Illuminate\Support\Collection<int, array<string, mixed>> */
    private function formsForList(): \Illuminate\Support\Collection
    {
        return Form::query()
            ->with(['questions.options'])
            ->withCount(['questions', 'satisfactionResponses', 'surveyResponses'])
            ->orderByDesc('id')
            ->get()
            ->map(fn (Form $form) => $this->listPayload($form))
            ->values();
    }

    private function typeLabel(?string $type): string
    {
        return match ($type) {
            Form::TYPE_REGISTRATION => 'ตอนลงทะเบียน',
            Form::TYPE_POST_ACTIVITY => 'หลังกิจกรรม',
            Form::TYPE_HEALTH_FOLLOW_UP => 'ติดตามสุขภาพ',
            default => 'ไม่ระบุ',
        };
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            Form::STATUS_ACTIVE => 'ใช้งานอยู่',
            Form::STATUS_INACTIVE => 'ปิดใช้งาน',
            default => 'ฉบับร่าง',
        };
    }
}
