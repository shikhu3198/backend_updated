<?php

namespace App\Http\Controllers\API;


use App\Models\Food;
use App\Models\Category;
use App\Repositories\FoodRepository;
use App\Repositories\RestaurantRepository;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use InfyOm\Generator\Criteria\LimitOffsetCriteria;
use Prettus\Repository\Criteria\RequestCriteria;
use Illuminate\Support\Facades\Response;
use Prettus\Repository\Exceptions\RepositoryException;
use Flash;

/**
 * Class FoodController
 * @package App\Http\Controllers\API
 */

class FoodCategoryAPIController extends Controller
{
    /** @var  FoodRepository */
    private $foodRepository;

    private $restaurantRepository;

    public function __construct(FoodRepository $foodRepo, RestaurantRepository $restaurantRepo)
    {
        $this->foodRepository = $foodRepo;
        $this->restaurantRepository = $restaurantRepo;
    }

    /**
     * Display a listing of the Food.
     * GET|HEAD /foods
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $res_category_id = $request->input('res_category_id');
        $restaurant_id = $request->input('restaurant_id');

        $category_foods = Category::select('categories.*')->with('foods');
            
        $category_foods = $category_foods->orderBy('categories.id')->get();

        $Arr = $category_foods->toArray();

        foreach ($category_foods as $key => $value) {
            
            $relationData = $value->foods()->get();            
            foreach ($relationData as $rkey => $rvalue) {

            $data = Food::select('foods.*')
                ->join('restaurants','foods.restaurant_id','=','restaurants.id')->where('foods.category_id',$value->id);
                if(isset($res_category_id) && $res_category_id != ''){
                    $data = $data->where('restaurants.res_category_id',$res_category_id);
                }
                if(isset($restaurant_id) && $restaurant_id != ''){
                    $data = $data->where('restaurants.id',$restaurant_id);
                }
                $data = $data->get();
                if(count($data) == '0')
                {
                    $Arr[$key]['foods'] = array();    
                }
                else
                {
                    $Arr[$key]['foods'] = $data;
                }
            }
            
        }
        
        if (count($Arr) == 0 || empty($Arr)) {
            return $this->sendError('Category not found');
        }

        return $this->sendResponse($Arr, 'Categories retrieved successfully');
    }

    /**
     * Display the specified Food.
     * GET|HEAD /foods/{id}
     *
     * @param  int $id
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, $id)
    {
        /** @var Food $food */
        if (!empty($this->foodRepository)) {
            try{
                $this->foodRepository->pushCriteria(new RequestCriteria($request));
                $this->foodRepository->pushCriteria(new LimitOffsetCriteria($request));
            } catch (RepositoryException $e) {
                Flash::error($e->getMessage());
            }
            $category_foods = Category::select('categories.*')->with('foods');
            if(isset($category_id) && $category_id != ''){
                    $category_foods = $category_foods->where('categories.id',$category_id);
                }
        $category_foods = $category_foods->where('categories.id',$id)->orderBy('categories.id')->get();
            
        }
            
        if (count($category_foods) == 0 || empty($category_foods)) {
            return $this->sendError('Categories not found');
        }

        return $this->sendResponse($category_foods->toArray(), 'Food retrieved successfully');
    }

}
