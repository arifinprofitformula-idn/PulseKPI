<?php

use App\Enums\KpiAssessmentStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('kpi_assessments')) {
            return;
        }

        Schema::create('kpi_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kpi_assignment_id')
                ->constrained('kpi_assignments')
                ->cascadeOnUpdate()
                ->cascadeOnDelete()
                ->unique();
            $table->foreignId('employee_id')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->foreignId('assessor_id')
                ->nullable()
                ->constrained('users')
                ->cascadeOnUpdate()
                ->nullOnDelete();
            $table->enum('status', KpiAssessmentStatus::values())
                ->default(KpiAssessmentStatus::DRAFT->value)
                ->index();
            $table->decimal('kpi_score', 8, 2)->default(0);
            $table->decimal('attendance_score', 8, 2)->default(100);
            $table->decimal('attendance_deduction', 8, 2)->default(0);
            $table->decimal('final_score', 8, 2)->default(0);
            $table->string('grade')->nullable();
            $table->timestamp('submitted_at')->nullable()->index();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kpi_assessments');
    }
};
