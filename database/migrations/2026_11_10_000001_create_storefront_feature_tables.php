<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->unsignedTinyInteger('rating');
            $table->string('title')->nullable();
            $table->text('body');
            $table->boolean('is_verified')->default(false);
            $table->boolean('is_approved')->default(false);
            $table->timestamps();

            $table->index(['product_id', 'is_approved']);
        });

        Schema::create('custom_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('email');
            $table->string('phone', 30)->nullable();
            $table->string('item_type');
            $table->string('colors')->nullable();
            $table->string('size')->nullable();
            $table->string('budget')->nullable();
            $table->date('needed_by')->nullable();
            $table->text('description');
            $table->string('reference_image')->nullable();
            $table->string('status')->default('new');
            $table->decimal('quote_amount', 10, 2)->nullable();
            $table->text('admin_notes')->nullable();
            $table->timestamps();

            $table->index('status');
        });

        Schema::create('wishlist_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('session_id')->nullable();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'product_id']);
            $table->unique(['session_id', 'product_id']);
        });

        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('type')->default('percent'); // percent | fixed
            $table->decimal('value', 10, 2);
            $table->decimal('min_subtotal', 10, 2)->default(0);
            $table->unsignedInteger('max_uses')->nullable();
            $table->unsignedInteger('used_count')->default(0);
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('stock_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('email');
            $table->timestamp('notified_at')->nullable();
            $table->timestamps();

            $table->index(['product_id', 'notified_at']);
        });

        Schema::create('newsletter_subscribers', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->timestamps();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('discount', 10, 2)->default(0);
            $table->string('coupon_code')->nullable();
            $table->boolean('gift_wrap')->default(false);
            $table->decimal('gift_wrap_fee', 10, 2)->default(0);
            $table->text('gift_message')->nullable();
            $table->boolean('gift_hide_prices')->default(false);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->unsignedSmallInteger('lead_time_days')->nullable();
        });

        DB::table('coupons')->insert([
            'code' => 'WELCOME10',
            'type' => 'percent',
            'value' => 10,
            'min_subtotal' => 1000,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::table('products', fn (Blueprint $t) => $t->dropColumn('lead_time_days'));
        Schema::table('orders', fn (Blueprint $t) => $t->dropColumn(['discount', 'coupon_code', 'gift_wrap', 'gift_wrap_fee', 'gift_message', 'gift_hide_prices']));
        Schema::dropIfExists('newsletter_subscribers');
        Schema::dropIfExists('stock_alerts');
        Schema::dropIfExists('coupons');
        Schema::dropIfExists('wishlist_items');
        Schema::dropIfExists('custom_orders');
        Schema::dropIfExists('reviews');
    }
};
