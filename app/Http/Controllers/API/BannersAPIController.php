<?php

namespace App\Http\Controllers\API;


use App\Models\Banners;
use App\Repositories\BannersRepository;
use App\Repositories\RestaurantRepository;
use App\Repositories\FoodRepository;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use InfyOm\Generator\Criteria\LimitOffsetCriteria;
use Prettus\Repository\Criteria\RequestCriteria;
use Illuminate\Support\Facades\Response;
use Prettus\Repository\Exceptions\RepositoryException;
use Flash;

/**
 * Class CategoryController
 * @package App\Http\Controllers\API
 */

class BannersAPIController extends Controller
{
    /** @var  CategoryRepository */
    private $bannersRepository;

    private $restaurantRepository;

    private $foodRepository;

    public function __construct(BannersRepository $bannersRepo, RestaurantRepository $restaurantRepo, FoodRepository $foodRepo)
    {
        $this->bannersRepository = $bannersRepo;
        $this->restaurantRepository = $restaurantRepo;
        $this->foodRepository = $foodRepo;
    }

    /**
     * Display a listing of the Category.
     * GET|HEAD /categories
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $res_category_id = $request->input('res_category_id');

        try{
            $this->bannersRepository->pushCriteria(new RequestCriteria($request));
            $this->bannersRepository->pushCriteria(new LimitOffsetCriteria($request));
        } catch (RepositoryException $e) {
            Flash::error($e->getMessage());
        }
        
        $banners = $this->bannersRepository->all();

        if(isset($res_category_id) && $res_category_id != '')
        {                        
            foreach ($banners as $key => $value) {
                if($value->type_id == 1)
                {
                    $restaurant = $this->restaurantRepository->findByField(array("res_category_id"=>$res_category_id,"id"=>$value->redirect_url));

                    if(count($restaurant) > 0)
                    {
                        $banners[$key] = $value;
                    }
                    else
                    {
                        unset($banners[$key]);
                    }
                }
                else if($value->type_id == 3)
                {
                    $foods = $this->foodRepository->myFoods()->pluck('id','restaurant_id');   
                        
                    foreach ($foods as $key => $value) {

                        $food = $this->restaurantRepository->findByField(array("res_category_id"=>$res_category_id,"id"=>$value));

                        if(count($food) > 0)
                        {
                            $banners[$key] = $value;
                        }
                        else
                        {
                            unset($banners[$key]);
                        }
                    }
                }
            }            
        }

        
       /*$test = json_decode($banners);
       
       foreach ($test as $key => $value) {
           // $banners[$key]['banner_image'] = last($value->media);
           $banners[$key]['banner_image'] = last($value->media);
           $value->media = last($value->media);
           $data[] = $value;
       }*/
       
       if (empty($banners)) {
            return $this->sendError('Banners not found');
        }
        
       return $this->sendResponse($banners->toArray(), 'Banners retrieved successfully');
    }

    /**
     * Display the specified Category.
     * GET|HEAD /categories/{id}
     *
     * @param  int $id
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        /** @var Category $category */
        if (!empty($this->bannersRepository)) {
            $banners = $this->bannersRepository->findWithoutFail($id);
        }

        if (empty($banners)) {
            return $this->sendError('Banners not found');
        }

        return $this->sendResponse($banners->toArray(), 'Banners retrieved successfully');
    }
}
