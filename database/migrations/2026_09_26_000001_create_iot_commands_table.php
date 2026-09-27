<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('iot_commands', function (Blueprint $table) {
            $table->id();
            $table->uuid('command_id')->unique();
            $table->foreignId('dispositivo_id')->constrained('dispositivos')->cascadeOnDelete();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('accion', ['on', 'off']);
            $table->enum('status', ['pending', 'executed', 'rejected', 'failed', 'superseded', 'expired'])->default('pending');
            $table->json('resultado')->nullable();
            $table->timestamp('requested_at')->index();
            $table->timestamp('expires_at')->index();
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamps();

            $table->index(['dispositivo_id', 'status', 'requested_at'], 'iot_commands_device_status_requested_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('iot_commands');
    }
};
