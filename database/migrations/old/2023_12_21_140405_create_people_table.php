<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('people', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('ichud_id')->nullable();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('telephone')->nullable();
            $table->string('phone_number')->nullable();
            $table->string('email')->nullable();
            $table->string('address')->nullable();
            $table->foreignId('city_id')->nullable()->constrained();
            $table->string('country')->nullable();
            $table->timestamp('birthday')->nullable();
            $table->foreignId('father_id')->nullable()->constrained('people');
            $table->foreignId('father_in_law_id')->nullable()->constrained('people');
            $table->string('father_name')->nullable();
            $table->string('father_in_law_name')->nullable();
            $table->timestamps();

            $table->index('ichud_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('people');
    }
};
