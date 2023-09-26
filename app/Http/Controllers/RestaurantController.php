<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Suport\Facades\Auth;

use App\Models\User;
use App\Models\UserAppointment;
use App\Models\UserFavorite;
use App\Models\Restaurant;
use App\Models\RestaurantPhotos;
use App\Models\RestaurantServices;
use App\Models\RestaurantTestimonial;
use App\Models\RestaurantAvailability;

class RestaurantController extends Controller
{
    private $loggedUser;

    public function __construct() {
        $this->middleware('auth:api');
        $this->loggedUser = auth()->user();
    }

    /*
    public function createRandom() {
        $array = ['Error' => ''];
        
        for($q=0;$q<10;$q++) {
            $firstNames = ['Comida', 'Lanche', 'Pizza', 'Alimentação'];
            $lastNames = ['Bom', 'Saúdavel', 'Fitness', 'Maromba'];

            $mesas = ['Mesa Pequena', 'Mesa Média', 'Mesa Grande', 'Mesa Gigante'];
            $mesasQtd = ['1', '2', '3', '4'];

            $depos = [
                'Lorem Ipsum is simply dummy text of the printing and', 'typesetting industry. Lorem Ipsum has been the industrys', 'standard dummy text ever since the 1500s, when an unknow', 'printer took a galley of type and scrambled it to make a', 'type specimen book It has survived not only five',
            ];

            $newRestaurant = new Restaurant();
            $newRestaurant->name = $firstNames[rand(0, count($firstNames)-1)].' '.$lastNames[rand(0, count($lastNames)-1)];
            $newRestaurant->avatar = rand(1, 4).'.png';
            $newRestaurant->stars = rand(2, 4).'.'.rand(0, 9);
            $newRestaurant->latitude = '-23.5'.rand(0,9).'30907';
            $newRestaurant->longitude = '-46.6'.rand(0,9).'82795';
            $newRestaurant->save();

            $ns = rand(3, 6);

            for($w=0;$w<4;$w++) {
                $newRestaurantPhoto = new RestaurantPhotos();
                $newRestaurantPhoto->id_restaurant = $newRestaurant->id;
                $newRestaurantPhoto->url = rand(1, 5).'.png';
                $newRestaurantPhoto->save();
            }

            for($w=0;$w<4;$w++) {
                $newRestaurantService = new RestaurantServices();
                $newRestaurantService->id_restaurant = $newRestaurant->id;
                $newRestaurantService->name = $mesas[rand(0, count($mesas)-1)];
                $newRestaurantService->qtd = $mesasQtd[rand(0, count($mesasQtd)-1)];
                $newRestaurantService->save();
            }

            for($w=0;$w<4;$w++) {
                $newRestaurantTestimonial = new RestaurantTestimonial();
                $newRestaurantTestimonial->id_restaurant = $newRestaurant->id;
                $newRestaurantTestimonial->name = $firstNames[rand(0, count($firstNames)-1)].' '.$lastNames[rand(0, count($lastNames)-1)];
                $newRestaurantTestimonial->rate = rand(2, 4).'.'.rand(0,9);
                $newRestaurantTestimonial->body = $depos[rand(0, count($depos)-1)];
                $newRestaurantTestimonial->save();
            }

            for($e=0;$e<4;$e++) {
                $rAdd = rand(7, 10);
                $hours = [];
                for($r=0;$r<8;$r++) {
                    $time = $r + $rAdd;
                    if ($time < 10) {
                        $time = '0'.$time;
                    }
                    $hours[] = $time.':00';
                }
                $newRestaurantAvail = new RestaurantAvailability();
                $newRestaurantAvail-> id_restaurant = $newRestaurant->id;
                $newRestaurantAvail-> weekday = $e;
                $newRestaurantAvail->hours = implode(',', $hours);
                $newRestaurantAvail->save();
            }
        }

        return $array;
    }
    */

