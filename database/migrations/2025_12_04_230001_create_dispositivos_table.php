<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dispositivos', function (Blueprint $table) {
            $table->id(); // bigint(20) UNSIGNED PRIMARY KEY
            $table->string('nombre');
            $table->string('tipo');
            $table->integer('gpio_pin')->nullable();
            $table->boolean('estado')->default(false); // tinyint(1) con default 0
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dispositivos');
    }
};