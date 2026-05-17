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
        Schema::table('users', function (Blueprint $table) {
            $table->string('employee_code')->nullable()->unique()->after('email');
            $table->string('whatsapp')->nullable()->after('employee_code');
            $table->string('phone')->nullable()->after('whatsapp');
            $table->foreignId('division_id')
                ->nullable()
                ->after('phone')
                ->constrained()
                ->nullOnDelete();
            $table->foreignId('department_id')
                ->nullable()
                ->after('division_id')
                ->constrained()
                ->nullOnDelete();
            $table->foreignId('position_id')
                ->nullable()
                ->after('department_id')
                ->constrained()
                ->nullOnDelete();
            $table->foreignId('supervisor_id')
                ->nullable()
                ->after('position_id')
                ->constrained('users')
                ->nullOnDelete();
            $table->string('employment_status')->nullable()->after('supervisor_id');
            $table->date('joined_at')->nullable()->after('employment_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('supervisor_id');
            $table->dropConstrainedForeignId('position_id');
            $table->dropConstrainedForeignId('department_id');
            $table->dropConstrainedForeignId('division_id');
            $table->dropUnique('users_employee_code_unique');
            $table->dropColumn([
                'employee_code',
                'whatsapp',
                'phone',
                'employment_status',
                'joined_at',
            ]);
        });
    }
};
