<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('people', function (Blueprint $table) {
            $table->foreignId('father_id')->nullable()->constrained('people')->nullOnDelete();
            $table->foreignId('mother_id')->nullable()->constrained('people')->nullOnDelete();
            $table->foreignId('spouse_id')->nullable()->constrained('people')->nullOnDelete();
            $table->foreignId('father_in_law_id')->nullable()->constrained('people')->nullOnDelete();
            $table->foreignId('mother_in_law_id')->nullable()->constrained('people')->nullOnDelete();
            $table->foreignId('current_family_id')->nullable()->constrained('families')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('people', function (Blueprint $table) {
            $table->dropForeign([
                'father_id',
                'mother_id',
                'spouse_id',
                'father_in_law_id',
                'mother_in_law_id',
                'current_family_id',
            ]);

            $table->dropColumn([
                'father_id',
                'mother_id',
                'spouse_id',
                'father_in_law_id',
                'mother_in_law_id',
                'current_family_id',
            ]);
        });
    }
};
