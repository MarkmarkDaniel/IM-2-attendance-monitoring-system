<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance_sessions', function (Blueprint $table) {
            // The rotating QR token dies at this moment
            $table->timestamp('qr_token_expires_at')->nullable()->after('qr_token');
        });

        Schema::table('attendances', function (Blueprint $table) {
            // Audit trail - who scanned, from where, on what device
            $table->string('ip_address', 45)->nullable()->after('recorded_by');
            $table->string('user_agent', 255)->nullable()->after('ip_address');

            $table->index('ip_address', 'attendances_ip_index');
        });
    }

    public function down(): void
    {
        Schema::table('attendance_sessions', function (Blueprint $table) {
            $table->dropColumn('qr_token_expires_at');
        });

        Schema::table('attendances', function (Blueprint $table) {
            $table->dropIndex('attendances_ip_index');
            $table->dropColumn(['ip_address', 'user_agent']);
        });
    }
};
