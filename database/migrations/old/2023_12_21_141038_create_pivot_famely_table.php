<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pivot_family', function (Blueprint $table) {
            $table->id();
            $table->foreignId('person_id')->constrained('people')->cascadeOnDelete();
            $table->foreignId('relation_id')->constrained('people')->cascadeOnDelete();
            $table->string('relation');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::table('pivot_family', function (Blueprint $table) {
            $table->dropForeign(['person_id']);
            $table->dropForeign(['relation_id']);
        });
        Schema::dropIfExists('pivot_family');
    }
};
