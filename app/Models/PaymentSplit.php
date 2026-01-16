<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentSplit extends Model
{
    protected $fillable = [
        'payment_id',
        'partner_id',
        'amount',
        'currency',
        'percentage_applied',
        'status',
        'transferred_at',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'percentage_applied' => 'decimal:2',
        'transferred_at' => 'datetime',
    ];

    /**
     * Pago asociado
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    /**
     * Socio que recibe este split
     */
    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    /**
     * ¿Está pendiente?
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * ¿Ya fue transferido?
     */
    public function isTransferred(): bool
    {
        return $this->status === 'transferred';
    }

    /**
     * Marcar como transferido
     */
    public function markAsTransferred(?string $notes = null): void
    {
        $this->update([
            'status' => 'transferred',
            'transferred_at' => now(),
            'notes' => $notes,
        ]);
    }

    /**
     * Scope: pendientes
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope: transferidos
     */
    public function scopeTransferred($query)
    {
        return $query->where('status', 'transferred');
    }

    /**
     * Scope: por socio
     */
    public function scopeForPartner($query, int $partnerId)
    {
        return $query->where('partner_id', $partnerId);
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
            return $symbol . number_format($this->amount, 0, ',', '.');
        }

        return $symbol . number_format($this->amount, 2, ',', '.');
    }
}
