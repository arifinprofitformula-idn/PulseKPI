<?php

use App\Enums\KpiApprovalAction;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('kpi_approvals')) {
            return;
        }

        Schema::create('kpi_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kpi_assessment_id')
                ->constrained('kpi_assessments')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->foreignId('actor_id')
                ->nullable()
                ->constrained('users')
                ->cascadeOnUpdate()
                ->nullOnDelete();
            $table->enum('action', KpiApprovalAction::values())->index();
            $table->string('from_status')->nullable();
            $table->string('to_status');
            $table->text('notes')->nullable();
            $table->timestamp('acted_at')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kpi_approvals');
    }
};
