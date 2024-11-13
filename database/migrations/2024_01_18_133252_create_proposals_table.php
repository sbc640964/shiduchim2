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

        Schema::create('proposals', function (Blueprint $table) {
            $table->id();
            $table->string('status');
            $table->string('sub_status')->nullable();
            $table->string('status_girl')->nullable();
            $table->string('status_guy')->nullable();
            $table->string('reason_status')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('matchmaker_id')->nullable()->constrained();
            $table->foreignId('offered_by')->nullable()->constrained('people');
            $table->foreignId('family_id')->nullable()->unique()->constrained();
            $table->foreignId('handling_by')->nullable()->constrained('users');
            $table->timestamps();
        });

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('proposals');
    }
};
