<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('kpi_attendance_adjustments')) {
            return;
        }

        Schema::create('kpi_attendance_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kpi_assessment_id')
                ->constrained('kpi_assessments')
                ->cascadeOnUpdate()
                ->cascadeOnDelete()
                ->unique();
            $table->unsignedTinyInteger('working_days')->default(26);
            $table->unsignedTinyInteger('sick_days')->default(0);
            $table->unsignedTinyInteger('permission_days')->default(0);
            $table->unsignedTinyInteger('absent_days')->default(0);
            $table->unsignedTinyInteger('leave_days')->default(0);
            $table->decimal('deduction_score', 8, 2)->default(0);
            $table->decimal('attendance_score', 8, 2)->default(100);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kpi_attendance_adjustments');
    }
};
