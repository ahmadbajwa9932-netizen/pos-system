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
    Schema::create('sales', function (Blueprint $table) {
        $table->id();
        $table->string('voucher_no')->unique();
        $table->foreignId('customer_id')->nullable()->constrained('customers')->onDelete('set null');
        $table->enum('payment_type', ['cash', 'credit', 'card']);
        $table->decimal('subtotal', 10, 2);
        $table->enum('discount_type', ['amount', 'percentage'])->default('amount');
        $table->decimal('discount', 10, 2)->default(0);
        $table->decimal('discount_amount', 10, 2)->default(0);
        $table->decimal('tax', 10, 2)->default(0);
        $table->decimal('grand_total', 10, 2);
        $table->decimal('received_amount', 10, 2)->default(0);
        $table->decimal('change_amount', 10, 2)->default(0);
        $table->decimal('remaining_balance', 10, 2)->default(0);
        $table->date('due_date')->nullable();
        $table->string('status')->default('paid'); // paid, unpaid, partial
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
        Schema::dropIfExists('sales');
    }
};
