<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_proposal', function (Blueprint $table) {
            $table->timestamp('timeout')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('user_proposal', function (Blueprint $table) {
            $table->dropColumn('timeout');
        });
    }
};
