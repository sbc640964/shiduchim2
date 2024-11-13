<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->morphs('model');
            $table->string('name');
            $table->string('phone_number');
            $table->string('email')->nullable();
            $table->string('address')->nullable();
            $table->foreignId('person_id')->nullable()->constrained()->nullOnDelete();
            $table->text('not')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('model_relation_type')->nullable();
            $table->string('filter')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->dropForeign(['person_id']);
            $table->dropForeign(['created_by']);
        });
        Schema::dropIfExists('contacts');
    }
};
