 <?php

  use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('videos', function (Blueprint $table) {
            $table->bigInteger('file_size')->unsigned()->change();
            $table->bigInteger('original_file_size')->unsigned()->nullable()->change();
            $table->bigInteger('compressed_file_size')->unsigned()->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('videos', function (Blueprint $table) {
            $table->integer('file_size')->unsigned()->change();
            $table->integer('original_file_size')->unsigned()->nullable()->change();
            $table->integer('compressed_file_size')->unsigned()->nullable()->change();
        });
    }
};
