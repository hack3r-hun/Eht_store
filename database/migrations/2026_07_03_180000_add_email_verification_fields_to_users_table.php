<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('verification_method')->nullable()->after('email_verified_at');
            $table->string('email_otp')->nullable()->after('verification_method');
            $table->timestamp('email_otp_expires_at')->nullable()->after('email_otp');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['verification_method', 'email_otp', 'email_otp_expires_at']);
        });
    }
};
