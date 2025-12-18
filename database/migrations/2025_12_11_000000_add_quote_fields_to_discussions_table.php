<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('discussions', function (Blueprint $table): void {
            $table->unsignedBigInteger('quoted_message_id')->nullable()->after('parent_id');
            $table->text('quoted_text')->nullable()->after('quoted_message_id');

            $table->foreign('quoted_message_id')
                ->references('id')
                ->on('discussions')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('discussions', function (Blueprint $table): void {
            $table->dropForeign(['quoted_message_id']);
            $table->dropColumn(['quoted_message_id', 'quoted_text']);
        });
    }
};

