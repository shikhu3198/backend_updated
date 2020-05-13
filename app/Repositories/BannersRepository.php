<?php

namespace App\Repositories;

use App\Models\Banners;
use InfyOm\Generator\Common\BaseRepository;


class BannersRepository extends BaseRepository
{
    /**
     * @var array
     */
    protected $fieldSearchable = [
        'type',
        'redirect_url'
    ];

    /**
     * Configure the Model
     **/
    public function model()
    {
        return Banners::class;
    }

    /**
     * get my foods
     **/
    public function myFoods(){
        return Banners::join("user_restaurants", "user_restaurants.restaurant_id", "=", "foods.restaurant_id")
            ->where('user_restaurants.user_id', auth()->id())->get();
    }
}
