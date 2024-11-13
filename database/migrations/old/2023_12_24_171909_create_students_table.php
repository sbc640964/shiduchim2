<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('father_id')->nullable();
            $table->foreignId('city_id')->constrained();
            $table->string('father_name')->nullable();
            $table->string('mother_name')->nullable();
            $table->foreignId('parent_father_id')->nullable()->constrained('people');
            $table->foreignId('parent_mother_id')->nullable()->constrained('people');
            $table->string('parent_father_name')->nullable();
            $table->string('parent_mother_name')->nullable();
            $table->string('address')->nullable();
            $table->foreignId('prev_school_id')->nullable()->constrained('schools');
            $table->foreignId('school_id')->nullable()->constrained();
            $table->string('class')->nullable();
            $table->unsignedInteger('class_serial_in_school')->nullable();
            $table->foreignId('father_synagogue_id')->nullable()->constrained('synagogues');
            $table->date('birthday')->nullable();
            $table->string('gender');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
