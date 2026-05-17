<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kpi_report_exports', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('requested_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->foreignId('kpi_assessment_id')
                ->nullable()
                ->constrained('kpi_assessments')
                ->nullOnDelete();
            $table->string('type');
            $table->string('format');
            $table->string('status');
            $table->json('filters')->nullable();
            $table->string('file_path')->nullable();
            $table->string('file_name')->nullable();
            $table->string('disk')->default('local');
            $table->unsignedInteger('total_rows')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index('requested_by');
            $table->index('type');
            $table->index('format');
            $table->index('status');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kpi_report_exports');
    }
};
