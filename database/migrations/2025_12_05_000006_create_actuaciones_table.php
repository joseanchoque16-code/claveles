<?php
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up(): void
    {
        // Asegúrate de que esta migración tenga un timestamp posterior a 'dispositivos', 'reglas', y 'programaciones'.
        Schema::create('actuaciones', function (Blueprint $table) {
            $table->id(); // bigint(20) UNSIGNED PRIMARY KEY
            
            // FK a dispositivos (Requerido)
            $table->foreignId('dispositivo_id')->constrained('dispositivos')->onDelete('cascade');
            
            $table->enum('origen', ['manual', 'automatico']); // 'manual' o 'automatico'
            
            // FK a reglas (Opcional)
            $table->foreignId('regla_id')->nullable()->constrained('reglas')->onDelete('set null');
            
            // FK a programaciones (Opcional)
            $table->foreignId('programacion_id')->nullable()->constrained('programaciones')->onDelete('set null');
            
            $table->tinyInteger('estado_anterior')->nullable(); // tinyint(4)
            $table->tinyInteger('estado_nuevo')->nullable(); // tinyint(4)

            // El campo motivo contiene un JSON con los detalles de la actuación.
            // En Laravel se usa ->json() o ->longText() si el motor MySQL lo requiere para CHECK(json_valid).
            $table->json('motivo')->nullable();
            
            // El campo created_at ya tiene DEFAULT current_timestamp() en tu SQL, 
            // pero en Laravel, si usas $table->timestamps(), Laravel maneja created_at y updated_at.
            // Dado que tu SQL solo tiene created_at, lo definimos explícitamente:
            $table->timestamp('created_at')->useCurrent();
            // Nota: Opcionalmente puedes agregar $table->timestamp('updated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('actuaciones');
    }
};