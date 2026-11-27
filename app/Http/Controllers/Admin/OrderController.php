<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\User;
use App\Services\Payments\SettlePayment;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class OrderController extends Controller
{
    private const PER_PAGE = 15;

    public function index(Request $request): Response
    {
        $status = $request->string('status')->toString();

        if (! in_array($status, [Order::STATUS_PENDING_PAYMENT, Order::STATUS_PAID], true)) {
            $status = '';
        }

        $buyer = User::query()->find($request->integer('user'));

        $orders = Order::query()
            ->with(['user:id,name', 'items:id,order_id,title'])
            ->when($buyer instanceof User, fn ($query) => $query->where('user_id', $buyer->id))
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
            'buyer' => $buyer instanceof User ? [
                'id' => $buyer->id,
                'name' => $buyer->name,
            ] : null,
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

        $latestId = $order->payments->first()?->id;

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
                'payments' => $order->payments->map(function (Payment $payment) use ($order, $latestId): array {
                    $actions = $payment->id === $latestId ? $this->actionsFor($order, $payment) : [];

                    return [
                        'id' => $payment->id,
                        'created_at' => $payment->created_at?->timezone((string) config('app.timezone'))->format('d/m/Y H:i'),
                        'status' => $payment->status,
                        'label' => $this->paymentLabel($payment->status),
                        'amount' => Money::format($payment->amount_cents),
                        'settle_url' => $actions === [] ? null : route('admin.orders.settle', [$order, $payment]),
                        'actions' => $actions,
                    ];
                })->values(),
            ],
        ]);
    }

    public function settle(Request $request, Order $order, Payment $payment, SettlePayment $settle): RedirectResponse
    {
        if ($payment->order_id !== $order->id) {
            abort(404);
        }

        $action = $request->string('action')->toString();

        try {
            $settle->settle($payment, $action);
        } catch (RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', match ($action) {
            'approve' => 'Pagamento aprovado.',
            'decline' => 'Pagamento recusado.',
            'cancel' => 'Pagamento cancelado.',
            'expire' => 'Pagamento expirado.',
            default => 'Pagamento atualizado.',
        });
    }

    /**
     * @return list<array{name: string, label: string}>
     */
    private function actionsFor(Order $order, Payment $payment): array
    {
        if ($order->status !== Order::STATUS_PENDING_PAYMENT || $payment->status !== Payment::STATUS_PENDING) {
            return [];
        }

        if (! is_string($payment->gateway_id) || $payment->gateway_id === '') {
            return [];
        }

        return [
            ['name' => 'approve', 'label' => 'Aprovar'],
            ['name' => 'decline', 'label' => 'Recusar'],
            ['name' => 'cancel', 'label' => 'Cancelar'],
            ['name' => 'expire', 'label' => 'Expirar'],
        ];
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
