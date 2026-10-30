<?php

namespace App\Http\Requests\Student;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isStudent() ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $submitting = $this->input('action') === 'submit';

        $required = Rule::requiredIf($submitting);

        return [
            'date' => ['required', 'date', 'before_or_equal:today'],
            'activities_performed' => [$required, 'nullable', 'string', 'min:10', 'max:5000'],
            'skills_learned' => [$required, 'nullable', 'string', 'min:10', 'max:5000'],
            'challenges' => [$required, 'nullable', 'string', 'min:5', 'max:5000'],
            'solutions' => [$required, 'nullable', 'string', 'min:5', 'max:5000'],
            'daily_reflection' => [$required, 'nullable', 'string', 'min:10', 'max:5000'],
            'hours_worked' => [$required, 'nullable', 'numeric', 'between:0.5,24'],
            'action' => ['required', Rule::in(['draft', 'submit'])],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'date.before_or_equal' => 'Future dates cannot be submitted.',
        ];
    }
}
