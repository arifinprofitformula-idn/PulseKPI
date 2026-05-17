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
        Schema::create('kpi_template_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('kpi_template_id')->index();
            $table->unsignedInteger('sort_order');
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('weight', 5, 2);
            $table->text('target_description');
            $table->string('data_source')->nullable();
            $table->boolean('is_required')->default(true);
            $table->timestamps();

            $table->foreign('kpi_template_id', 'fk_kpi_template_items_template')
                ->references('id')
                ->on('kpi_templates')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kpi_template_items');
    }
};
