<?php

namespace App\Http\Requests\Checkout;

use App\Models\Order;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class RetryPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $order = $this->route('order');

        abort_unless($order instanceof Order && $this->user()?->can('view', $order), 404);

        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'document' => ['nullable', 'string', 'max:20'],
        ];
    }

    public function payerDocument(): ?string
    {
        $order = $this->route('order');

        if (! $order instanceof Order || $order->gateway !== Order::GATEWAY_PAGSMILE) {
            return null;
        }

        return preg_replace('/\D/', '', (string) $this->input('document')) ?? '';
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $order = $this->route('order');

            if (! $order instanceof Order || $order->gateway !== Order::GATEWAY_PAGSMILE) {
                return;
            }

            if (strlen((string) $this->payerDocument()) !== 11) {
                $validator->errors()->add('document', 'Informe o CPF com 11 dígitos.');
            }
        });
    }
}
