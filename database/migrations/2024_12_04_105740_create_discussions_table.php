<?php

use App\Models\Discussion;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('discussions', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Discussion::class, 'parent_id')->nullable()->constrained('discussions');
            $table->foreignIdFor(User::class)->constrained('users');
            $table->string('title')->nullable();
            $table->longText('content');
            $table->boolean('is_popup')->default(false);
            $table->text('image_hero')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('discussions');
        Schema::enableForeignKeyConstraints();
    }
};
