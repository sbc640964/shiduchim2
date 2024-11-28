<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('import_rows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('import_batch_id')->constrained('import_batches')->cascadeOnDelete();
            $table->json('data');
            $table->string('status')->default('pending');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->text('error')->nullable();
            $table->nullableMorphs('import_model');
            $table->string('import_model_state')->nullable();
            $table->json('error_stack')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::table('import_rows', function (Blueprint $table) {
            $table->dropForeign(['import_batch_id']);
        });
        Schema::dropIfExists('import_rows');
    }
};
