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

        Schema::create('matchmakers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('person_id')->constrained();
            $table->bigInteger('level')->default(1);
            $table->foreignId('created_by')->constrained('users');
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('matchmakers');
    }
};
