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
    Schema::create('iot_commands', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('dispositivo_id');
        $table->string('tipo', 50); // 'feed'
        $table->json('payload')->nullable(); // {"feed_steps":1200}
        $table->enum('estado', ['pending','acked','failed'])->default('pending');
        $table->string('nonce', 40)->unique(); // para evitar repetición
        $table->timestamp('acked_at')->nullable();
        $table->timestamps();

        $table->foreign('dispositivo_id')->references('id')->on('dispositivos')->onDelete('cascade');
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('iot_commands');
    }
};
