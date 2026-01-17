<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Partner;
use App\Models\Payment;
use App\Models\PaymentSplit;
use Illuminate\Http\Request;
use Carbon\Carbon;

class PaymentReportController extends Controller
{
    /**
     * Dashboard de pagos
     */
    public function index(Request $request)
    {
        // Período por defecto: último mes
        $startDate = $request->input('start_date')
            ? Carbon::parse($request->input('start_date'))
            : now()->subMonth();
        $endDate = $request->input('end_date')
            ? Carbon::parse($request->input('end_date'))
            : now();

        // Estadísticas generales
        $stats = [
            'total_revenue' => Payment::completed()->sum('amount'),
            'period_revenue' => Payment::completed()
                ->whereBetween('paid_at', [$startDate, $endDate])
                ->sum('amount'),
            'total_payments' => Payment::completed()->count(),
            'period_payments' => Payment::completed()
                ->whereBetween('paid_at', [$startDate, $endDate])
                ->count(),
            'pending_splits' => PaymentSplit::pending()
                ->whereHas('payment', fn($q) => $q->where('status', 'completed'))
                ->sum('amount'),
        ];

        // Pagos recientes
        $recentPayments = Payment::with(['organization', 'subscription.plan'])
            ->completed()
            ->orderBy('paid_at', 'desc')
            ->limit(20)
            ->get();

        // Ingresos por mes (últimos 6 meses)
        $monthlyRevenue = Payment::completed()
            ->where('paid_at', '>=', now()->subMonths(6))
            ->selectRaw('DATE_FORMAT(paid_at, "%Y-%m") as month, SUM(amount) as total, currency')
            ->groupBy('month', 'currency')
            ->orderBy('month')
            ->get();

        return view('owner.payments.index', compact(
            'stats',
            'recentPayments',
            'monthlyRevenue',
            'startDate',
            'endDate'
        ));
    }

    /**
     * Vista de splits por socio
     */
    public function splits(Request $request)
    {
        $partners = Partner::with(['paymentSplits' => function($q) {
            $q->whereHas('payment', fn($p) => $p->where('status', 'completed'))
              ->with('payment')
              ->orderBy('created_at', 'desc');
        }])->get();

        // Resumen por socio
        $partnerSummary = $partners->map(function($partner) {
            return [
                'partner' => $partner,
                'total_earned' => $partner->getTotalEarnings(),
                'pending_amount' => $partner->getPendingAmount(),
                'transferred_amount' => $partner->paymentSplits()
                    ->where('status', 'transferred')
                    ->sum('amount'),
            ];
        });

        // Filtrar splits por estado
        $status = $request->input('status', 'all');
        $partnerId = $request->input('partner_id');

        $splitsQuery = PaymentSplit::with(['payment.organization', 'partner'])
            ->whereHas('payment', fn($q) => $q->where('status', 'completed'))
            ->orderBy('created_at', 'desc');

        if ($status !== 'all') {
            $splitsQuery->where('status', $status);
        }

        if ($partnerId) {
            $splitsQuery->where('partner_id', $partnerId);
        }

        $splits = $splitsQuery->paginate(30);

        return view('owner.payments.splits', compact('partners', 'partnerSummary', 'splits', 'status', 'partnerId'));
    }

    /**
     * Marcar split como transferido
     */
    public function markTransferred(Request $request, PaymentSplit $split)
    {
        $split->markAsTransferred($request->input('notes'));

        return back()->with('success', 'Split marcado como transferido.');
    }

    /**
     * Marcar múltiples splits como transferidos
     */
    public function markMultipleTransferred(Request $request)
    {
        $validated = $request->validate([
            'split_ids' => 'required|array',
            'split_ids.*' => 'exists:payment_splits,id',
            'notes' => 'nullable|string',
        ]);

        PaymentSplit::whereIn('id', $validated['split_ids'])
            ->update([
                'status' => 'transferred',
                'transferred_at' => now(),
                'notes' => $validated['notes'],
            ]);

        return back()->with('success', count($validated['split_ids']) . ' splits marcados como transferidos.');
    }

    /**
     * Exportar splits a CSV
     */
    public function exportCsv(Request $request)
    {
        $status = $request->input('status', 'all');
        $partnerId = $request->input('partner_id');

        $splitsQuery = PaymentSplit::with(['payment.organization', 'partner'])
            ->whereHas('payment', fn($q) => $q->where('status', 'completed'))
            ->orderBy('created_at', 'desc');

        if ($status !== 'all') {
            $splitsQuery->where('status', $status);
        }

        if ($partnerId) {
            $splitsQuery->where('partner_id', $partnerId);
        }

        $splits = $splitsQuery->get();

        $filename = 'splits_' . now()->format('Y-m-d') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function() use ($splits) {
            $file = fopen('php://output', 'w');

            // Headers
            fputcsv($file, [
                'Fecha Pago',
                'Socio',
                'Porcentaje',
                'Monto',
                'Moneda',
                'Estado',
                'Fecha Transferencia',
                'Organización',
                'Proveedor',
            ]);

            foreach ($splits as $split) {
                fputcsv($file, [
                    $split->payment->paid_at?->format('Y-m-d H:i'),
                    $split->partner->name,
                    $split->percentage_applied . '%',
                    $split->amount,
                    $split->currency,
                    $split->status,
                    $split->transferred_at?->format('Y-m-d H:i'),
                    $split->payment->organization?->name ?? 'N/A',
                    $split->payment->payment_provider,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
