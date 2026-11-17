<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CourseReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'period_id' => ['required', 'integer', 'exists:periods,id'],
        ];
    }
}
