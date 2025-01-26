<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('proposals', function (Blueprint $table) {
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->string('reason_closed')->nullable();
        });
    }

    public function down(): void

    {
        Schema::table('proposals', function (Blueprint $table) {
            $table->dropColumn('opened_at');
            $table->dropColumn('closed_at');
            $table->dropColumn('reason_closed');
        });
    }
};
