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
        Schema::create('work_orders', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('maintenance_request_id')->unique()->constrained('maintenance_requests')->cascadeOnDelete();
            $table->foreignId('appointment_id')->nullable()->constrained('appointments')->nullOnDelete();
            $table->foreignId('technician_id')->constrained('technicians')->restrictOnDelete();
            $table->string('status', 30)->default('open');
            $table->text('diagnosis')->nullable();
            $table->text('work_notes')->nullable();
            $table->json('before_photos')->nullable();
            $table->json('after_photos')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['technician_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('work_orders');
    }
};
