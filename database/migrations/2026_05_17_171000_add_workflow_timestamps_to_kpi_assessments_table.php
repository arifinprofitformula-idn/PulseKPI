<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('kpi_assessments')) {
            return;
        }

        Schema::table('kpi_assessments', function (Blueprint $table) {
            $table->timestamp('reviewed_at')->nullable()->after('submitted_at')->index();
            $table->timestamp('approved_at')->nullable()->after('reviewed_at')->index();
            $table->timestamp('rejected_at')->nullable()->after('approved_at')->index();
            $table->timestamp('locked_at')->nullable()->after('rejected_at')->index();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('kpi_assessments')) {
            return;
        }

        Schema::table('kpi_assessments', function (Blueprint $table) {
            $table->dropColumn([
                'reviewed_at',
                'approved_at',
                'rejected_at',
                'locked_at',
            ]);
        });
    }
};
