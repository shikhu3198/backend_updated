<?php

namespace App\Http\Controllers;

use App\Criteria\Users\DriversCriteria;
use App\Criteria\Users\ManagersCriteria;
use App\DataTables\RestaurantDataTable;
use App\Events\RestaurantChangedEvent;
use App\Http\Requests\CreateRestaurantRequest;
use App\Http\Requests\UpdateRestaurantRequest;
use App\Repositories\CustomFieldRepository;
use App\Repositories\RestaurantRepository;
use App\Repositories\UploadRepository;
use App\Repositories\UserRepository;
use App\Repositories\Restaurant_CategoryRepository;
use Flash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Prettus\Validator\Exceptions\ValidatorException;

class RestaurantController extends Controller
{
    /** @var  RestaurantRepository */
    private $restaurantRepository;
    private $Restaurant_CategoryRepository;
    /**
     * @var CustomFieldRepository
     */
    private $customFieldRepository;

    /**
     * @var UploadRepository
     */
    private $uploadRepository;
    /**
     * @var UserRepository
     */
    private $userRepository;

    public function __construct(RestaurantRepository $restaurantRepo, CustomFieldRepository $customFieldRepo, UploadRepository $uploadRepo, UserRepository $userRepo,Restaurant_CategoryRepository $Restaurant_CategoryRepository)
    {
        parent::__construct();
        $this->restaurantRepository = $restaurantRepo;
        $this->customFieldRepository = $customFieldRepo;
        $this->uploadRepository = $uploadRepo;
        $this->userRepository = $userRepo;
        $this->Restaurant_CategoryRepository = $Restaurant_CategoryRepository;
    }

    /**
     * Display a listing of the Restaurant.
     *
     * @param RestaurantDataTable $restaurantDataTable
     * @return Response
     */
    public function index(RestaurantDataTable $restaurantDataTable)
    {
        return $restaurantDataTable->render('restaurants.index');
    }

    /**
     * Show the form for creating a new Restaurant.
     *
     * @return Response
     */
    public function create()
    {
        if (auth()->user()->hasRole('admin')){
            $res_category = $this->Restaurant_CategoryRepository->pluck('name', 'id');
        }else{
            $res_category = $this->Restaurant_CategoryRepository->myRestaurants_category()->pluck('name', 'id');
        }

        $startTimeSelected = [];
        $endTimeSelected = [];
        $startMinutesSelected = [];
        $endMinutesSelected = [];
        $isOpenSelected = [];

        $week = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];

        $hour = array();
        for($hours = 1; $hours <= 24; $hours ++)
        {
            $hour[] = str_pad($hours, 2, '0', STR_PAD_LEFT);
        }
        $hours = $hour;

        $minutes = array();
        
        for($minutes = 0; $minutes <= 60; $minutes ++)
        {
            $minute[] = str_pad($minutes, 2, '0', STR_PAD_LEFT);
        }
        $minutes = $minute;

