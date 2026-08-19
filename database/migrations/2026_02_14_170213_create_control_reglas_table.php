<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('control_reglas', function (Blueprint $table) {
            $table->id();

            $table->foreignId('sensor_id')
                ->constrained('sensores')
                ->cascadeOnDelete();

            $table->foreignId('dispositivo_id')
                ->constrained('dispositivos')
                ->cascadeOnDelete();

            $table->boolean('activa')->default(true);
            $table->unsignedInteger('prioridad')->default(10)->index();

            // Histéresis: dos umbrales
            $table->decimal('umbral_on', 10, 3)->nullable();
            $table->decimal('umbral_off', 10, 3)->nullable();

            // Anti-ciclado (segundos)
            $table->unsignedInteger('min_on_s')->default(0);
            $table->unsignedInteger('min_off_s')->default(0);

            // Ventana horaria opcional
            $table->time('hora_inicio')->nullable();
            $table->time('hora_fin')->nullable();
            $table->json('dias_semana')->nullable(); // ej: [1,2,3,4,5,6,7]

            $table->timestamps();

            $table->index(['sensor_id', 'dispositivo_id']);
            $table->index(['activa', 'prioridad']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('control_reglas');
    }
};
