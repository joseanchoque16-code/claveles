<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('dispositivos', function (Blueprint $table) {
            $table->id();

            $table->string('codigo', 50)->unique(); // D_RIEGO, D_LUZ, D_FAN, D_CALEF
            $table->string('nombre', 120);
            $table->string('tipo', 30)->default('rele');

            $table->unsignedInteger('gpio_pin');     // fijo
            $table->boolean('invertido')->default(false); // true si el relé es activo LOW

            $table->tinyInteger('estado')->default(0); // 0=OFF, 1=ON (lógico)
            $table->boolean('habilitado')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dispositivos');
    }
};
