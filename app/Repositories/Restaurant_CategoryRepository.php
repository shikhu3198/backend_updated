<?php

namespace App\Repositories;

use App\Models\Restautant_category;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InfyOm\Generator\Common\BaseRepository;

/**
 * Class CategoryRepository
 * @package App\Repositories
 * @version August 29, 2019, 9:38 pm UTC
 *
 * @method Category findWithoutFail($id, $columns = ['*'])
 * @method Category find($id, $columns = ['*'])
 * @method Category first($columns = ['*'])
*/
class Restaurant_CategoryRepository extends BaseRepository
{
    /**
     * @var array
     */
    protected $fieldSearchable = [
        'name'
    ];

    /**
     * Configure the Model
     **/
    public function model()
    {
        return Restautant_category::class;
    }

    public function myRestaurants_category()
    {
        return Restautant_category::join("user_restaurants", "restaurant_id", "=", "restaurant_categories.id")->where('user_restaurants.user_id', auth()->id())->get();
    }
}
