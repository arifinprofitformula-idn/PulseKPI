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
            $table->foreignId('kpi_template_id')
                ->constrained()
                ->cascadeOnUpdate()
                ->cascadeOnDelete()
                ->index();
            $table->unsignedInteger('sort_order');
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('weight', 5, 2);
            $table->text('target_description');
            $table->string('data_source')->nullable();
            $table->boolean('is_required')->default(true);
            $table->timestamps();
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
