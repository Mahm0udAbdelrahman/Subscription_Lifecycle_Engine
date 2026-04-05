<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained('plans')->restrictOnDelete();
            $table->foreignId('plan_price_id')->constrained('plan_prices')->restrictOnDelete();
            $table->string('status');          
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('starts_at');
            $table->timestamp('ends_at')->nullable();
            $table->timestamp('canceled_at')->nullable();
            $table->timestamp('grace_period_ends_at')->nullable();
            $table->decimal('price', 10, 2);
            $table->string('currency', 3);
            $table->string('billing_cycle');
            $table->timestamps();

            $table->index(['status', 'trial_ends_at']);
            $table->index(['status', 'grace_period_ends_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
