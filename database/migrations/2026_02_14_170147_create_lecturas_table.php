<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('lecturas', function (Blueprint $table) {
            $table->id();

            $table->foreignId('sensor_id')
                ->constrained('sensores')
                ->cascadeOnDelete();

            $table->decimal('valor', 10, 3);
            $table->dateTime('registrado_en')->index();

            $table->timestamps();

            $table->index(['sensor_id', 'registrado_en']); // clave para series/graficas
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lecturas');
    }
};
