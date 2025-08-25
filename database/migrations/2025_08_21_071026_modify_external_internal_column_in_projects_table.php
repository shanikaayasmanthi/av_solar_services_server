<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
 public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            // First drop the existing column
            $table->dropColumn('External/Internal');
        });

        Schema::table('projects', function (Blueprint $table) {
            // Then add it back without the comment
            $table->enum('External/Internal', ['External', 'Internal'])
                  ->default('Internal')
                  ->after('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            // Drop the column without comment
            $table->dropColumn('External/Internal');
        });

        Schema::table('projects', function (Blueprint $table) {
            // Add it back with the comment (for rollback)
            $table->enum('External/Internal', ['External', 'Internal'])
                  ->default('Internal')
                  ->after('type')
                  ->comment('Specifies whether the project is External or Internal');
        });
    }
};
