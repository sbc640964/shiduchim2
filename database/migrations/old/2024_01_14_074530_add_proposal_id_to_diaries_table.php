<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('diaries', function (Blueprint $table) {
            $table->foreignId('proposal_id')->nullable()->constrained('proposals');
        });
    }

    public function down(): void
    {
        Schema::table('diaries', function (Blueprint $table) {
            $table->dropForeign(['proposal_id']);
            $table->dropColumn('proposal_id');
        });
    }
};
