<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proposals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('guy_id')->constrained('students');
            $table->foreignId('girl_id')->constrained('students');
            $table->string('status');
            $table->string('sub_status')->nullable();
            $table->string('status_boy')->nullable();
            $table->string('status_girl')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->string('reason_status')->nullable();
            $table->string('not')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('matchmaker_id')->nullable()->constrained('matchmakers');
            $table->foreignId('offered_by')->nullable()->constrained('people');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proposals');
    }
};
