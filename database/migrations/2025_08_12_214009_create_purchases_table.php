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
        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->string('product_name');
            $table->decimal('purchased_price', 10, 2);     //purchased price
            $table->decimal('sold_price', 10, 2);
            $table->decimal('previous_sold_price', 10, 2)->nullable();
            $table->decimal('quantity', 10, 2);
            $table->string('unit')->default('pcs'); 
            $table->integer('sold_quantity')->default(0);
            $table->date('purchase_date');
            $table->foreignId('supplier_id')->constrained('suppliers')->onDelete('cascade');
            $table->foreignId('category_id')->nullable()->constrained('categories')->OnDelete('set null');
            // soft deletes
        $table->softDeletes();
            $table->timestamps();
            // ✅ Add indexes for faster searching/filtering
            $table->index('product_name');
            $table->index('sold_price');
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropIndex(['product_name']);
            $table->dropIndex(['sold_price']);
        });
        Schema::dropIfExists('purchases');

    }
};
