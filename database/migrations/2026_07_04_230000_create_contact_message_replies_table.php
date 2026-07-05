<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contact_message_replies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contact_message_id')->constrained()->cascadeOnDelete();
            $table->foreignId('admin_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('message');
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['contact_message_id', 'created_at']);
        });

        Schema::table('contact_messages', function (Blueprint $table) {
            $table->timestamp('last_replied_at')->nullable()->after('is_read');
            $table->foreignId('last_replied_by')->nullable()->after('last_replied_at')->constrained('users')->nullOnDelete();
            $table->index(['email', 'created_at']);
            $table->index('last_replied_at');
        });
    }

    public function down(): void
    {
        Schema::table('contact_messages', function (Blueprint $table) {
            $table->dropForeign(['last_replied_by']);
            $table->dropIndex(['email', 'created_at']);
            $table->dropIndex(['last_replied_at']);
            $table->dropColumn(['last_replied_at', 'last_replied_by']);
        });

        Schema::dropIfExists('contact_message_replies');
    }
};
