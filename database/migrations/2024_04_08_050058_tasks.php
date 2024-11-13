<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Query\Expression;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->string('type')->default('regular');
            $table->text('description');
            $table->timestamp('due_date');
            $table->integer('priority')->default(0);
            $table->foreignId('proposal_id')->nullable()->constrained();
            $table->json('data')->default(new Expression('(JSON_OBJECT())'));
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('diary_completed_id')->nullable()->constrained('diaries');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['proposal_id']);
        });
        Schema::dropIfExists('tasks');
    }
};
