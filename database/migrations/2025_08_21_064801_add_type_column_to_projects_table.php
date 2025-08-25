<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->enum('External/Internal', ['External', 'Internal'])
                  ->default('Internal')
                  ->after('type')
                  ->comment('Specifies whether the project is External or Internal');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('External/Internal');
        });
    }
};
