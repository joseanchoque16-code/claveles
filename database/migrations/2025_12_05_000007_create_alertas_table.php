<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alertas', function (Blueprint $table) {
            $table->id();

            // FK a sensores (Opcional, ya que tu SQL usa DEFAULT NULL)
            $table->foreignId('sensor_id')->nullable()->constrained('sensores')->onDelete('set null');

            $table->string('tipo', 50); // Ej: 'umbral'
            $table->string('mensaje', 255);
            $table->enum('nivel', ['info', 'warning', 'critical'])->default('warning');
            $table->boolean('visto')->default(false); // tinyint(1)
            
            // Tu SQL solo tiene created_at
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alertas');
    }
};