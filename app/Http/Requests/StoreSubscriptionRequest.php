<?php

namespace App\Http\Requests;

use App\Models\Package;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreSubscriptionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('manage_subscriptions') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'package_id' => ['required', 'exists:packages,id'],
            'pt_user_id' => [
                'nullable',
                'exists:users,id',
                function ($attribute, $value, $fail) {
                    $package = Package::find($this->input('package_id'));
                    if ($package && in_array($package->type, ['pt', 'combo']) && empty($value)) {
                        $fail('Instruktur Personal Trainer (PT) wajib dipilih untuk paket ini.');
                    }
                },
            ],
            'start_date' => ['nullable', 'date'],
            'queue_after_active' => ['nullable', 'boolean'],
            'price_paid' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * Custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'package_id.required' => 'Pilihan paket langganan wajib dipilih.',
            'package_id.exists' => 'Paket langganan yang dipilih tidak valid.',
        ];
    }
}
