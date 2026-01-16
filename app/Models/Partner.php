<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Partner extends Model
{
    protected $fillable = [
        'name',
        'email',
        'role',
        'paypal_email',
        'mercadopago_email',
        'split_percentage',
        'is_active',
        'can_edit_settings',
    ];

    protected $casts = [
        'split_percentage' => 'decimal:2',
        'is_active' => 'boolean',
        'can_edit_settings' => 'boolean',
    ];

    /**
     * Splits de pagos de este socio
     */
    public function paymentSplits(): HasMany
    {
        return $this->hasMany(PaymentSplit::class);
    }

    /**
     * Usuario asociado (si existe)
     */
    public function user()
    {
        return User::where('email', $this->email)->first();
    }

    /**
     * Scope: solo activos
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: owner (puede editar)
     */
    public function scopeOwner($query)
    {
        return $query->where('can_edit_settings', true);
    }

    /**
     * Es el owner?
     */
    public function isOwner(): bool
    {
        return $this->can_edit_settings || $this->role === 'owner';
    }

    /**
     * Total ganado (completado)
     */
    public function getTotalEarnings(): float
    {
        return $this->paymentSplits()
            ->whereHas('payment', function ($q) {
                $q->where('status', 'completed');
            })
            ->sum('amount');
    }

    /**
     * Total pendiente de transferir
     */
    public function getPendingAmount(): float
    {
        return $this->paymentSplits()
            ->where('status', 'pending')
            ->whereHas('payment', function ($q) {
                $q->where('status', 'completed');
            })
            ->sum('amount');
    }
}
