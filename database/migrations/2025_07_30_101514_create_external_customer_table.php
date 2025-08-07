<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
        public function up()
    {
        Schema::create('external_customer', function (Blueprint $table) {
            $table->id(); // auto-increment primary key
            $table->string('name');
            $table->string('nic', 20);
            $table->string('phone_no', 15);
            $table->text('address')->nullable();
            $table->timestamps(); 
        });
    }

    public function down()
    {
        Schema::dropIfExists('external_customer');
    }

};
