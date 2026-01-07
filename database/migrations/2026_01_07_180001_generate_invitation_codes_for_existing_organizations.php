<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Generar códigos únicos para organizaciones existentes
        $organizations = DB::table('organizations')->whereNull('invitation_code')->get();

        foreach ($organizations as $org) {
            $code = $this->generateUniqueCode();
            DB::table('organizations')
                ->where('id', $org->id)
                ->update(['invitation_code' => $code]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No revertimos los códigos generados
    }

    /**
     * Genera un código único de 8 caracteres alfanuméricos (mayúsculas)
     */
    private function generateUniqueCode(): string
    {
        do {
            // Generar código de 8 caracteres: letras mayúsculas y números
            $code = strtoupper(Str::random(8));
            // Verificar que sea único
            $exists = DB::table('organizations')->where('invitation_code', $code)->exists();
        } while ($exists);

        return $code;
    }
};
