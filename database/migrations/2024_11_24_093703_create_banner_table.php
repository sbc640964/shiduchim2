<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up()
    {
        // Deprecated migration kept for legacy databases.
        // The canonical banners table is created in 2025_11_06_110505_create_banners_table.php.
        // This no-op prevents duplicate table creation on fresh installs.

    }
};
