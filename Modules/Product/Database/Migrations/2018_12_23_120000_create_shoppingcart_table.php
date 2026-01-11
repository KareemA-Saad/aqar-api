<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateShoppingcartTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
         if(!Schema::hasTable(config('cart.database.table') ?? 'shoppingcart')){  
             
            Schema::create(config('cart.database.table') ?? 'shoppingcart', function (Blueprint $table) {
                $table->string('identifier');
                $table->string('instance');
                $table->longText('content');
                $table->nullableTimestamps();
    
                $table->primary(['identifier', 'instance']);
            });
         }
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::drop(config('cart.database.table'));
    }
}
