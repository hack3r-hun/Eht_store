<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('product_variants')) {
            Schema::create('product_variants', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_id')->constrained()->cascadeOnDelete();
                $table->string('size', 10);
                $table->string('sku')->nullable()->unique();
                $table->decimal('price', 10, 2);
                $table->decimal('sale_price', 10, 2)->nullable();
                $table->unsignedInteger('stock_quantity')->default(0);
                $table->boolean('is_active')->default(true);
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->timestamps();

                $table->unique(['product_id', 'size']);
                $table->index(['product_id', 'is_active']);
            });
        }

        if (! Schema::hasColumn('cart_items', 'product_variant_id')) {
            // MySQL may use the (user_id, product_id) unique index to support the user_id FK.
            // Add a dedicated user_id index first so the unique can be dropped safely.
            $this->ensureIndex('cart_items', 'cart_items_user_id_index', ['user_id']);
            $this->dropIndexIfExists('cart_items', 'cart_items_user_id_product_id_unique');
            $this->dropIndexIfExists('cart_items', 'cart_items_session_id_product_id_unique');

            Schema::table('cart_items', function (Blueprint $table) {
                $table->foreignId('product_variant_id')
                    ->nullable()
                    ->after('product_id')
                    ->constrained('product_variants')
                    ->nullOnDelete();

                $table->unique(['user_id', 'product_variant_id']);
                $table->unique(['session_id', 'product_variant_id']);
            });
        }

        if (! Schema::hasColumn('order_items', 'size')) {
            Schema::table('order_items', function (Blueprint $table) {
                $table->string('size', 10)->nullable()->after('product_sku');
            });
        }

        if (! Schema::hasColumn('order_items', 'product_variant_id')) {
            Schema::table('order_items', function (Blueprint $table) {
                $table->foreignId('product_variant_id')
                    ->nullable()
                    ->after('product_id')
                    ->constrained('product_variants')
                    ->nullOnDelete();
            });
        }

        $sizeOrder = ['S' => 1, 'M' => 2, 'L' => 3, 'XL' => 4];

        DB::table('products')->orderBy('id')->chunkById(100, function ($products) use ($sizeOrder) {
            foreach ($products as $product) {
                $existing = DB::table('product_variants')
                    ->where('product_id', $product->id)
                    ->where('size', 'M')
                    ->value('id');

                if ($existing) {
                    $variantId = $existing;
                } else {
                    $variantId = DB::table('product_variants')->insertGetId([
                        'product_id' => $product->id,
                        'size' => 'M',
                        'sku' => $product->sku.'-M',
                        'price' => $product->price,
                        'sale_price' => $product->sale_price,
                        'stock_quantity' => $product->stock_quantity,
                        'is_active' => true,
                        'sort_order' => $sizeOrder['M'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                DB::table('cart_items')
                    ->where('product_id', $product->id)
                    ->whereNull('product_variant_id')
                    ->update(['product_variant_id' => $variantId]);
            }
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('order_items', 'product_variant_id')) {
            Schema::table('order_items', function (Blueprint $table) {
                $table->dropConstrainedForeignId('product_variant_id');
            });
        }

        if (Schema::hasColumn('order_items', 'size')) {
            Schema::table('order_items', function (Blueprint $table) {
                $table->dropColumn('size');
            });
        }

        if (Schema::hasColumn('cart_items', 'product_variant_id')) {
            $this->dropIndexIfExists('cart_items', 'cart_items_user_id_product_variant_id_unique');
            $this->dropIndexIfExists('cart_items', 'cart_items_session_id_product_variant_id_unique');

            Schema::table('cart_items', function (Blueprint $table) {
                $table->dropConstrainedForeignId('product_variant_id');
            });

            Schema::table('cart_items', function (Blueprint $table) {
                $table->unique(['user_id', 'product_id']);
                $table->unique(['session_id', 'product_id']);
            });
        }

        Schema::dropIfExists('product_variants');
    }

    protected function ensureIndex(string $table, string $indexName, array $columns): void
    {
        $exists = collect(Schema::getIndexes($table))->contains(fn (array $index) => ($index['name'] ?? '') === $indexName);

        if ($exists) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($columns, $indexName) {
            $blueprint->index($columns, $indexName);
        });
    }

    protected function dropIndexIfExists(string $table, string $indexName): void
    {
        $exists = collect(Schema::getIndexes($table))->contains(fn (array $index) => ($index['name'] ?? '') === $indexName);

        if (! $exists) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($indexName) {
            $blueprint->dropIndex($indexName);
        });
    }
};
