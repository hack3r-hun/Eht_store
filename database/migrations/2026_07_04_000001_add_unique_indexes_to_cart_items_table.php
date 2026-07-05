<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Merge duplicate rows (same owner + product) before adding the unique constraints.
        $duplicates = DB::table('cart_items')
            ->select('user_id', 'session_id', 'product_id')
            ->selectRaw('MIN(id) as keep_id')
            ->selectRaw('SUM(quantity) as total_quantity')
            ->groupBy('user_id', 'session_id', 'product_id')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicates as $duplicate) {
            DB::table('cart_items')
                ->where('id', $duplicate->keep_id)
                ->update(['quantity' => $duplicate->total_quantity]);

            DB::table('cart_items')
                ->where('product_id', $duplicate->product_id)
                ->where('id', '!=', $duplicate->keep_id)
                ->when(
                    $duplicate->user_id === null,
                    fn ($query) => $query->whereNull('user_id'),
                    fn ($query) => $query->where('user_id', $duplicate->user_id)
                )
                ->when(
                    $duplicate->session_id === null,
                    fn ($query) => $query->whereNull('session_id'),
                    fn ($query) => $query->where('session_id', $duplicate->session_id)
                )
                ->delete();
        }

        Schema::table('cart_items', function (Blueprint $table) {
            $table->unique(['user_id', 'product_id']);
            $table->unique(['session_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::table('cart_items', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'product_id']);
            $table->dropUnique(['session_id', 'product_id']);
        });
    }
};
