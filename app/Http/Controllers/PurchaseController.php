<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Support\Money;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PurchaseController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        if ($user === null) {
            abort(403);
        }

        $orders = Order::query()
            ->where('user_id', $user->id)
            ->with(['items', 'payments'])
            ->latest('id')
            ->get();

        return Inertia::render('Purchases', [
            'orders' => $orders->map(fn (Order $order): array => $this->row($order))->values(),
        ]);
    }

    /**
     * @return array{id: int, created_at: string|null, titles: array<int, string>, total: string, status: string, status_label: string, payment_status: string|null, payment_label: string|null, pix_url: string|null}
     */
    private function row(Order $order): array
    {
        $latest = $order->payments->sortByDesc('id')->first();

        return [
            'id' => $order->id,
            'created_at' => $order->created_at?->timezone((string) config('app.timezone'))->format('d/m/Y H:i'),
            'titles' => $order->items->map(fn (OrderItem $item): string => $item->title)->values()->all(),
            'total' => Money::format($order->total_cents),
            'status' => $order->status,
            'status_label' => $this->orderLabel($order->status),
            'payment_status' => $latest?->status,
            'payment_label' => $latest === null ? null : $this->paymentLabel($latest->status),
            'pix_url' => $order->status === Order::STATUS_PENDING_PAYMENT
                ? route('payments.show', $order)
                : null,
        ];
    }

    private function orderLabel(string $status): string
    {
        return match ($status) {
            Order::STATUS_PENDING_PAYMENT => 'Aguardando pagamento',
            Order::STATUS_PAID => 'Pago',
            'canceled' => 'Cancelado',
            'expired' => 'Expirado',
            default => 'Aguardando pagamento',
        };
    }

    private function paymentLabel(string $status): string
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
