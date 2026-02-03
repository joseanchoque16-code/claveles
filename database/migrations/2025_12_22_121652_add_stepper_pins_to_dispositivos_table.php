<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    Schema::table('dispositivos', function (Blueprint $table) {
        $table->unsignedSmallInteger('gpio_step')->nullable()->after('gpio_pin');
        $table->unsignedSmallInteger('gpio_dir')->nullable()->after('gpio_step');

        // Parámetros útiles (podés ajustarlos desde la web)
        $table->unsignedInteger('steps_90')->nullable()->after('gpio_dir');     // ventana: cuántos steps = 90°
        $table->unsignedInteger('feed_steps')->nullable()->after('steps_90');  // comedero: dosis fija
        $table->tinyInteger('invert_dir')->default(0)->after('feed_steps');    // 0/1 por si gira al revés
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dispositivos', function (Blueprint $table) {
            //
        });
    }
};
