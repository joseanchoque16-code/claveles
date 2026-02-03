<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('programaciones', function (Blueprint $table) {
            $table->id();

            // FK a dispositivos
            $table->foreignId('dispositivo_id')->constrained('dispositivos')->onDelete('cascade');
            
            // tipo enum('pasto','croquetas','agua') DEFAULT NULL
            $table->enum('tipo', ['pasto', 'croquetas', 'agua'])->nullable();
            
            // dias set('lun','mar','mie','jue','vie','sab','dom') DEFAULT NULL
            // Usamos string para almacenar el set de días (e.g., "lun,mar,mie")
            $table->string('dias')->nullable();
            
            // hora time NOT NULL
            $table->time('hora');

            // duracion_seg int(10) UNSIGNED NOT NULL DEFAULT 10
            $table->unsignedInteger('duracion_seg')->default(10);
            
            // activo tinyint(1) DEFAULT 1 (Laravel usa boolean para tinyint(1))
            $table->boolean('activo')->default(true);
            
            // fecha date DEFAULT NULL
            $table->date('fecha')->nullable();
            
            // habilitado tinyint(1) NOT NULL DEFAULT 1
            $table->boolean('habilitado')->default(true);
            
            // orden int(11) NOT NULL DEFAULT 0
            $table->integer('orden')->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('programaciones');
    }
};
