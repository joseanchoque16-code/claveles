<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lecturas', function (Blueprint $table) {
            $table->id();
            
            // FK a la tabla 'sensores'
            $table->foreignId('sensor_id')->constrained('sensores')->onDelete('cascade');
            
            $table->double('valor');
            // 'registrado_en' se define como timestamp con DEFAULT current_timestamp()
            $table->timestamp('registrado_en')->useCurrent();
            
            // Nota: En este esquema, no se usa $table->timestamps()
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lecturas');
    }
};