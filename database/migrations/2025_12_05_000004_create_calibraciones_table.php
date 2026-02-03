<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calibraciones', function (Blueprint $table) {
            $table->id();

            // FK a sensores (Requerido)
            $table->foreignId('sensor_id')->constrained('sensores')->onDelete('cascade');
            
            // decimal(6,3) con default 0.000
            $table->decimal('offset', 6, 3)->default(0.000);
            // decimal(6,3) con default 1.000
            $table->decimal('escala', 6, 3)->default(1.000);
            
            // Tu SQL solo tiene created_at
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calibraciones');
    }
};