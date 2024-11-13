<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('custom_notifications', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('body');
            $table->string('type');
            $table->string('status')->default('draft');
            $table->json('recipients');
            $table->json('data');
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->foreignId('user_id')->constrained();
            $table->timestamps();
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->foreignId('custom_notification_id')->nullable()->constrained();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('custom_notifications');

        Schema::table('notifications', function (Blueprint $table) {
            $table->dropForeign(['custom_notification_id']);
            $table->dropColumn('custom_notification_id');
        });
    }
};
