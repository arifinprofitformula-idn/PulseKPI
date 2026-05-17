<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('kpi_assessment_items')) {
            return;
        }

        Schema::create('kpi_assessment_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kpi_assessment_id')
                ->constrained('kpi_assessments')
                ->cascadeOnUpdate()
                ->cascadeOnDelete()
                ->index();
            $table->foreignId('kpi_template_item_id')
                ->constrained('kpi_template_items')
                ->cascadeOnUpdate()
                ->restrictOnDelete()
                ->index();
            $table->decimal('actual_value', 12, 2)->nullable();
            $table->unsignedTinyInteger('score')->default(0);
            $table->decimal('weighted_score', 8, 2)->default(0);
            $table->text('evidence_note')->nullable();
            $table->string('evidence_file_path')->nullable();
            $table->string('evidence_original_name')->nullable();
            $table->string('evidence_mime_type')->nullable();
            $table->unsignedInteger('evidence_size')->nullable();
            $table->timestamps();

            $table->unique(
                ['kpi_assessment_id', 'kpi_template_item_id'],
                'kpi_assessment_items_assessment_template_item_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kpi_assessment_items');
    }
};
