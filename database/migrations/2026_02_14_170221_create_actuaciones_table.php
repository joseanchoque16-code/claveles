<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('actuaciones', function (Blueprint $table) {
            $table->id();

            $table->foreignId('dispositivo_id')
                ->constrained('dispositivos')
                ->cascadeOnDelete();

            $table->foreignId('control_regla_id')
                ->nullable()
                ->constrained('control_reglas')
                ->nullOnDelete();

            $table->enum('accion', ['on', 'off']);
            $table->enum('origen', ['manual', 'automatico']);

            $table->decimal('valor_sensor', 10, 3)->nullable();
            $table->json('motivo')->nullable();

            $table->dateTime('ejecutado_en')->index();
            $table->timestamps();

            $table->index(['dispositivo_id', 'ejecutado_en']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('actuaciones');
    }
};
