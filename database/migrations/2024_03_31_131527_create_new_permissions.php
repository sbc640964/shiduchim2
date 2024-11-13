<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;

return new class extends Migration {
    public function up(): void
    {
        Permission::createOrFirst([
            'guard_name' => 'web',
            'name' => 'update_death'
        ]);

        Permission::createOrFirst([
            'guard_name' => 'web',
            'name' => 'update_divorce'
        ]);

        Permission::createOrFirst([
            'guard_name' => 'web',
            'name' => 'export_proposals'
        ]);
    }

    public function down(): void
    {
    }
};
