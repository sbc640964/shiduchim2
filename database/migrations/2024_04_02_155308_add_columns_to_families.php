<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('families', function (Blueprint $table) {
            $table->foreignId('proposal_id')->nullable()->constrained('proposals');
            $table->foreignId('matchmaker_id')->nullable()->constrained('matchmakers');
        });
    }

    public function down(): void
    {
        Schema::table('families', function (Blueprint $table) {
            //
        });
    }
};
