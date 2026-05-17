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
        if (Schema::hasTable('kpi_score_rules')) {
            return;
        }

        Schema::create('kpi_score_rules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('kpi_template_item_id')->index();
            $table->unsignedTinyInteger('score');
            $table->string('label');
            $table->decimal('min_value', 8, 2)->nullable();
            $table->decimal('max_value', 8, 2)->nullable();
            $table->text('description')->nullable();
            $table->timestamps();

            $table->unique(['kpi_template_item_id', 'score']);

            $table->foreign('kpi_template_item_id', 'fk_kpi_score_rules_template_item')
                ->references('id')
                ->on('kpi_template_items')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kpi_score_rules');
    }
};
