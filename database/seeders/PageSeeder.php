<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

class PageSeeder extends Seeder
{
    public function run(): void
    {
        Page::create([
            'slug' => 'about',
            'title' => 'About Us',
            'content' => '<p>EK Yarn Co. creates handcrafted crochet and knitted products made with care, creativity, and quality yarns. Every piece is thoughtfully handmade with attention to detail, from playful amigurumi and daily accessories to cozy wearables and personalized gifts.</p><p>Based in Karachi, we work with customers who want something unique, useful, cute, or meaningful. Ready-made pieces can be ordered online, and custom ideas can be discussed for colors, size, quantity, and delivery timing.</p><p>We offer local and nationwide delivery, bringing handmade yarn creations to customers across Pakistan.</p>',
            'meta' => [
                'hero_title' => 'Handmade Yarn Creations With Personality',
                'hero_subtitle' => 'Crochet, knit, accessories, cozy wearables, and custom gifts made with care in Karachi.',
            ],
        ]);

        Page::create([
            'slug' => 'home',
            'title' => 'Home',
            'content' => null,
            'meta' => [
                'hero_title' => 'Handcrafted Crochet & Knit Pieces Made With Care',
                'hero_subtitle' => 'Cute amigurumi, keychains, coasters, scrunchies, wallets, headbands, cozy wearables, and custom gifts made with quality yarns.',
                'hero_cta' => 'Shop Handmade',
            ],
        ]);
    }
}
