<?php

namespace App\Http\Requests\Supervisor;

use App\Models\Evaluation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EvaluationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isSupervisor() ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $scale = (int) settings('evaluation_scale', 5);
        $isUpdate = $this->route('evaluation') instanceof Evaluation;

        $rules = [
            'strengths' => ['nullable', 'string', 'max:3000'],
            'areas_for_improvement' => ['nullable', 'string', 'max:3000'],
            'remarks' => ['nullable', 'string', 'max:3000'],
            'recommendation' => ['nullable', 'string', 'max:100'],
        ];

        foreach (array_keys(Evaluation::$criteria) as $criterion) {
            $rules[$criterion] = ['required', 'integer', 'between:1,'.$scale];
        }

        if (! $isUpdate) {
            $rules['internship_id'] = ['required', 'integer', Rule::exists('internships', 'id')->whereNull('deleted_at')];
            $rules['week_start'] = ['required', 'date'];
        }

        return $rules;
    }
}
