<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('partners', function (Blueprint $table) {
            // Mercado Pago OAuth credentials para split automático
            $table->string('mp_user_id')->nullable()->after('mercadopago_email');
            $table->text('mp_access_token')->nullable()->after('mp_user_id');
            $table->text('mp_refresh_token')->nullable()->after('mp_access_token');
            $table->timestamp('mp_token_expires_at')->nullable()->after('mp_refresh_token');
            $table->boolean('mp_connected')->default(false)->after('mp_token_expires_at');
        });
    }

    public function down(): void
    {
        Schema::table('partners', function (Blueprint $table) {
            $table->dropColumn([
                'mp_user_id',
                'mp_access_token',
                'mp_refresh_token',
                'mp_token_expires_at',
                'mp_connected',
            ]);
        });
    }
};
