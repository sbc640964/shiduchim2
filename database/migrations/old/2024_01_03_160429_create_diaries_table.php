<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Query\Expression;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('diaries', function (Blueprint $table) {
            $table->id();
            $table->string('type');
            $table->json('data')->default(new Expression('(JSON_OBJECT())'));
            $table->morphs('model');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->integer('confidentiality_level')->default(1);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('diaries');
    }
};
