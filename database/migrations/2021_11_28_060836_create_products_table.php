<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('image')->nullable(); // image filename
            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('price', 8, 2); // 8 digits, 2 decimal places
            $table->text('contact_info');
            $table->date('expiry_date');
            $table->unsignedTinyInteger('thirty_days_discount'); // Discount percentage after 30 days left
            $table->unsignedTinyInteger('fifteen_days_discount'); // Discount percentage after 15 days left
            $table->unsignedInteger('views')->default(0);
            $table->integer('votes')->default(0);
            $table->foreignIdFor(User::class)->constrained();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('products');
    }
}
