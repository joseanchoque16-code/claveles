<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reglas', function (Blueprint $table) {
            $table->id();

            // FK a sensores
            $table->foreignId('sensor_id')->constrained('sensores')->onDelete('cascade');
            
            // tipo enum('mañana','noche') NOT NULL
            $table->enum('tipo', ['mañana', 'noche']);
            
            // valor_min double NOT NULL
            $table->double('valor_min');
            
            // valor_max double NOT NULL
            $table->double('valor_max');
            
            // hysteresis decimal(5,2) DEFAULT NULL
            $table->decimal('hysteresis', 5, 2)->nullable();
            
            // hold_seconds int(11) DEFAULT NULL
            $table->integer('hold_seconds')->nullable();
            
            // accion enum('encender','apagar') NOT NULL
            $table->enum('accion', ['encender', 'apagar']);

            $table->time('hora_inicio')->nullable();
            $table->time('hora_fin')->nullable();
            
            // dias set('lun','mar','mie','jue','vie','sab','dom') DEFAULT NULL
            // Usamos string para almacenar el set de días
            $table->string('dias')->nullable();
            
            $table->integer('orden')->default(0);
            $table->boolean('habilitado')->default(true); // tinyint(1)
            
            // histeresis_on decimal(8,2) DEFAULT NULL
            $table->decimal('histeresis_on', 8, 2)->nullable();
            
            // histeresis_off decimal(8,2) DEFAULT NULL
            $table->decimal('histeresis_off', 8, 2)->nullable();
            
            // FK a dispositivos
            $table->foreignId('dispositivo_id')->constrained('dispositivos')->onDelete('cascade');
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reglas');
    }
};