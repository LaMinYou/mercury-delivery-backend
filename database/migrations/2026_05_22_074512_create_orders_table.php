<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->foreignId('restaurant_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('customer_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('rider_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->foreignId('payment_id')->constrained('payment_methods')->onDelete('cascade');
            $table->string('order_type');
            $table->decimal('dest_latitude', 10, 8);
            $table->decimal('dest_longitude', 10, 8);
            $table->string('dest_address');
            $table->decimal('total_price', 12, 2);
            $table->decimal('delivery_fee', 12, 2);
            $table->decimal('service_fee', 12, 2);
            $table->string('payment_status')->default('pending');
            $table->string('delivery_status')->default('pending');
            $table->string('order_status')->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
