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
        Schema::create('sale_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained('sales')->onDelete('cascade');
    $table->foreignId('purchase_id')->constrained('purchases')->onDelete('cascade'); // linking to purchases
    $table->integer('quantity');
    $table->decimal('price', 10, 2); // this will usually come from purchases table but stored for record

    // Discount fields
    $table->enum('discount_type', ['amount', 'percentage'])->default('amount'); // per product
    $table->decimal('discount_value', 10, 2)->default(0); // raw value entered (amount or %)
    $table->decimal('discount_amount', 10, 2)->default(0); // calculated discount in amount

    $table->decimal('total', 10, 2); // price * quantity
    $table->decimal('total_after_discount', 10, 2); // total - discount_amount
    // soft deletes
    $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sale_items');
    }
};
