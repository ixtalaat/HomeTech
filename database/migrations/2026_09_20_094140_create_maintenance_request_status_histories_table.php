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
        Schema::create('maintenance_request_status_histories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('maintenance_request_id')->constrained('maintenance_requests', 'id', 'mrsh_request_fk')->cascadeOnDelete();
            $table->string('from_status', 50)->nullable();
            $table->string('status', 50);
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('reason')->nullable();
            $table->timestamps();

            $table->index('maintenance_request_id', 'mrsh_request_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maintenance_request_status_histories');
    }
};
