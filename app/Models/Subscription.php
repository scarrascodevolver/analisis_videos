<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subscription extends Model
{
    protected $fillable = [
        'organization_id',
        'plan_id',
        'status',
        'payment_provider',
        'provider_subscription_id',
        'current_period_start',
        'current_period_end',
        'cancelled_at',
        'cancellation_reason',
        'trial_ends_at',
    ];

    protected $casts = [
        'current_period_start' => 'datetime',
        'current_period_end' => 'datetime',
        'cancelled_at' => 'datetime',
        'trial_ends_at' => 'datetime',
    ];

    /**
     * Organización de esta suscripción
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Plan de suscripción
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'plan_id');
    }

    /**
     * Pagos de esta suscripción
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * ¿Está activa?
     */
    public function isActive(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        if ($this->current_period_end && $this->current_period_end->isPast()) {
            return false;
        }

        return true;
    }

    /**
     * ¿Está en período de prueba?
     */
    public function onTrial(): bool
    {
        return $this->trial_ends_at && $this->trial_ends_at->isFuture();
    }

    /**
     * ¿Está cancelada pero aún activa?
     */
    public function onGracePeriod(): bool
    {
        return $this->cancelled_at &&
               $this->current_period_end &&
               $this->current_period_end->isFuture();
    }

    /**
     * Días restantes
     */
    public function daysRemaining(): int
    {
        if (! $this->current_period_end) {
            return 0;
        }

        return max(0, now()->diffInDays($this->current_period_end, false));
    }

    /**
     * Scope: activas
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('current_period_end')
                    ->orWhere('current_period_end', '>', now());
            });
    }

    /**
     * Scope: expiradas
     */
    public function scopeExpired($query)
    {
        return $query->where('current_period_end', '<', now());
    }

    /**
     * Cancelar suscripción
     */
    public function cancel(?string $reason = null): void
    {
        $this->update([
            'cancelled_at' => now(),
            'cancellation_reason' => $reason,
        ]);
    }

    /**
     * Renovar suscripción
     */
    public function renew(?int $months = null): void
    {
        $months = $months ?? $this->plan->duration_months ?? 1;

        $this->update([
            'status' => 'active',
            'current_period_start' => now(),
            'current_period_end' => now()->addMonths($months),
            'cancelled_at' => null,
            'cancellation_reason' => null,
        ]);
    }
}
