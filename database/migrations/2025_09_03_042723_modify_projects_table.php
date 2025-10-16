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
        Schema::table('projects', function (Blueprint $table) {
            // Make service_years_in_agreement nullable
            $table->integer('service_years_in_agreement')->nullable()->change();
            
            // Make service_rounds_in_agreement nullable
            $table->integer('service_rounds_in_agreement')->nullable()->change();
            
            // Remove installed_date_ocp column
            $table->dropColumn('installed_date_ocp');
            
            // Add is_hold column as boolean with default false (0)
            $table->boolean('is_hold')->default(false)->after('remarks');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            // Reverse the changes
            $table->integer('service_years_in_agreement')->nullable(false)->change();
            $table->integer('service_rounds_in_agreement')->nullable(false)->change();
            $table->date('installed_date_ocp')->nullable();
            $table->dropColumn('is_hold');
        });
    }
};