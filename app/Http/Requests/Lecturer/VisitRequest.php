<?php

namespace App\Http\Requests\Lecturer;

use App\Models\LecturerVisit;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VisitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isLecturer() ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $isUpdate = $this->route('visit') instanceof LecturerVisit;

        $rules = [
            'visit_date' => ['required', 'date', 'after_or_equal:today'],
            'purpose' => ['required', 'string', 'min:5', 'max:500'],
        ];

        if (! $isUpdate) {
            $rules['internship_id'] = ['required', 'integer', Rule::exists('internships', 'id')->whereNull('deleted_at')];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'visit_date.after_or_equal' => 'Visits must be scheduled for today or a future date.',
        ];
    }
}
