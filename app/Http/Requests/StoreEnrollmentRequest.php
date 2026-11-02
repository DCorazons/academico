<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEnrollmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'student_id' => ['required', 'integer', 'exists:students,id'],
            'course_id' => [
                'required',
                'integer',
                'exists:courses,id',
                Rule::unique('enrollments')->where('student_id', $this->integer('student_id')),
            ],
        ];
    }
}
