<?php

namespace App\Http\Requests\Checkout;

use App\Models\Order;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class StoreCheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'gateway' => ['nullable', 'string'],
            'document' => ['nullable', 'string', 'max:20'],
        ];
    }

    public function gatewayChoice(): string
    {
        $gateway = $this->string('gateway')->toString();

        return in_array($gateway, [Order::GATEWAY_JUNTOS, Order::GATEWAY_PAGSMILE], true)
            ? $gateway
            : Order::GATEWAY_JUNTOS;
    }

    public function payerDocument(): ?string
    {
        if ($this->gatewayChoice() !== Order::GATEWAY_PAGSMILE) {
            return null;
        }

        $digits = preg_replace('/\D/', '', (string) $this->input('document')) ?? '';

        return $digits;
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->gatewayChoice() !== Order::GATEWAY_PAGSMILE) {
                return;
            }

            if (strlen((string) $this->payerDocument()) !== 11) {
                $validator->errors()->add('document', 'Informe o CPF com 11 dígitos.');
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Informe o nome para confirmar.',
            'email.required' => 'Informe o e-mail para confirmar.',
            'email.email' => 'Informe um e-mail válido.',
        ];
    }
}
