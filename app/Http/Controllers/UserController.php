<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Suport\Facades\Auth;
use Illuminate\Support\Facades\Validator;

use Intervention\Image\Facades\Image;

use App\Models\User;
use App\Models\UserAppointment;
use App\Models\UserFavorite;
use App\Models\Restaurant;
use App\Models\RestaurantServices;

class UserController extends Controller
{
    private $loggedUser;

    public function __construct() {
        $this->middleware('auth:api');
        $this->loggedUser = auth()->user();
    }

    public function read() {
        $array = ['error' => ''];

        $info = $this->loggedUser;
        $info['avatar'] = url('media/avatars/'.$info['avatar']);
        $array['data'] = $info;

        return  $array;
    }

    public function addFavorite(Request $request) {
        $array = ['error' => ''];

        $id_restaurant = $request->input('restaurant');

        $restaurant = Restaurant::find($id_restaurant);

        if($restaurant) {
            $fav = UserFavorite::select()
                ->where('id_user', $this->loggedUser->id)
                ->where('id_restaurant', $id_restaurant)
            ->first();

            if($fav) {
                //remover
                $fav->delete();
                $array['have'] = false;
            } else {
                //adicionar
                $newFav = new UserFavorite();
                $newFav->id_user = $this->loggedUser->id;
                $newFav->id_restaurant = $id_restaurant;
                $newFav->save();
                $array['have'] = true;
            }
        } else {
            $array['error'] = 'Restaurante inexistente!';
        }
        return $array;
    }

    public function getFavorites() {
        $array = ['error' => '', 'list' =>[]];

        $favs = UserFavorite::select()
            ->where('id_user', $this->loggedUser->id)
        ->get();

        if($favs) {
            foreach($favs as $fav) {
                $restaurant = Restaurant::find($fav['id_restaurant']);
                $restaurant['avatar'] = url('media/avatars/'.$restaurant['avatar']);
                $array['list'][] = $restaurant;
            }
        }

        return $array;
    }

    public function getAppointments() {
        $array = ['error'=>'', 'list'=>[]];

        $apps = UserAppointment::select()
            ->where('id_user', $this->loggedUser->id)
            ->orderBy('ap_datetime', 'DESC')
        ->get();

        if($apps) {
            foreach($apps as $app) {
                $restaurant = Restaurant::find($app['id_restaurant']);
                $restaurant['avatar'] = url('media/avatars/'.$restaurant['avatar']);

                $service = RestaurantServices::find($app['id_service']);

                $array['list'][] = [
                    'id' => $app['id'],
                    'datetime' => $app['ap_datetime'],
                    'restaurant' => $restaurant,
                    'service' => $service
                ];
            }
        }

        return $array;
    }
}
