<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableCustomer extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('customers') && 1==2) {
            Schema::create('customers', function(Blueprint $table) {
               $table->increments('id');
               $table->string('name')->nullable();
               $table->string('email')->nullable();
               $table->string('password')->nullable();
               $table->enum('status', ['active', 'inactive'])->default('active');
               $table->string('token')->nullable();
               $table->timestamps();

               $table->index(['email']);
               $table->index(['token']);
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('customers');
    }
}
