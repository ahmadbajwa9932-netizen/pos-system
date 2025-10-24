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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->enum('customer_type', ['cash', 'credit','card'])->default('cash');
            $table->decimal('current_balance', 15, 2)->default(0);
            $table->string('shop_name')->nullable();
            $table->string('city')->nullable();
            $table->string('contact')->nullable();
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
        Schema::dropIfExists('customers');
    }
};
