<?php

namespace App\Http\Controllers\API;


use App\Models\Food;
use App\Repositories\CategoryRepository;
use App\Repositories\CustomFieldRepository;
use App\Repositories\FoodRepository;
use App\Repositories\RestaurantRepository;
use App\Repositories\UploadRepository;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use InfyOm\Generator\Criteria\LimitOffsetCriteria;
use Prettus\Repository\Criteria\RequestCriteria;
use Illuminate\Support\Facades\Response;
use Prettus\Repository\Exceptions\RepositoryException;
use Flash;
use Prettus\Validator\Exceptions\ValidatorException;

/**
 * Class FoodController
 * @package App\Http\Controllers\API
 */

class FoodAPIController extends Controller
{
    /** @var  FoodRepository */
    private $foodRepository;

    private $restaurantRepository;
    /**
     * @var CustomFieldRepository
     */
    private $customFieldRepository;
    /**
     * @var UploadRepository
     */
    private $uploadRepository;


    public function __construct(FoodRepository $foodRepo, CustomFieldRepository $customFieldRepo, UploadRepository $uploadRepo, RestaurantRepository $restaurantRepo)
    {
        parent::__construct();
        $this->foodRepository = $foodRepo;
        $this->customFieldRepository = $customFieldRepo;
        $this->uploadRepository = $uploadRepo;
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
        /*try{
            $this->foodRepository->pushCriteria(new RequestCriteria($request));
            $this->foodRepository->pushCriteria(new LimitOffsetCriteria($request));
        } catch (RepositoryException $e) {
            return $this->sendError($e->getMessage());
        }
        $foods = $this->foodRepository->all();*/

        $res_category_id = $request->input('res_category_id');
        $restaurant_id = $request->input('restaurant_id');
        $category_id = $request->input('categoryid');
        $limit = $request->input('limit');

        $foods = Food::select('foods.*')
                ->join('restaurants','foods.restaurant_id','=','restaurants.id');
                if(isset($res_category_id) && $res_category_id != ''){
                   $foods = $foods->where('restaurants.res_category_id',$res_category_id);
                }
                if(isset($category_id) && $category_id != ''){
                    $foods = $foods->where('foods.category_id',$category_id);
                }
                if(isset($restaurant_id) && $restaurant_id != ''){
                    $foods = $foods->where('foods.restaurant_id',$restaurant_id);
                }
                $foods = $foods->orderBy('foods.id')
                ->limit($limit)->get();
        
        try{
            $this->foodRepository->pushCriteria(new RequestCriteria($request));
            $this->foodRepository->pushCriteria(new LimitOffsetCriteria($request));
        } catch (RepositoryException $e) {
            return $this->sendError($e->getMessage());
        }

        if(isset($res_category_id) && $res_category_id != ''){

            foreach ($foods as $key => $value) {

                $food = $this->restaurantRepository->findByField(array("id"=>$value->restaurant_id,'res_category_id'=>$res_category_id));
                
                if(count($food) > 0)
                {
                    $foods[$key] = $value;
                }
                else
                {
                    unset($foods[$key]);
                }
            }
        }
        $custum_Arr = $foods->toArray();
            
        foreach ($foods as $fkey => $fvalue) {

            $getRelationData = $fvalue->restaurant()->first();    
            $res_opening_hours = array();
            foreach (json_decode($getRelationData['res_opening_hours']) as $key => $value) {
                
                $res_opening_hours[] = $value;
            }
            $custum_Arr[$fkey]['restaurant']['res_opening_hours'] = $res_opening_hours;
        }
        
           
        
        if (count($custum_Arr) == 0) {
            return $this->sendError('Food not found');
        }

        return $this->sendResponse($custum_Arr, 'Foods retrieved successfully');

        /* end code */
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
                return $this->sendError($e->getMessage());
            }
            $food = $this->foodRepository->findWithoutFail($id);
            $custum_Arr = $food->toArray();

            $getRelationData = $food->restaurant()->first();

           foreach (json_decode($getRelationData['res_opening_hours']) as $key => $value) {
                
                $res_opening_hours[] = $value;
            }
            $custum_Arr['restaurant']['res_opening_hours'] = $res_opening_hours; 
        }
            
        if (empty($custum_Arr)) {
            return $this->sendError('Food not found');
        }

        return $this->sendResponse($custum_Arr, 'Food retrieved successfully');
    }

    /**
     * Store a newly created Food in storage.
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $input = $request->all();
        $customFields = $this->customFieldRepository->findByField('custom_field_model', $this->foodRepository->model());
        try {
            $food = $this->foodRepository->create($input);
            $food->customFieldsValues()->createMany(getCustomFieldsValues($customFields, $request));
            if (isset($input['image']) && $input['image']) {
                $cacheUpload = $this->uploadRepository->getByUuid($input['image']);
                $mediaItem = $cacheUpload->getMedia('image')->first();
                $mediaItem->copy($food, 'image');
            }
        } catch (ValidatorException $e) {
            return $this->sendError($e->getMessage());
        }

        return $this->sendResponse($food->toArray(), __('lang.saved_successfully', ['operator' => __('lang.food')]));
    }

    /**
     * Update the specified Food in storage.
     *
     * @param int $id
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function update($id, Request $request)
    {
        $food = $this->foodRepository->findWithoutFail($id);

        if (empty($food)) {
            return $this->sendError('Food not found');
        }
        $input = $request->all();
        $customFields = $this->customFieldRepository->findByField('custom_field_model', $this->foodRepository->model());
        try {
            $food = $this->foodRepository->update($input, $id);

            if (isset($input['image']) && $input['image']) {
                $cacheUpload = $this->uploadRepository->getByUuid($input['image']);
                $mediaItem = $cacheUpload->getMedia('image')->first();
                $mediaItem->copy($food, 'image');
            }
            foreach (getCustomFieldsValues($customFields, $request) as $value) {
                $food->customFieldsValues()
                    ->updateOrCreate(['custom_field_id' => $value['custom_field_id']], $value);
            }
        } catch (ValidatorException $e) {
            return $this->sendError($e->getMessage());
        }

        return $this->sendResponse($food->toArray(), __('lang.updated_successfully', ['operator' => __('lang.food')]));

    }

    /**
     * Remove the specified Food from storage.
     *
     * @param int $id
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        $food = $this->foodRepository->findWithoutFail($id);

        if (empty($food)) {
            return $this->sendError('Food not found');
        }

        $food = $this->foodRepository->delete($id);

        return $this->sendResponse($food, __('lang.deleted_successfully', ['operator' => __('lang.food')]));

    }

}
