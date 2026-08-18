<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                  ->constrained('users')
                  ->cascadeOnDelete();

            $table->string('title', 150);
            $table->text('message');

            // success | warning | danger | info  (maps to Bootstrap colours)
            $table->enum('type', ['success', 'warning', 'danger', 'info'])
                  ->default('info');

            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'is_read'], 'notifications_user_read_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
