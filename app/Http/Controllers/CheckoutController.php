<?php

namespace App\Http\Controllers;

use App\Http\Requests\Checkout\StoreCheckoutRequest;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use App\Services\Orders\PlaceOrder;
use App\Services\Payments\ChargeOrder;
use App\Services\Payments\RefreshPayment;
use App\Support\Cart;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class CheckoutController extends Controller
{
    public function show(Request $request, Cart $cart): Response|RedirectResponse
    {
        $user = $request->user();

        if ($user === null) {
            abort(403);
        }

        $items = $cart->items();

        if ($items->isEmpty()) {
            return redirect()->route('cart');
        }

        return Inertia::render('Checkout', [
            'items' => $items
                ->map(fn (Product $product): array => $product->toCatalogCard())
                ->values(),
            'total' => Money::format((int) $items->sum('price_cents')),
            'payer' => [
                'name' => $user->name,
                'email' => $user->email,
            ],
            'notice' => 'Pagamento de teste, sem dinheiro real.',
        ]);
    }

    public function store(StoreCheckoutRequest $request, PlaceOrder $placeOrder, ChargeOrder $chargeOrder, Cart $cart): RedirectResponse
    {
        $user = $request->user();

        if ($user === null) {
            abort(403);
        }

        try {
            $order = $placeOrder->place($user);
        } catch (RuntimeException $exception) {
            if ($cart->items()->isEmpty()) {
                return redirect()->route('cart')->with('error', $exception->getMessage());
            }

            return back()->with('error', $exception->getMessage());
        }

        return $this->chargeAndRedirect($chargeOrder, $order, $user);
    }

    public function retry(Request $request, Order $order, ChargeOrder $chargeOrder): RedirectResponse
    {
        $user = $request->user();

        if ($user === null || $order->user_id !== $user->id) {
            abort(404);
        }

        return $this->chargeAndRedirect($chargeOrder, $order, $user);
    }

    public function payment(Request $request, Order $order, RefreshPayment $refreshPayment): Response
    {
        $this->ownedOrder($request, $order);

        if (! $request->session()->pull('payment_refreshed')) {
            $this->consult($refreshPayment, $order);
        }

        return $this->paymentPage($order);
    }

    public function refresh(Request $request, Order $order, RefreshPayment $refreshPayment): RedirectResponse
    {
        $this->ownedOrder($request, $order);

        $redirect = redirect()
            ->route('payments.show', $order)
            ->with('payment_refreshed', true);

        if (! $refreshPayment->refresh($order)) {
            return $redirect->with('error', 'Não foi possível falar com o serviço de pagamento.');
        }

        return $redirect;
    }

    private function chargeAndRedirect(ChargeOrder $chargeOrder, Order $order, User $user): RedirectResponse
    {
        try {
            $payment = $chargeOrder->charge($order, $user);
        } catch (RuntimeException $exception) {
            return redirect()
                ->route('payments.show', $order)
                ->with('error', $exception->getMessage());
        }

        $redirect = redirect()->route('payments.show', $order);

        if ($payment->status === Payment::STATUS_FAILED) {
            return $redirect->with('error', 'Não foi possível falar com o serviço de pagamento.');
        }

        return $redirect;
    }

    private function ownedOrder(Request $request, Order $order): void
    {
        $user = $request->user();

        if ($user === null || $order->user_id !== $user->id) {
            abort(404);
        }
    }

    private function consult(RefreshPayment $refreshPayment, Order $order): void
    {
        if (! $refreshPayment->refresh($order)) {
            session()->flash('error', 'Não foi possível falar com o serviço de pagamento.');
        }
    }

    private function paymentPage(Order $order): Response
    {
        $order->load('items');
        $payments = $order->payments()->latest('id')->get();
        $latest = $payments->first();

        return Inertia::render('CheckoutPayment', [
            'notice' => 'Pagamento de teste, sem dinheiro real.',
            'total' => Money::format($order->total_cents),
            'items' => $order->items->map(fn (OrderItem $item): array => [
                'title' => $item->title,
                'price' => Money::format($item->unit_price_cents),
            ])->values(),
            'payment' => $this->paymentPayload($latest),
            'explanation' => $this->explanation($latest),
            'canRetry' => $this->canRetry($order, $latest),
            'attempts' => $payments->values()->map(fn (Payment $payment, int $index): array => [
                'created_at' => $payment->created_at?->timezone((string) config('app.timezone'))->format('d/m/Y H:i'),
                'label' => $this->statusLabel($payment->status),
                'status' => $payment->status,
                'gateway_id' => $payment->gateway_id,
                'active' => $index === 0,
            ])->all(),
            'orderId' => $order->id,
        ]);
    }

    private function explanation(?Payment $payment): ?string
    {
        if ($payment === null) {
            return null;
        }

        return match ($payment->status) {
            Payment::STATUS_FAILED => 'Não conseguimos concluir este Pix.',
            Payment::STATUS_CANCELED => 'Esta tentativa foi encerrada.',
            Payment::STATUS_EXPIRED => 'Este Pix expirou.',
            default => null,
        };
    }

    private function canRetry(Order $order, ?Payment $payment): bool
    {
        if ($order->status !== Order::STATUS_PENDING_PAYMENT || $payment === null) {
            return false;
        }

        return in_array($payment->status, [
            Payment::STATUS_FAILED,
            Payment::STATUS_CANCELED,
            Payment::STATUS_EXPIRED,
        ], true);
    }

    /**
     * @return array{status: string, label: string, gateway_id: string|null, pix_code: string|null, simulated: bool, created_at: string|null}|null
     */
    private function paymentPayload(?Payment $payment): ?array
    {
        if ($payment === null) {
            return null;
        }

        $stored = $payment->raw_response;
        $pix = is_array($stored) ? ($stored['pix_code'] ?? null) : null;
        $pixCode = is_string($pix) && $pix !== '' ? $pix : null;

        return [
            'status' => $payment->status,
            'label' => $this->statusLabel($payment->status),
            'gateway_id' => $payment->gateway_id,
            'pix_code' => $pixCode,
            'simulated' => $pixCode === null || str_starts_with($pixCode, 'SIMULATED_PIX_NOT_PAYABLE'),
            'created_at' => $payment->created_at?->timezone((string) config('app.timezone'))->format('d/m/Y H:i'),
        ];
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            Payment::STATUS_PENDING => 'Pendente',
            Payment::STATUS_PAID => 'Pago',
            Payment::STATUS_FAILED => 'Recusado',
            Payment::STATUS_CANCELED => 'Cancelado',
            Payment::STATUS_EXPIRED => 'Expirado',
            default => 'Pendente',
        };
    }
}
