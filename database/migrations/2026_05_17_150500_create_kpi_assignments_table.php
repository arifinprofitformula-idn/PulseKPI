<?php

use App\Enums\KpiAssignmentStatus;
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
        if (Schema::hasTable('kpi_assignments')) {
            return;
        }

        Schema::create('kpi_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kpi_period_id')
                ->constrained('kpi_periods')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->foreignId('kpi_template_id')
                ->constrained('kpi_templates')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->foreignId('employee_id')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->foreignId('assigned_by')
                ->nullable()
                ->constrained('users')
                ->cascadeOnUpdate()
                ->nullOnDelete();
            $table->enum('status', KpiAssignmentStatus::values())->default(KpiAssignmentStatus::DRAFT->value)->index();
            $table->timestamp('assigned_at')->nullable()->index();
            $table->timestamp('cancelled_at')->nullable()->index();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['kpi_period_id', 'employee_id'], 'kpi_assignments_period_employee_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kpi_assignments');
    }
};
