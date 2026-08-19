<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sensores', function (Blueprint $table) {
            $table->id();

            $table->string('codigo', 50)->unique();   // S_TEMP, S_HR, S_HSUELO, S_LUZ, S_NIVEL
            $table->string('nombre', 120);
            $table->string('tipo', 50);               // dht11_temp, dht11_hum, soil_cap, ldr, water_level
            $table->string('unidad', 20)->nullable(); // °C, %, etc.

            $table->unsignedInteger('gpio_pin')->nullable(); // fijo (si querés)
            $table->boolean('activo')->default(true);

            $table->decimal('valor_actual', 10, 3)->nullable();
            $table->timestamps();

            $table->index('tipo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sensores');
    }
};
