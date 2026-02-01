<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payment extends Model
{
    protected $fillable = [
        'organization_id',
        'subscription_id',
        'payment_provider',
        'provider_payment_id',
        'amount',
        'currency',
        'amount_usd',
        'status',
        'payer_email',
        'payer_name',
        'country_code',
        'paid_at',
        'refunded_at',
        'provider_data',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'amount_usd' => 'decimal:2',
        'paid_at' => 'datetime',
        'refunded_at' => 'datetime',
        'provider_data' => 'array',
    ];

    /**
     * Organización
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Suscripción asociada
     */
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    /**
     * Splits de este pago
     */
    public function splits(): HasMany
    {
        return $this->hasMany(PaymentSplit::class);
    }

    /**
     * ¿Está completado?
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Scope: completados
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope: por proveedor
     */
    public function scopeProvider($query, string $provider)
    {
        return $query->where('payment_provider', $provider);
    }

    /**
     * Scope: por período
     */
    public function scopePeriod($query, $startDate, $endDate)
    {
        return $query->whereBetween('paid_at', [$startDate, $endDate]);
    }

    /**
     * Crear splits para este pago
     */
    public function createSplits(): void
    {
        $partners = Partner::active()->get();

        foreach ($partners as $partner) {
            $splitAmount = ($this->amount * $partner->split_percentage) / 100;

            PaymentSplit::create([
                'payment_id' => $this->id,
                'partner_id' => $partner->id,
                'amount' => $splitAmount,
                'currency' => $this->currency,
                'percentage_applied' => $partner->split_percentage,
                'status' => 'pending',
            ]);
        }
    }

    /**
     * Formatear monto
     */
    public function getFormattedAmount(): string
    {
        $symbols = [
            'CLP' => '$',
            'USD' => 'US$',
            'EUR' => '€',
            'PEN' => 'S/',
            'BRL' => 'R$',
        ];

        $symbol = $symbols[$this->currency] ?? '$';

        if ($this->currency === 'CLP') {
            return $symbol.number_format($this->amount, 0, ',', '.');
        }

        return $symbol.number_format($this->amount, 2, ',', '.');
    }

    /**
     * Nombre del proveedor
     */
    public function getProviderName(): string
    {
        return match ($this->payment_provider) {
            'paypal' => 'PayPal',
            'mercadopago' => 'Mercado Pago',
            default => ucfirst($this->payment_provider),
        };
    }
}
