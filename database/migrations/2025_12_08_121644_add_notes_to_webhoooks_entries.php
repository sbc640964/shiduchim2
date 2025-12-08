<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('webhook_entries', function (Blueprint $table) {
            $table->json('notes')->default(DB::raw('(JSON_ARRAY())'))->after('error');
        });
    }

    public function down(): void
    {
        Schema::table('webhook_entries', function (Blueprint $table) {
            $table->dropColumn('notes');
        });
    }
};
