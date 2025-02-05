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
        Schema::connection($this->getConnection())->disableForeignKeyConstraints();

        Schema::create('calls', function (Blueprint $table) {
            $table->id();
            $table->timestamp('started_at');
            $table->timestamp('finished_at');
            $table->bigInteger('extension')->comment('מספר השלוחה');
            $table->string('direction')->comment('נכנסת/יוצאת');
            $table->string('phone');
            $table->foreignId('phone_id')->nullable()->constrained();
            $table->string('audio_url')->nullable();
            $table->foreignId('diary_id')->nullable()->constrained();
            $table->boolean('is_pending')->default(true)->comment('ממתין להתאמה של המשתמש');
            $table->bigInteger('data_raw')->comment('המידע הגולמי שהתקבל מGIS');
            $table->timestamps();
        });

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('calls');
    }
};
