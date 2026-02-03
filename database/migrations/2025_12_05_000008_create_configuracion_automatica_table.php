<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('configuracion_automatica', function (Blueprint $table) {
            $table->id();

            // modo_global enum('manual','automatico') NOT NULL DEFAULT 'manual'
            $table->enum('modo_global', ['manual', 'automatico'])->default('manual');

            // stale_min int(10) UNSIGNED NOT NULL DEFAULT 5
            $table->unsignedInteger('stale_min')->default(5);

            // tz varchar(64) NOT NULL DEFAULT 'America/La_Paz'
            $table->string('tz', 64)->default('America/La_Paz');
            
            $table->timestamps(); // Incluye created_at y updated_at
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('configuracion_automatica');
    }
};