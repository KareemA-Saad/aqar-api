<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EmailTemplateRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $templateTypes = [
            'user_reset_password',
            'user_email_verify',
            'admin_email_verify',
            'newsletter_verify',
            'wallet_manual_payment_approved',
        ];

        return [
            'subject' => ['required', 'string', 'max:191'],
            'message' => ['required', 'string', 'max:5000'],
            'type' => ['required', 'string', Rule::in($templateTypes)],
            'lang' => ['required', 'string', 'max:10'],
        ];
    }

    /**
     * Get custom error messages for validator errors.
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
            'type.required' => 'Template type is required',
            'type.in' => 'Invalid template type',
            'lang.required' => 'Language code is required',
        ];
    }
}
