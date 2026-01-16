<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubscriptionPlan extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'price_clp',
        'price_pen',
        'price_brl',
        'price_eur',
        'price_usd',
        'duration_months',
        'is_active',
        'features',
    ];

    protected $casts = [
        'price_clp' => 'decimal:0',
        'price_pen' => 'decimal:2',
        'price_brl' => 'decimal:2',
        'price_eur' => 'decimal:2',
        'price_usd' => 'decimal:2',
        'duration_months' => 'integer',
        'is_active' => 'boolean',
        'features' => 'array',
    ];

    /**
     * Suscripciones de este plan
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class, 'plan_id');
    }

    /**
     * Obtener precio según moneda
     */
    public function getPriceForCurrency(string $currency): float
    {
        $field = 'price_' . strtolower($currency);

        if (isset($this->$field)) {
            return (float) $this->$field;
        }

        // Fallback a USD
        return (float) $this->price_usd;
    }

    /**
     * Scope: solo planes activos
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Formatear precio con moneda
     */
    public function getFormattedPrice(string $currency): string
    {
        $price = $this->getPriceForCurrency($currency);
        $symbols = [
            'clp' => '$',
            'usd' => 'US$',
            'eur' => '€',
            'pen' => 'S/',
            'brl' => 'R$',
        ];

        $symbol = $symbols[strtolower($currency)] ?? '$';

        if (strtolower($currency) === 'clp') {
            return $symbol . number_format($price, 0, ',', '.');
        }

        return $symbol . number_format($price, 2, ',', '.');
    }
}