    private function searchGeo($address) {
        $key = env('MAPS_KEY', null);

        $address = urlencode($address);
        $url = "https://maps.googleapis.com/maps/api/geocode/json?address=".$address.'&key='.$key;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $res = curl_exec($ch);
        curl_close($ch);

        return json_decode($res, true);
    }

    public function list(Request $request) {
        $array = ['error' => ''];

        $lat = $request->input('lat');
        $lng = $request->input('lng');
        $city = $request->input('city');
        $offset = $request->input('offset');
        if(!$offset) {
            $offset = 0;
        }

        if(!empty($city)) {
            $res = $this->searchGeo($city);

            if(count($res['results']) > 0) {
                $lat = $res['results'][0]['geometry']['location']['lat'];
                $lng = $res['results'][0]['geometry']['location']['lng'];
            }
        } elseif(!empty($lat) && !empty($lng)) {
            $res = $this->searchGeo($lat.','.$lng);

            if(count($res['results']) > 0) {
                $city = $res['results'][0]['formatted_address'];
            }
        } else {
            $lat = '-23.2927';
            $lng = '-51.1732';
            $city = 'Londrina';
        }

        $restaurants = Restaurant::select(Restaurant::raw('*, SQRT(POW(69.1 * (latitude - '.$lat.'), 2) + POW(69.1 * ('.$lng.' - longitude) * COS(latitude / 57.3), 2)) AS distance'))
        ->havingRaw('distance < ?', [30])
        ->orderBy('distance', 'ASC')
        ->offset($offset)
        ->limit(5)
        ->get();

        foreach($restaurants as $rkey => $rvalue) {
            $restaurants[$rkey]['avatar'] = url('media/avatars/'.$restaurants[$rkey]['avatar']);

        }

        $array['data'] = $restaurants;
        $array['loc'] = 'Sâo Paulo';

        return $array;
    }

    public function one($id) {
        $array = ['error' => ''];

        $restaurant = Restaurant::find($id);

        if($restaurant) {
            $restaurant['avatar'] = url('media/avatars/'.$restaurant['avatar']);
            $restaurant['favorited'] = false;
            $restaurant['photos'] = [];
            $restaurant['services'] = [];
            $restaurant['testimonials'] = [];
            $restaurant['available'] = [];

            // verificando favorito
            $cFavorite = UserFavorite::where('id_user', $this->loggedUser->id)
                ->where('id_restaurant', $restaurant->id)
                ->count();
            if ($cFavorite > 0) {
                $restaurant['favorited'] = true;
            }

            // fotos do restaurante
            $restaurant['photos'] = RestaurantPhotos::select(['id', 'url'])->where('id_restaurant', $restaurant->id)->get();
            foreach($restaurant['photos'] as $rkey => $rvalue) {
                $restaurant['photos'][$rkey]['url'] = url('media/uploads/'.$restaurant['photos'][$rkey]['url']);
            }

            // mesas do restaurante
            $restaurant['services'] = RestaurantServices::select(['id', 'name', 'qtd'])->where('id_restaurant', $restaurant->id)->get();

            // depoimento do restaurante
            $restaurant['testimonials'] = RestaurantTestimonial::select(['id', 'name', 'rate', 'body'])->where('id_restaurant', $restaurant->id)->get();

            // Pegando disponibilidade do Restaurante
            $availability = [];

            // - Pegando a disponibilidade crua
            $avails = restaurantAvailability::where('id_restaurant', $restaurant->id)->get();
            $availWeekdays = [];
            foreach($avails as $item) {
                $availWeekdays[$item['weekday']] = explode(',', $item['hours']);
            }

            // - Pegar os agendamentos dos próximos 20 dias
            $appointments = [];
            $appQuery = UserAppointment::where('id_restaurant', $restaurant->id)
                ->whereBetween('ap_datetime', [
                    date('Y-m-d').' 00:00:00',
                    date('Y-m-d', strtotime('+20 days')).' 23:59:59'
                ])
                ->get();
            foreach($appQuery as $appItem) {
                $appointments[] = $appItem['ap_datetime'];
            }

            // - Gerar disponibilidade real
            for($q=0;$q<20;$q++) {
                $timeItem = strtotime('+'.$q.' days');
                $weekday = date('w', $timeItem);

                if(in_array($weekday, array_keys($availWeekdays))) {
                    $hours = [];

                    $dayItem = date('Y-m-d', $timeItem);

                    foreach($availWeekdays[$weekday] as $hourItem) {
                        $dayFormated = $dayItem.' '.$hourItem.':00';
                        if(!in_array($dayFormated, $appointments)) {
                            $hours[] = $hourItem;
                        }
                    }

                    if(count($hours) > 0) {
                        $availability[] = [
                            'date' => $dayItem,
                            'hours' => $hours
                        ];
                    }

                }
            }

            $restaurant['available'] = $availability;

            $array['data'] = $restaurant;

        } else {
            $array['error'] = 'Restaurante não existe.';
            return $array;
        }

        return $array;
    }

