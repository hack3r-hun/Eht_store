<?php

use Database\Seeders\MediaSeeder;
use Illuminate\Database\Migrations\Migration;

// Data fix: source.unsplash.com was shut down, so every seeded image URL in
// categories.image and product_images.path is dead. Re-running MediaSeeder
// (idempotent updateOrCreate) rewrites them to the working images.unsplash.com
// links in config/media.php. Runs as a migration so production is fixed on deploy.
return new class extends Migration
{
    public function up(): void
    {
        (new MediaSeeder)->run();
    }

    public function down(): void
    {
        // Data fix only; nothing to revert.
    }
};
