<?php

use App\Enums\KpiPeriodType;
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
        if (Schema::hasTable('kpi_periods')) {
            return;
        }

        Schema::create('kpi_periods', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('type', KpiPeriodType::values())->index();
            $table->unsignedTinyInteger('month')->nullable()->index();
            $table->unsignedSmallInteger('year')->index();
            $table->date('starts_at')->index();
            $table->date('ends_at')->index();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kpi_periods');
    }
};
