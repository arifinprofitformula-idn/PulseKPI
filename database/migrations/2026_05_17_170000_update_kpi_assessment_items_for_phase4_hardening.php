<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kpi_assessment_items', function (Blueprint $table) {
            $table->string('template_item_name')->after('kpi_template_item_id');
            $table->text('template_item_description')->nullable()->after('template_item_name');
            $table->decimal('template_item_weight', 5, 2)->default(0)->after('template_item_description');
            $table->text('template_item_target_description')->after('template_item_weight');
            $table->string('template_item_data_source')->nullable()->after('template_item_target_description');
            $table->boolean('template_item_is_required')->default(true)->after('template_item_data_source');
            $table->unsignedTinyInteger('score')->nullable()->default(null)->change();
        });
    }

    public function down(): void
    {
        Schema::table('kpi_assessment_items', function (Blueprint $table) {
            $table->unsignedTinyInteger('score')->default(0)->change();
            $table->dropColumn([
                'template_item_name',
                'template_item_description',
                'template_item_weight',
                'template_item_target_description',
                'template_item_data_source',
                'template_item_is_required',
            ]);
        });
    }
};
