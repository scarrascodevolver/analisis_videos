<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Already fixed in a previous migration (categories_org_name_unique exists)
    }

    public function down(): void
    {
        // Nothing to revert
    }
};
