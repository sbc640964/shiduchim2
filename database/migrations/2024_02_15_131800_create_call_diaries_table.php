<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('call_diaries', function (Blueprint $table) {
            $table->id();
            $table->string('event');
            $table->string('call_id');
            $table->enum('direction', ['in', 'out']);
            $table->string('from');
            $table->string('to');
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('person_id')->constrained()->cascadeOnDelete();
            $table->foreignId('phone_id')->constrained()->cascadeOnDelete();
            $table->string('extension')->nullable();
            $table->json('data');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('call_diaries');
    }
};
