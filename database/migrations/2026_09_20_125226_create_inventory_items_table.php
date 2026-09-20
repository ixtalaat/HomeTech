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
        Schema::create('inventory_items', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->unique();
            $table->string('sku')->nullable()->unique();
            $table->string('unit', 20)->default('pcs');
            $table->integer('current_stock')->default(0);
            $table->unsignedInteger('low_stock_threshold')->default(5);
            $table->decimal('unit_cost', 10, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_items');
    }
};
