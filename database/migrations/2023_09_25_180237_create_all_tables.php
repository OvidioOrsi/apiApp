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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('avatar')->default('default.png');
            $table->string('email')->unique();
            $table->string('password');
        });
        Schema::create('userfavorites', function (Blueprint $table) {
            $table->id();
            $table->integer('id_user');
            $table->integer('id_restaurant');
        });
        Schema::create('userappointments', function (Blueprint $table) {
            $table->id();
            $table->integer('id_user');
            $table->integer('id_restaurant');
            $table->datetime('ap_datetime');        
        });
        Schema::create('restaurants', function (Blueprint $table) {
            $table->id();
            $table->string('name');  
            $table->string('avatar')->default('default.png'); 
            $table->float('stars')->default(0); 
            $table->string('latitude')->nullable();
            $table->string('longitude')->nullable();
        });
        Schema::create('restaurantphotos', function (Blueprint $table) {
            $table->id();
            $table->integer('id_restaurant');  
            $table->string('url'); 
        });
        Schema::create('restaurantreviews', function (Blueprint $table) {
            $table->id();
            $table->integer('id_restaurant');  
            $table->float('rate'); 
        });
        Schema::create('restaurantservices', function (Blueprint $table) {
            $table->id();
            $table->integer('id_restaurant');  
            $table->string('name'); 
            $table->integer('qtd');
        });
        Schema::create('restauranttestimonials', function (Blueprint $table) {
            $table->id();
            $table->integer('id_restaurant');  
            $table->string('name'); 
            $table->float('rate');
            $table->string('body');
        });
        Schema::create('restaurantavailability', function (Blueprint $table) {
            $table->id();
            $table->integer('id_restaurant');  
            $table->integer('weekday'); 
            $table->text('hours');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('userfavorites');
        Schema::dropIfExists('userappointments');
        Schema::dropIfExists('restaurants');
        Schema::dropIfExists('restaurantphotos');
        Schema::dropIfExists('restaurantreviews');
        Schema::dropIfExists('restaurantservices');
        Schema::dropIfExists('restauranttestimonials');
        Schema::dropIfExists('restaurantavailability');
    }
};
