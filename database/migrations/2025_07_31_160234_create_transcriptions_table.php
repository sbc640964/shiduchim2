<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('transcriptions', function (Blueprint $table) {
            $table->id();
            $table->string('status');
            $table->json('data')->nullable();
            $table->unsignedTinyInteger('current_step');
            $table->unsignedTinyInteger('total_steps');
            $table->timestamps();
        });

        Schema::table('calls', function (Blueprint $table) {
            $table->foreignId('transcription_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transcriptions');
    }
};
