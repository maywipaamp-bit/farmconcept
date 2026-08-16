<?php

namespace App\Services;

use App\Http\Requests\EvaluationRequest;
use App\Models\Form;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class EvaluationService
{
    public function create(array $data, User $user): Form
    {
        return DB::transaction(function () use ($data, $user): Form {
            $form = Form::create([
                'code' => 'TMP-'.Str::upper(Str::random(12)),
                ...$this->formAttributes($data, $user),
                'created_by' => $user->id,
            ]);

            $form->update(['code' => sprintf('EVL-%s-%04d', now()->format('y'), $form->id)]);
            $this->replaceStructure($form, $data);

            return $form->load(['fields', 'questions.options']);
        });
    }

    public function update(Form $form, array $data, User $user): Form
    {
        if ($form->hasResponses()) {
            throw ValidationException::withMessages([
                'form' => 'แบบประเมินนี้มีคำตอบแล้ว จึงแก้ไขโครงสร้างเดิมไม่ได้ กรุณาทำสำเนาเป็นชุดใหม่',
            ]);
        }

        return DB::transaction(function () use ($form, $data, $user): Form {
            $attributes = $this->formAttributes($data, $user);
            if ($data['status'] === Form::STATUS_ACTIVE && $form->published_at !== null) {
                $attributes['published_at'] = $form->published_at;
            }
            $form->update($attributes);
            $this->replaceStructure($form, $data);

            return $form->load(['fields', 'questions.options']);
        });
    }

    public function duplicate(Form $source, User $user): Form
    {
        return DB::transaction(function () use ($source, $user): Form {
            $source->load(['fields', 'questions.options']);
            $copy = Form::create([
                ...$source->only(['description', 'type', 'is_anonymous', 'registration_mode', 'max_participants']),
                'code' => 'TMP-'.Str::upper(Str::random(12)),
                'name' => $source->name.' (สำเนา)',
                'status' => Form::STATUS_DRAFT,
                'published_at' => null,
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);
            $copy->update(['code' => sprintf('EVL-%s-%04d', now()->format('y'), $copy->id)]);

            foreach ($source->fields as $field) {
                $copy->fields()->create($field->only(['field_key', 'is_enabled', 'is_required', 'sort_order']));
            }
            foreach ($source->questions as $question) {
                $newQuestion = $copy->questions()->create($question->only(['sort_order', 'question_type', 'text', 'dimension', 'is_required']));
                foreach ($question->options as $option) {
                    $newQuestion->options()->create($option->only(['sort_order', 'label', 'value', 'is_other']));
                }
            }

            return $copy->load(['fields', 'questions.options']);
        });
    }

    public function changeStatus(Form $form, string $status, User $user): Form
    {
        if ($status === Form::STATUS_DRAFT && $form->hasResponses()) {
            throw ValidationException::withMessages(['status' => 'แบบประเมินที่มีคำตอบแล้วเปลี่ยนกลับเป็นฉบับร่างไม่ได้']);
        }

        if ($status === Form::STATUS_ACTIVE) {
            $this->assertPublishable($form);
        }

        $form->update([
            'status' => $status,
            'published_at' => $status === Form::STATUS_ACTIVE ? ($form->published_at ?? now()) : $form->published_at,
            'updated_by' => $user->id,
        ]);

        return $form;
    }

    public function destroy(Form $form): void
    {
        /* สถานะไม่ใช่เกณฑ์ — ชุดที่เผยแพร่แล้วแต่ยังไม่มีใครตอบและไม่ได้ผูกกิจกรรม ลบได้
           เกณฑ์จริงคือข้อมูลที่จะกำพร้า: คำตอบที่เก็บมาแล้ว กับกิจกรรมที่อ้างชุดนี้อยู่ */
        if ($form->activities()->exists() || $form->hasResponses()) {
            throw ValidationException::withMessages([
                'form' => 'ลบได้เฉพาะแบบประเมินที่ยังไม่มีคำตอบและยังไม่ถูกผูกกับกิจกรรม',
            ]);
        }

        $form->delete();
    }

    private function assertPublishable(Form $form): void
    {
        $form->loadMissing(['fields', 'questions.options']);

        if ($form->type === Form::TYPE_REGISTRATION) {
            $required = $form->fields->whereIn('field_key', EvaluationRequest::REQUIRED_FIELD_KEYS)
                ->filter(fn ($field) => $field->is_enabled && $field->is_required)
                ->pluck('field_key')->unique();
            if ($required->count() !== count(EvaluationRequest::REQUIRED_FIELD_KEYS)) {
                throw ValidationException::withMessages(['fields' => 'ฟิลด์ชื่อ–นามสกุล เบอร์โทรศัพท์ และ PDPA ต้องเปิดใช้งาน']);
            }

            return;
        }

        if ($form->questions->where('question_type', '!=', 'section')->isEmpty()) {
            throw ValidationException::withMessages(['questions' => 'ต้องมีคำถามอย่างน้อย 1 ข้อก่อนเปิดใช้งาน']);
        }
    }

    /** @return array<string, mixed> */
    private function formAttributes(array $data, User $user): array
    {
        $registration = $data['type'] === Form::TYPE_REGISTRATION;

        return [
            'name' => trim($data['name']),
            'description' => $data['description'] ?? null,
            'type' => $data['type'],
            'status' => $data['status'],
            'is_anonymous' => $data['type'] === Form::TYPE_POST_ACTIVITY,
            'registration_mode' => $registration ? $data['registration_mode'] : null,
            'max_participants' => $registration
                ? (($data['registration_mode'] ?? 'single') === 'group' ? (int) $data['max_participants'] : 1)
                : null,
            'published_at' => $data['status'] === Form::STATUS_ACTIVE ? now() : null,
            'updated_by' => $user->id,
        ];
    }

    private function replaceStructure(Form $form, array $data): void
    {
        $form->questions()->delete();
        $form->fields()->delete();

        if ($form->type === Form::TYPE_REGISTRATION) {
            foreach (collect($data['fields'] ?? [])->sortBy('sort_order')->values() as $index => $field) {
                $required = in_array($field['key'], EvaluationRequest::REQUIRED_FIELD_KEYS, true);
                $form->fields()->create([
                    'field_key' => $field['key'],
                    'is_enabled' => $required || $field['is_enabled'],
                    'is_required' => $required,
                    'sort_order' => $index + 1,
                ]);
            }
        }

        foreach (collect($data['questions'] ?? [])->sortBy('sort_order')->values() as $index => $question) {
            $saved = $form->questions()->create([
                'sort_order' => $index + 1,
                'question_type' => $question['type'],
                'text' => trim($question['text']),
                'dimension' => $question['dimension'] ?? null,
                'is_required' => $question['type'] === 'section' ? false : $question['is_required'],
            ]);

            foreach (collect($question['options'] ?? [])->values() as $optionIndex => $option) {
                $saved->options()->create([
                    'sort_order' => $optionIndex + 1,
                    'label' => trim($option['label']),
                    'value' => 'option_'.($optionIndex + 1),
                    'is_other' => (bool) ($option['is_other'] ?? false),
                ]);
            }
        }
    }
}
