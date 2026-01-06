<?php

namespace App\Models;

use App\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory, BelongsToOrganization;

    protected $fillable = ['key', 'value'];

    /**
     * Obtener un setting por su key
     */
    public static function get($key, $default = null)
    {
        $setting = self::where('key', $key)->first();
        return $setting ? $setting->value : $default;
    }

    /**
     * Establecer un setting
     */
    public static function set($key, $value)
    {
        return self::updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );
    }

    /**
     * Verificar si las evaluaciones están habilitadas
     */
    public static function areEvaluationsEnabled()
    {
        return (bool) self::get('evaluations_enabled', true);
    }

    /**
     * Alternar estado de evaluaciones
     */
    public static function toggleEvaluations()
    {
        $currentStatus = self::get('evaluations_enabled', '1');
        $newStatus = $currentStatus === '1' ? '0' : '1';
        self::set('evaluations_enabled', $newStatus);
        return $newStatus === '1';
    }
}
