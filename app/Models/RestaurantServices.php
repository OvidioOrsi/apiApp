<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RestaurantServices extends Model
{
    use HasFactory;

    protected $table = 'restaurantservices';
    public $timestamps = false;
}
