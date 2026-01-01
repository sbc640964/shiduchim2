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
        Schema::create('notes', function (Blueprint $table) {
            $table->id();
            $table->nullableMorphs('documentable');

            $table->foreignId('owner_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->string('visibility')->default('private');
            $table->string('category')->nullable();
            $table->longText('content');

            $table->foreignId('call_id')
                ->nullable()
                ->constrained('calls')
                ->nullOnDelete();

            $table->timestamps();

            $table->index(['visibility', 'owner_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notes');
    }
};
