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
        'mp_user_id',
        'mp_access_token',
        'mp_refresh_token',
        'mp_token_expires_at',
        'mp_connected',
        'split_percentage',
        'is_active',
        'can_edit_settings',
    ];

    protected $casts = [
        'split_percentage' => 'decimal:2',
        'is_active' => 'boolean',
        'can_edit_settings' => 'boolean',
        'mp_connected' => 'boolean',
        'mp_token_expires_at' => 'datetime',
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

    /**
     * Verificar si tiene Mercado Pago conectado
     */
    public function hasMercadoPagoConnected(): bool
    {
        return $this->mp_connected && !empty($this->mp_user_id);
    }

    /**
     * Verificar si el token de MP está vigente
     */
    public function isMpTokenValid(): bool
    {
        if (!$this->mp_access_token || !$this->mp_token_expires_at) {
            return false;
        }
        return $this->mp_token_expires_at->isFuture();
    }

    /**
     * Desconectar Mercado Pago
     */
    public function disconnectMercadoPago(): void
    {
        $this->update([
            'mp_user_id' => null,
            'mp_access_token' => null,
            'mp_refresh_token' => null,
            'mp_token_expires_at' => null,
            'mp_connected' => false,
        ]);
    }
}
