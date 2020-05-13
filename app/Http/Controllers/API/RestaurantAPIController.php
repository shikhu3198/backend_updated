<?php

namespace App\Http\Controllers\API;


use App\Models\Restaurant;
use App\Repositories\CustomFieldRepository;
use App\Repositories\RestaurantRepository;
use App\Repositories\UploadRepository;
use App\Repositories\UserRepository;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use InfyOm\Generator\Criteria\LimitOffsetCriteria;
use Prettus\Repository\Criteria\RequestCriteria;
use Illuminate\Support\Facades\Response;
use Prettus\Repository\Exceptions\RepositoryException;
use Flash;
use Prettus\Validator\Exceptions\ValidatorException;

/**
 * Class RestaurantController
 * @package App\Http\Controllers\API
 */

class RestaurantAPIController extends Controller
{
    /** @var  RestaurantRepository */
    private $restaurantRepository;

    /**
     * @var CustomFieldRepository
     */
    private $customFieldRepository;

    /**
     * @var UploadRepository
     */
    private $uploadRepository;


    public function __construct(RestaurantRepository $restaurantRepo, CustomFieldRepository $customFieldRepo, UploadRepository $uploadRepo)
    {
        parent::__construct();
        $this->restaurantRepository = $restaurantRepo;
        $this->customFieldRepository = $customFieldRepo;
        $this->uploadRepository = $uploadRepo;

    }

    /**
     * Display a listing of the Restaurant.
     * GET|HEAD /restaurants
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $res_category_id = $request->input('res_category_id');

        try{
            $this->restaurantRepository->pushCriteria(new RequestCriteria($request));
            $this->restaurantRepository->pushCriteria(new LimitOffsetCriteria($request));
        } catch (RepositoryException $e) {
            return $this->sendError($e->getMessage());
        }
        $inputs = $request->all();
        if($request->has(['myLon','myLat','areaLon','areaLat']) && !empty($inputs['search'])){
            $restaurants = $this->restaurantRepository->near($inputs['myLon'],$inputs['myLat'],$inputs['areaLon'],$inputs['areaLat'],$res_category_id,$inputs['search']);
        }else if($request->has(['myLon','myLat','areaLon','areaLat']) && empty($inputs['search'])){
                
                $restaurants = $this->restaurantRepository->nearwithoutsearch($inputs['myLon'],$inputs['myLat'],$inputs['areaLon'],$inputs['areaLat'], $res_category_id);
                
        }else if(isset($inputs['search'])){
            $restaurants = $this->restaurantRepository->searchDetails($res_category_id,$inputs['search']);
        } else if(isset($res_category_id) && $res_category_id != ''){
            $restaurants = $this->restaurantRepository->findByField("res_category_id",$res_category_id);
        }else{
            $restaurants = $this->restaurantRepository->all();
        }

        foreach ($restaurants as $skey => $restaurant) 
            {              
              $res_opening_hours = array();
                if(isset($restaurant['res_opening_hours']) && $restaurant['res_opening_hours'] != '')
                {
                    foreach (json_decode($restaurant['res_opening_hours']) as $key => $value) {
                        $res_opening_hours[] = $value;
                    }

                    $restaurants[$skey]['res_opening_hours'] = $res_opening_hours;
                }
                else
                {
                    $restaurants[$skey]['res_opening_hours'] = array();
                }
            }

        if (empty($restaurants)) {
            return $this->sendError('Restaurant not found');
        }
        
        return $this->sendResponse($restaurants, 'Restaurants retrieved successfully');
    }

    /**
     * Display the specified Restaurant.
     * GET|HEAD /restaurants/{id}
     *
     * @param  int $id
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, $id)
    {
        /** @var Restaurant $restaurant */
        if (!empty($this->restaurantRepository)) {
            try{
                $this->restaurantRepository->pushCriteria(new RequestCriteria($request));
                $this->restaurantRepository->pushCriteria(new LimitOffsetCriteria($request));
            } catch (RepositoryException $e) {
                return $this->sendError($e->getMessage());
            }
            $restaurant = $this->restaurantRepository->findWithoutFail($id);

            if(isset($restaurant['res_opening_hours']) && $restaurant['res_opening_hours'] != '')
            {

                foreach (json_decode($restaurant['res_opening_hours']) as $key => $value) {
                    $res_opening_hours[] = $value;
                }

                $restaurant['res_opening_hours'] = $res_opening_hours;
            }
            else
            {
                $restaurant['res_opening_hours'] = array();
            }
        }

        if (empty($restaurant)) {
            return $this->sendError('Restaurant not found');
        }

        return $this->sendResponse($restaurant->toArray(), 'Restaurant retrieved successfully');
    }

    /**
     * Store a newly created Restaurant in storage.
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $input = $request->all();
        if (auth()->user()->hasRole('manager')){
            $input['users'] = [auth()->id()];
        }
        $customFields = $this->customFieldRepository->findByField('custom_field_model', $this->restaurantRepository->model());
        try {
            $restaurant = $this->restaurantRepository->create($input);
            $restaurant->customFieldsValues()->createMany(getCustomFieldsValues($customFields, $request));
            if (isset($input['image']) && $input['image']) {
                $cacheUpload = $this->uploadRepository->getByUuid($input['image']);
                $mediaItem = $cacheUpload->getMedia('image')->first();
                $mediaItem->copy($restaurant, 'image');
            }
        } catch (ValidatorException $e) {
            return $this->sendError($e->getMessage());
        }

        return $this->sendResponse($restaurant->toArray(),__('lang.saved_successfully', ['operator' => __('lang.restaurant')]));
    }

    /**
     * Update the specified Restaurant in storage.
     *
     * @param int $id
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function update($id, Request $request)
    {
        $restaurant = $this->restaurantRepository->findWithoutFail($id);

        if (empty($restaurant)) {
            return $this->sendError('Restaurant not found');
        }
        $input = $request->all();
        $customFields = $this->customFieldRepository->findByField('custom_field_model', $this->restaurantRepository->model());
        try {
            $restaurant = $this->restaurantRepository->update($input, $id);
            $input['users'] = isset($input['users']) ? $input['users'] : [];
            $input['drivers'] = isset($input['drivers']) ? $input['drivers'] : [];
            if (isset($input['image']) && $input['image']) {
                $cacheUpload = $this->uploadRepository->getByUuid($input['image']);
                $mediaItem = $cacheUpload->getMedia('image')->first();
                $mediaItem->copy($restaurant, 'image');
            }
            foreach (getCustomFieldsValues($customFields, $request) as $value) {
                $restaurant->customFieldsValues()
                    ->updateOrCreate(['custom_field_id' => $value['custom_field_id']], $value);
            }
        } catch (ValidatorException $e) {
            return $this->sendError($e->getMessage());
        }

        return $this->sendResponse($restaurant->toArray(),__('lang.updated_successfully', ['operator' => __('lang.restaurant')]));
    }

    /**
     * Remove the specified Restaurant from storage.
     *
     * @param int $id
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        $restaurant = $this->restaurantRepository->findWithoutFail($id);

        if (empty($restaurant)) {
            return $this->sendError('Restaurant not found');
        }

        $restaurant = $this->restaurantRepository->delete($id);

        return $this->sendResponse($restaurant,__('lang.deleted_successfully', ['operator' => __('lang.restaurant')]));
    }
}
