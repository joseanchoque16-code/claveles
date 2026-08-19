<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('alertas', function (Blueprint $table) {
            $table->id();

            $table->enum('tipo', [
                'SENSOR_STALE',
                'OUT_OF_RANGE',
                'TANK_EMPTY',
                'IRRIGATION_BLOCKED',
                'ACTUATOR_FAIL',
            ])->index();

            $table->enum('nivel', ['info', 'warning', 'critical'])
                ->default('warning')
                ->index();

            $table->foreignId('sensor_id')
                ->nullable()
                ->constrained('sensores')
                ->nullOnDelete();

            $table->foreignId('dispositivo_id')
                ->nullable()
                ->constrained('dispositivos')
                ->nullOnDelete();

            $table->string('mensaje', 255);
            $table->json('contexto')->nullable();

            $table->boolean('visto')->default(false)->index();
            $table->dateTime('cerrado_en')->nullable();

            $table->timestamps();
            $table->index(['tipo', 'nivel', 'visto']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alertas');
    }
};
