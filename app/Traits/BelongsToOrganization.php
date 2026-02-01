<?php

namespace App\Traits;

use App\Models\Organization;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToOrganization
{
    /**
     * Boot the trait
     */
    protected static function bootBelongsToOrganization(): void
    {
        // Global scope: filtrar automáticamente por organización actual
        static::addGlobalScope('organization', function (Builder $builder) {
            if (auth()->check()) {
                $currentOrg = auth()->user()->currentOrganization();
                if ($currentOrg) {
                    $builder->where($builder->getModel()->getTable().'.organization_id', $currentOrg->id);
                }
            }
        });

        // Al crear: asignar organization_id automáticamente
        static::creating(function ($model) {
            if (auth()->check() && empty($model->organization_id)) {
                $currentOrg = auth()->user()->currentOrganization();
                if ($currentOrg) {
                    $model->organization_id = $currentOrg->id;
                }
            }
        });
    }

    /**
     * Relación con la organización
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Scope para obtener registros de una organización específica
     */
    public function scopeForOrganization(Builder $query, $organizationId): Builder
    {
        return $query->where($this->getTable().'.organization_id', $organizationId);
    }

    /**
     * Scope para obtener registros sin filtro de organización
     * Útil para queries administrativos o reportes globales
     */
    public function scopeWithoutOrganizationScope(Builder $query): Builder
    {
        return $query->withoutGlobalScope('organization');
    }
}
