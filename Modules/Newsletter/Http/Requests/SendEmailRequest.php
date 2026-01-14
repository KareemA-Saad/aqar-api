<?php

declare(strict_types=1);

namespace Modules\Newsletter\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request validation for sending emails to newsletter subscribers.
 */
class SendEmailRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'subject' => ['required', 'string', 'max:191'],
            'message' => ['required', 'string', 'max:5000'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'subject.required' => 'Email subject is required',
            'subject.max' => 'Email subject cannot exceed 191 characters',
            'message.required' => 'Email message is required',
            'message.max' => 'Email message cannot exceed 5000 characters',
        ];
    }
}