        $user = $this->userRepository->getByCriteria(new ManagersCriteria())->pluck('name', 'id');
        $drivers = $this->userRepository->getByCriteria(new DriversCriteria())->pluck('name', 'id');
        $usersSelected = [];
        $driversSelected = [];
        $hasCustomField = in_array($this->restaurantRepository->model(), setting('custom_field_models', []));
        if ($hasCustomField) {
            $customFields = $this->customFieldRepository->findByField('custom_field_model', $this->restaurantRepository->model());
            $html = generateCustomField($customFields);
        }
        return view('restaurants.create')->with("customFields", isset($html) ? $html : false)->with("user", $user)->with("drivers", $drivers)->with("usersSelected", $usersSelected)->with("driversSelected", $driversSelected)->with("res_category", $res_category)->with('week',$week)->with('hours',$hours)->with('minutes',$minutes)->with('startTimeSelected',$startTimeSelected)->with('endTimeSelected',$endTimeSelected)->with('startMinutesSelected',$startMinutesSelected)->with('endMinutesSelected',$endMinutesSelected)->with('isOpenSelected',$isOpenSelected);
    }

    /**
     * Store a newly created Restaurant in storage.
     *
     * @param CreateRestaurantRequest $request
     *
     * @return Response
     */
    public function store(CreateRestaurantRequest $request)
    {
        $input = $request->all();
        if (auth()->user()->hasRole('manager')) {
            $input['users'] = [auth()->id()];
        }
        $customFields = $this->customFieldRepository->findByField('custom_field_model', $this->restaurantRepository->model());

        $final_ar = array();

        foreach ($input['week'] as $key => $value) {
            
            $final_ar[] = array("week" => $value,"start_time" => $input['start_time'][$key], "start_minutes" => $input['start_minutes'][$key],"end_time" => $input['end_time'][$key],"end_minutes" => $input['end_minutes'][$key],"is_open" => $input['is_open'][$key]);
        }
        
        // $input['res_opening_hours'] = json_encode($final_ar, JSON_FORCE_OBJECT);

        $res_opening_hours = json_encode(array_values($final_ar), JSON_FORCE_OBJECT);

        foreach (json_decode($res_opening_hours) as $key => $value) {
            $res_opening_hours_data[] = $value;
        }

        $input['res_opening_hours'] = json_encode($res_opening_hours_data);

        try {
            $restaurant = $this->restaurantRepository->create($input);
            $restaurant->customFieldsValues()->createMany(getCustomFieldsValues($customFields, $request));
            if (isset($input['image']) && $input['image']) {
                $cacheUpload = $this->uploadRepository->getByUuid($input['image']);
                $mediaItem = $cacheUpload->getMedia('image')->first();
                $mediaItem->copy($restaurant, 'image');
            }
            event(new RestaurantChangedEvent($restaurant));
        } catch (ValidatorException $e) {
            Flash::error($e->getMessage());
        }

        Flash::success(__('lang.saved_successfully', ['operator' => __('lang.restaurant')]));

        return redirect(route('restaurants.index'));
    }

    /**
     * Display the specified Restaurant.
     *
     * @param int $id
     *
     * @return Response
     */
    public function show($id)
    {
        $restaurant = $this->restaurantRepository->findWithoutFail($id);

        if (empty($restaurant)) {
            Flash::error('Restaurant not found');

            return redirect(route('restaurants.index'));
        }

        return view('restaurants.show')->with('restaurant', $restaurant);
    }

    /**
     * Show the form for editing the specified Restaurant.
     *
     * @param int $id
     *
     * @return Response
     */
    public function edit($id)
    {
        $restaurant = $this->restaurantRepository->findWithoutFail($id);

        $user = $this->userRepository->getByCriteria(new ManagersCriteria())->pluck('name', 'id');
        $drivers = $this->userRepository->getByCriteria(new DriversCriteria())->pluck('name', 'id');

        $res_opening_hours = json_decode($restaurant->res_opening_hours);

        $startTimeSelected = [];
        $endTimeSelected = [];
        $startMinutesSelected = [];
        $endMinutesSelected = [];
        $isOpenSelected = [];

        if(isset($res_opening_hours) && $res_opening_hours != '')
        {
            foreach ($res_opening_hours as $key => $value) {
                 
                $startTimeSelected[] = $value->start_time;      
                $endTimeSelected[] = $value->end_time;      
                $startMinutesSelected[] = $value->start_minutes;      
                $endMinutesSelected[] = $value->end_minutes;      
                $isOpenSelected[] = $value->is_open;      
            }
        }
        
        $week = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];

        $hour = array();
        for($hours = 1; $hours <= 24; $hours ++)
        {
            $hour[] = str_pad($hours, 2, '0', STR_PAD_LEFT);
        }
        $hours = $hour;

        $minutes = array();
        
        for($minutes = 0; $minutes <= 60; $minutes ++)
        {
            $minute[] = str_pad($minutes, 2, '0', STR_PAD_LEFT);
        }
        $minutes = $minute;

        if (auth()->user()->hasRole('admin')){
            $res_category = $this->Restaurant_CategoryRepository->pluck('name', 'id');
        }else{
            $res_category = $this->Restaurant_CategoryRepository->myRestaurants_category()->pluck('name', 'id');
        }

        $usersSelected = $restaurant->users()->pluck('users.id')->toArray();
        $driversSelected = $restaurant->drivers()->pluck('users.id')->toArray();

        if (empty($restaurant)) {
            Flash::error(__('lang.not_found', ['operator' => __('lang.restaurant')]));

            return redirect(route('restaurants.index'));
        }
        $customFieldsValues = $restaurant->customFieldsValues()->with('customField')->get();
        $customFields = $this->customFieldRepository->findByField('custom_field_model', $this->restaurantRepository->model());
        $hasCustomField = in_array($this->restaurantRepository->model(), setting('custom_field_models', []));
        if ($hasCustomField) {
            $html = generateCustomField($customFields, $customFieldsValues);
        }

        return view('restaurants.edit')->with('restaurant', $restaurant)->with("customFields", isset($html) ? $html : false)->with("user", $user)->with("drivers", $drivers)->with("usersSelected", $usersSelected)->with("driversSelected", $driversSelected)->with("res_category", $res_category)->with('week',$week)->with('hours',$hours)->with('minutes',$minutes)->with('startTimeSelected',$startTimeSelected)->with('endTimeSelected',$endTimeSelected)->with('startMinutesSelected',$startMinutesSelected)->with('endMinutesSelected',$endMinutesSelected)->with('isOpenSelected',$isOpenSelected);
    }

    /**
     * Update the specified Restaurant in storage.
     *
     * @param int $id
     * @param UpdateRestaurantRequest $request
     *
     * @return Response
     */
    public function update($id, UpdateRestaurantRequest $request)
    {
        $restaurant = $this->restaurantRepository->findWithoutFail($id);

        if (empty($restaurant)) {
            Flash::error('Restaurant not found');
            return redirect(route('restaurants.index'));
        }
        $input = $request->all();

        $final_ar = array();

        foreach ($input['week'] as $key => $value) {
            
            $final_ar[] = array("week" => $value,"start_time" => $input['start_time'][$key], "start_minutes" => $input['start_minutes'][$key],"end_time" => $input['end_time'][$key],"end_minutes" => $input['end_minutes'][$key],"is_open" => $input['is_open'][$key]);
        }

        // $input['res_opening_hours'] = json_encode(array_values($final_ar), JSON_FORCE_OBJECT);
        $res_opening_hours = json_encode(array_values($final_ar), JSON_FORCE_OBJECT);

        foreach (json_decode($res_opening_hours) as $key => $value) {
            $res_opening_hours_data[] = $value;
        }

        $input['res_opening_hours'] = json_encode($res_opening_hours_data);
        
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
            event(new RestaurantChangedEvent($restaurant));
        } catch (ValidatorException $e) {
            Flash::error($e->getMessage());
        }

        Flash::success(__('lang.updated_successfully', ['operator' => __('lang.restaurant')]));

        return redirect(route('restaurants.index'));
    }

    /**
     * Remove the specified Restaurant from storage.
     *
     * @param int $id
     *
     * @return Response
     */
    public function destroy($id)
    {
        $restaurant = $this->restaurantRepository->findWithoutFail($id);

        if (empty($restaurant)) {
            Flash::error('Restaurant not found');

            return redirect(route('restaurants.index'));
        }

        $this->restaurantRepository->delete($id);

        Flash::success(__('lang.deleted_successfully', ['operator' => __('lang.restaurant')]));

        return redirect(route('restaurants.index'));
    }

    /**
     * Remove Media of Restaurant
     * @param Request $request
     */
    public function removeMedia(Request $request)
    {
        $input = $request->all();
        $restaurant = $this->restaurantRepository->findWithoutFail($input['id']);
        try {
            if ($restaurant->hasMedia($input['collection'])) {
                $restaurant->getFirstMedia($input['collection'])->delete();
            }
        } catch (\Exception $e) {
            Log::error($e->getMessage());
        }
    }
}
