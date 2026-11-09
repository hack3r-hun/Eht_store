<?php

use App\Support\ContactNormalizer;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Normalised contact details copied onto the order so guests (no account) are
            // recognised across orders and can be reached for offers.
            $table->string('contact_name')->nullable();
            $table->string('contact_email')->nullable()->index();
            $table->string('contact_phone', 20)->nullable()->index();
            // Existing customers never agreed to marketing, so this starts false.
            $table->boolean('marketing_opt_in')->default(false);
        });

        DB::table('orders')
            ->leftJoin('users', 'users.id', '=', 'orders.user_id')
            ->select('orders.id', 'orders.shipping_address', 'users.email as user_email', 'users.name as user_name', 'users.phone as user_phone')
            ->orderBy('orders.id')
            ->chunk(200, function ($orders) {
                foreach ($orders as $order) {
                    $address = json_decode((string) $order->shipping_address, true) ?: [];

                    DB::table('orders')->where('id', $order->id)->update([
                        'contact_name' => $address['full_name'] ?? $order->user_name,
                        'contact_email' => ContactNormalizer::email($order->user_email ?: ($address['email'] ?? null)),
                        'contact_phone' => ContactNormalizer::phone($address['phone'] ?? $order->user_phone),
                    ]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['contact_name', 'contact_email', 'contact_phone', 'marketing_opt_in']);
        });
    }
};
