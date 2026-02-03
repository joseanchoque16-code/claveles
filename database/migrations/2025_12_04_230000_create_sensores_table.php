<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sensores', function (Blueprint $table) {
            $table->id();
            
            // orden int(11) NOT NULL DEFAULT 0
            $table->integer('orden')->default(0); 

            $table->string('nombre');
            $table->string('tipo');
            $table->integer('gpio_pin')->nullable();
            $table->integer('gpio_pin2')->nullable();
            $table->double('valor_actual')->default(0);
            $table->string('unidad', 10)->nullable();
            
            // icono varchar(255) DEFAULT NULL
            $table->string('icono')->nullable(); 

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sensores');
    }
};