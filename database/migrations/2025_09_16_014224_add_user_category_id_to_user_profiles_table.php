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
        Schema::table('user_profiles', function (Blueprint $table) {
            $table->unsignedBigInteger('user_category_id')
                ->nullable()
                ->after('division_category')
                ->comment('FK a categories - categoría del usuario (Juveniles, Adulta Primera, etc.)');

            $table->foreign('user_category_id')
                ->references('id')
                ->on('categories')
                ->onUpdate('cascade')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_profiles', function (Blueprint $table) {
            $table->dropForeign(['user_category_id']);
            $table->dropColumn('user_category_id');
        });
    }
};
