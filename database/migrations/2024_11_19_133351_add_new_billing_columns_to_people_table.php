<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('people', function (Blueprint $table) {
            $table->timestamp('billing_start_date')->nullable();
            $table->foreignId('billing_referrer_id')->nullable()->constrained('people');
            $table->string('billing_notes')->nullable();
            $table->boolean('billing_published')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('people', function (Blueprint $table) {
            $table->dropColumn('billing_start_date');
            $table->dropConstrainedForeignId('billing_referrer_id');
            $table->dropColumn('billing_notes');
            $table->dropColumn('billing_published');
        });
    }
};
