<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::create('diaries', function (Blueprint $table) {
            $table->id();
            $table->nullableMorphs('model');
            $table->foreignId('proposal_id')->nullable()->constrained();
            $table->json('data')->comment('מכיל את תוכן התיעוד למשל התמונה או התיאור או סיכום שיחה עם הקלטה וכו');
            $table->string('type');
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->timestamps();
        });

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('diaries');
    }
};
