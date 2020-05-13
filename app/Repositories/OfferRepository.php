<?php

namespace App\Repositories;

use App\Models\Offer;
use InfyOm\Generator\Common\BaseRepository;


class OfferRepository extends BaseRepository
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
        return Offer::class;
    }

    /**
     * get my foods
     **/
    public function myFoods(){
        return Offer::join("user_restaurants", "user_restaurants.restaurant_id", "=", "foods.restaurant_id")
            ->where('user_restaurants.user_id', auth()->id())->get();
    }
}
