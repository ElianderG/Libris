<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Support\Money;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    private const PER_PAGE = 15;

    public function index(Request $request): Response
    {
        $status = $request->string('status')->toString();

        if (! in_array($status, [Order::STATUS_PENDING_PAYMENT, Order::STATUS_PAID], true)) {
            $status = '';
        }

        $orders = Order::query()
            ->with(['user:id,name', 'items:id,order_id,title'])
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->latest('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString()
            ->through(fn (Order $order): array => [
                'id' => $order->id,
                'created_at' => $order->created_at?->timezone((string) config('app.timezone'))->format('d/m/Y H:i'),
                'buyer' => $order->user->name,
                'titles' => $order->items->map(fn (OrderItem $item): string => $item->title)->values()->all(),
                'total' => Money::format($order->total_cents),
                'status' => $order->status,
                'status_label' => $this->orderLabel($order->status),
                'detail_url' => route('admin.orders.show', $order),
            ]);

        return Inertia::render('admin/orders/Index', [
            'status' => $status,
            'orders' => $orders,
        ]);
    }

    public function show(Order $order): Response
    {
        $order->load([
            'user:id,name,email',
            'items',
            'payments' => fn ($query) => $query->latest('id'),
        ]);

        return Inertia::render('admin/orders/Show', [
            'order' => [
                'id' => $order->id,
                'created_at' => $order->created_at?->timezone((string) config('app.timezone'))->format('d/m/Y H:i'),
                'buyer' => $order->user->name,
                'email' => $order->user->email,
                'status' => $order->status,
                'status_label' => $this->orderLabel($order->status),
                'total' => Money::format($order->total_cents),
                'items' => $order->items->map(fn (OrderItem $item): array => [
                    'title' => $item->title,
                    'quantity' => $item->quantity,
                    'price' => Money::format($item->unit_price_cents),
                ])->values(),
                'payments' => $order->payments->map(fn (Payment $payment): array => [
                    'id' => $payment->id,
                    'created_at' => $payment->created_at?->timezone((string) config('app.timezone'))->format('d/m/Y H:i'),
                    'status' => $payment->status,
                    'label' => $this->paymentLabel($payment->status),
                    'amount' => Money::format($payment->amount_cents),
                    'gateway_id' => $payment->gateway_id,
                    'raw' => $this->rawBlock($payment),
                ])->values(),
            ],
        ]);
    }

    private function rawBlock(Payment $payment): ?string
    {
        if ($payment->raw_request === null && $payment->raw_response === null) {
            return null;
        }

        $json = json_encode(
            ['request' => $payment->raw_request, 'response' => $payment->raw_response],
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
        );

        return is_string($json) ? $json : null;
    }

    private function orderLabel(string $status): string
    {
        return match ($status) {
            Order::STATUS_PAID => 'Pago',
            default => 'Aguardando pagamento',
        };
    }

    private function paymentLabel(string $status): string
    {
        return match ($status) {
            Payment::STATUS_PAID => 'Pago',
            Payment::STATUS_FAILED => 'Recusado',
            Payment::STATUS_CANCELED => 'Cancelado',
            Payment::STATUS_EXPIRED => 'Expirado',
            default => 'Pendente',
        };
    }
}