    public function setAppointment($id, Request $request) {
        // service, year, month, day, hour
        $array = ['error' => ''];

        $service = $request->input('service');
        $year = intval($request->input('year'));
        $month = intval($request->input('month'));
        $day = intval($request->input('day'));
        $hour = intval($request->input('hour'));

        $month = ($month < 10) ? '0'.$month : $month;
        $day = ($day < 10) ? '0'.$day : $day;
        $hour = ($hour < 10) ? '0'.$hour : $hour;

        // 1 verificar se o serviço do restaurante existe
        $restaurantservice = RestaurantServices::select()
            ->where('id', $service)
            ->where('id_restaurant', $id)
        ->first();

        if($restaurantservice) {
            // 2 verificar se a data é real
            $apDate = $year.'-'.$month.'-'.$day.' '.$hour.':00:00';
            if(strtotime($apDate) > 0) {
                // 3 verificar se o restaurante já possui agendamento nesse dia e hora
                $apps = UserAppointment::select()
                    ->where('id_restaurant', $id)
                    ->where('ap_datetime', $apDate)
                ->count();
                if($apps === 0) {
                    // 4 verificar se o restaurante atende nessas datas
                    $weekday = date('w', strtotime($apDate));
                    $avail = RestaurantAvailability::select()
                        ->where('id_restaurant', $id)
                        ->where('weekday', $weekday)
                    ->first();
                    if($avail) {
                        // 4.2 verificar se o restaurante atende nesta hora
                        $hours = explode(',', $avail['hours']);
                        if(in_array($hour.':00', $hours)) {
                            // 5 fazer o agendamento
                            $newApp = new UserAppointment();
                            $newApp->id_user = $this->loggedUser->id;
                            $newApp->id_restaurant = $id;
                            $newApp->id_service = $service;
                            $newApp->ap_datetime = $apDate;
                            $newApp->save();
                        } else {
                            $array['error'] = 'Restaurante não atende nesta hora';
                        }
                    } else {
                        $array['error'] = 'Restaurante não atende neste dia';
                    }
                } else {
                    $array['error'] = 'Restaurante já possui agendamento neste dia/hora';
                }
            } else {
                $array['error'] = 'Data Inválida!';
            }
        } else {
            $array['error'] = 'Serviço Inexistente!';
        }
        return $array;
    }

    public function search(Request $request) {
        $array = ['error' => '', 'list'=> []];

        $q = $request->input('q');

        if($q) {
            $restaurants = Restaurant::select()
                ->where('name', 'LIKE', '%'.$q.'%')
            ->get();

            foreach($restaurants as $rkey => $restaurant) {
                $restaurants[$rkey]['avatar'] = url('media/avatars/'.$restaurants[$rkey]['avatar']);
            }

            $array['list'] = $restaurants;
        } else {
            $array['error'] = 'Digite algo para buscar';
        }
        return $array;
    }
}
