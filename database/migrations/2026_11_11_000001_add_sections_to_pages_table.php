<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            // Ordered list of page-builder blocks. NULL means "use the built-in default layout".
            $table->json('sections')->nullable();
            $table->boolean('is_published')->default(true);
        });

        // Make every storefront page editable from the CMS.
        $titles = [
            'home' => 'Home',
            'about' => 'About Us',
            'products' => 'Products',
            'contact' => 'Contact Us',
            'custom-order' => 'Custom Order',
            'track-order' => 'Track Order',
            'policies' => 'Shipping, Returns & Care',
        ];

        foreach ($titles as $slug => $title) {
            if (! DB::table('pages')->where('slug', $slug)->exists()) {
                DB::table('pages')->insert([
                    'slug' => $slug,
                    'title' => $title,
                    'content' => null,
                    'meta' => json_encode([]),
                    'is_published' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->dropColumn(['sections', 'is_published']);
        });
    }
};
