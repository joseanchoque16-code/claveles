<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('configuracion_automatica', function (Blueprint $table) {
            $table->id();

            $table->enum('modo_global', ['manual', 'automatico'])->default('automatico');
            $table->unsignedInteger('stale_min')->default(10); // sensor caído si no reporta en X min
            $table->string('timezone', 64)->default('America/La_Paz');

            // sube cada vez que el admin cambia setpoints/reglas (ESP32 detecta cambios)
            $table->unsignedBigInteger('config_version')->default(1)->index();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('configuracion_automatica');
    }
};
