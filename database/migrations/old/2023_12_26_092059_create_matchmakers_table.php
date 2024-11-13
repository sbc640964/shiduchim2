<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('matchmakers', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
            $table->foreignId('person_id')->constrained('people');
            $table->string('phone');
            $table->string('phone2')->nullable();
            $table->string('not')->nullable();
            $table->integer('level')->default(1);
            $table->foreignId('created_by')->constrained('users');
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::table('proposals', function (Blueprint $table) {
            $table->dropForeign(['person_id']);
            $table->dropForeign(['created_by']);
        });
        Schema::dropIfExists('matchmakers');
    }
};
