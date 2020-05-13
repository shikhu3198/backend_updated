<?php

namespace App\Repositories;

use App\Models\Restaurant;
use App\Models\Food;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InfyOm\Generator\Common\BaseRepository;

/**
 * Class RestaurantRepository
 * @package App\Repositories
 * @version August 29, 2019, 9:38 pm UTC
 *
 * @method Restaurant findWithoutFail($id, $columns = ['*'])
 * @method Restaurant find($id, $columns = ['*'])
 * @method Restaurant first($columns = ['*'])
 */
class RestaurantRepository extends BaseRepository
{
    /**
     * @var array
     */
    protected $fieldSearchable = [
        'name',
        'description',
        'address',
        'latitude',
        'longitude',
        'phone',
        'mobile',
        'information',
        'delivery_fee',
        'admin_commission',
    ];

    /**
     * Configure the Model
     **/
    public function model()
    {
        return Restaurant::class;
    }

    /**
     * get my restaurants
     */

    public function myRestaurants()
    {
        return Restaurant::join("user_restaurants", "restaurant_id", "=", "restaurants.id")
            ->where('user_restaurants.user_id', auth()->id())->get();
    }

    public function near($myLon, $myLat, $areaLon, $areaLat, $res_category_id,$search='')
    {
        // $this->applyCriteria();
        // $this->applyScope();

        $results = $this->model->select(DB::raw("SQRT(
            POW(69.1 * (restaurants.latitude - " . $myLat . "), 2) +
            POW(69.1 * (" . $myLon . " - restaurants.longitude) * COS(restaurants.latitude / 57.3), 2)) AS distance, SQRT(
            POW(69.1 * (restaurants.latitude - " . $areaLat . "), 2) +
            POW(69.1 * (" . $areaLon . " - restaurants.longitude) * COS(restaurants.latitude / 57.3), 2)) AS area"))
                    ->join('foods','foods.restaurant_id','=','restaurants.id');
        
        if(isset($res_category_id) && $res_category_id != '')
        {
            $results = $results->where('restaurants.res_category_id',$res_category_id);
        }
         if(isset($search) && $search != '')
        {
            // $results = $results->where('restaurants.name', 'LIKE', "%".$search."%")
            //                     ->orWhere('foods.name', 'LIKE', "%".$search."%");

            $results = $results->where(function($query) use ($search){
                $query->where('restaurants.name', 'LIKE', "%".$search."%")
                ->orWhere('foods.name', 'LIKE', "%".$search."%");
            });
        }

        $results = $results->groupBy('restaurants.id');
        $results = $results->orderBy('distance','asc');
        $results = $results->get();
        
        if($search != '' && $res_category_id != '')
        {
            $results2 = $this->searchDetails($res_category_id,$search);
            
            foreach ($results2 as $key => $value) {
                
                $final = array();
                if($value['type'] == 2)
                {
                    $result[$key]['type'] = $value['type'];
                    $result[$key]['id'] = $value['id'];
                    $result[$key]['name'] = $value['name'];
                    $result[$key]['media'] = $value['media'];
                }
                else if($value['type'] == 1)
                {
                    $result[$key]['type'] = $value['type'];
                    $result[$key]['id'] = $value['id'];
                    $result[$key]['name'] = $value['name'];     
                    $result[$key]['media'] = $value['media'];
                }
                else
                {
                    $result[$key]['type'] = $value['type'];
                    $result[$key]['id'] = $value['id'];
                    $result[$key]['name'] = $value['name']; 
                    $result[$key]['media'] = $value['media'];
                    $result[$key]['type'] = $value['type'];
                    $result[$key]['id'] = $value['id'];
                    $result[$key]['name'] = $value['name'];
                    $result[$key]['media'] = $value['media'];
                }
                $final = $result;
            }               
        }
        /*$this->resetModel();
        $this->resetScope();*/
        
        return $this->parserResult($final);
    }

    public function nearwithoutsearch($myLon, $myLat, $areaLon, $areaLat, $res_category_id)
    {
        $results = $this->model->select(DB::raw("SQRT(
            POW(69.1 * (restaurants.latitude - " . $myLat . "), 2) +
            POW(69.1 * (" . $myLon . " - restaurants.longitude) * COS(restaurants.latitude / 57.3), 2)) AS distance, SQRT(
            POW(69.1 * (restaurants.latitude - " . $areaLat . "), 2) +
            POW(69.1 * (" . $areaLon . " - restaurants.longitude) * COS(restaurants.latitude / 57.3), 2)) AS area"), "restaurants.*")
                    ->join('foods','foods.restaurant_id','=','restaurants.id');
        
        if(isset($res_category_id) && $res_category_id != '')
        {
            $results = $results->where('restaurants.res_category_id',$res_category_id);
        }
        
        $results = $results->groupBy('restaurants.id');
        $results = $results->orderBy('distance','asc');
        $results = $results->get();
        $this->resetModel();
        $this->resetScope();

        return $this->parserResult($results);
    }

    // public function near($myLon, $myLat, $areaLon, $areaLat, $res_category_id)
    // {
    //     $this->applyCriteria();
    //     $this->applyScope();

    //     $results = $this->model->select(DB::raw("SQRT(
    //         POW(69.1 * (latitude - " . $myLat . "), 2) +
    //         POW(69.1 * (" . $myLon . " - longitude) * COS(latitude / 57.3), 2)) AS distance, SQRT(
    //         POW(69.1 * (latitude - " . $areaLat . "), 2) +
    //         POW(69.1 * (" . $areaLon . " - longitude) * COS(latitude / 57.3), 2)) AS area"), "restaurants.*");
    //     if(isset($res_category_id) && $res_category_id != '')
    //     {
    //         $results = $results->where('restaurants.res_category_id',$res_category_id);
    //     }
    //     $results = $results->orderBy('distance','asc');
    //     $results = $results->get();
        
    //     $this->resetModel();
    //     $this->resetScope();

    //     return $this->parserResult($results);
    // }

    // 19-03-2020
    public function searchDetails($category_id,$searchData = ''){
        $restaurant = Restaurant::select('id','name')
                            ->where('res_category_id',$category_id)
                            ->where('name','like','%'.$searchData.'%')
                            ->groupBy('id')
                            ->orderBy('id');
        $restaurant = $restaurant->addSelect(DB::raw("'1' as type"))->get()->toArray();
        $food = Food::select('foods.*')
                            ->join('restaurants','foods.restaurant_id','=','restaurants.id')
                            ->where('res_category_id',$category_id)
                            ->where('foods.name','like','%'.$searchData.'%')
                            ->groupBy('foods.id')
                            ->orderBy('foods.id');
        $food = $food->addSelect(DB::raw("'2' as type"))->get()->toArray();

        if(count($restaurant) > 0 && count($food) > 0){
            return array_merge($restaurant,$food);
        }
        else if(count($restaurant) > 0){
            return $restaurant;
        } else{
            return $food;
        }                  
    }
    // 19-03-2020
}
