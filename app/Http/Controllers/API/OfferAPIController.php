<?php

namespace App\Http\Controllers\API;


use App\Models\Offer;
use App\Repositories\OfferRepository;
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

class OfferAPIController extends Controller
{
    /** @var  CategoryRepository */
    private $offerRepository;

    private $restaurantRepository;

    private $foodRepository;

    public function __construct(OfferRepository $offerRepo, RestaurantRepository $restaurantRepo, FoodRepository $foodRepo)
    {
        $this->offerRepository = $offerRepo;
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
            $this->offerRepository->pushCriteria(new RequestCriteria($request));
            $this->offerRepository->pushCriteria(new LimitOffsetCriteria($request));
        } catch (RepositoryException $e) {
            Flash::error($e->getMessage());
        }
        
        $offer = $this->offerRepository->where('is_active','1')->get();

        if(isset($res_category_id) && $res_category_id != '')
        {                        
            foreach ($offer as $key => $value) {
                if($value->type_id == 1)
                {
                    $restaurant = $this->restaurantRepository->findByField(array("res_category_id"=>$res_category_id,"id"=>$value->redirect_url));

                    if(count($restaurant) > 0)
                    {
                        $offer[$key] = $value;
                    }
                    else
                    {
                        unset($offer[$key]);
                    }
                }
                else if($value->type_id == 3)
                {
                    $foods = $this->foodRepository->myFoods()->pluck('id','restaurant_id');   
                        
                    foreach ($foods as $key => $value) {

                        $food = $this->restaurantRepository->findByField(array("res_category_id"=>$res_category_id,"id"=>$value));

                        if(count($food) > 0)
                        {
                            $offer[$key] = $value;
                        }
                        else
                        {
                            unset($offer[$key]);
                        }
                    }
                }
            }            
        }

        
       /*$test = json_decode($offer);
       
       foreach ($test as $key => $value) {
           // $offer[$key]['banner_image'] = last($value->media);
           // $offer[$key]['banner_image'] = last($value->media);
           // $value->banner_image = last($value->media);
           $data[] = $value;
       }*/
       
        if (empty($offer) || count($offer) == '0') {
            return $this->sendError('Offer not found');
        }
       return $this->sendResponse($offer->toArray(), 'Offer retrieved successfully');
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
        if (!empty($this->offerRepository)) {
            $offer = $this->offerRepository->findByField(array('is_active'=>'1',"id"=>$id));
        }

        if (empty($offer) || count($offer) == '0') {
            return $this->sendError('Offer not found');
        }

        return $this->sendResponse($offer->toArray(), 'Offer retrieved successfully');
    }
}
