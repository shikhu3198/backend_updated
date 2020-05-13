<?php

namespace App\Http\Controllers;

use App\DataTables\OfferDataTable;
use App\Http\Requests;
use App\Http\Requests\CreateOfferRequest;
use App\Http\Requests\UpdateOfferRequest;
use App\Repositories\OfferRepository ;
use App\Repositories\CustomFieldRepository;
use App\Repositories\UploadRepository;
use App\Repositories\RestaurantRepository;
use App\Repositories\CategoryRepository;
use App\Repositories\FoodRepository;
use Flash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Prettus\Validator\Exceptions\ValidatorException;

class OfferController extends Controller
{
    private $offerRepository;

    /**
     * @var CustomFieldRepository
     */
    private $customFieldRepository;

    /**
     * @var UploadRepository
     */
    private $uploadRepository;
    /**
     * @var RestaurantRepository
     */
    private $restaurantRepository;
    private $foodRepository;
    /**
     * @var CategoryRepository
     */
    private $categoryRepository;

    public function __construct(OfferRepository $offerRepo, CustomFieldRepository $customFieldRepo, UploadRepository $uploadRepo
        , RestaurantRepository $restaurantRepo
        , CategoryRepository $categoryRepo
        , FoodRepository $foodRepo)
    {
        parent::__construct();
        $this->offerRepository = $offerRepo;
        $this->customFieldRepository = $customFieldRepo;
        $this->uploadRepository = $uploadRepo;
        $this->restaurantRepository = $restaurantRepo;
        $this->categoryRepository = $categoryRepo;
        $this->foodRepository = $foodRepo;
    }

    /**
     * Display a listing of the Food.
     *
     * @param FoodDataTable $foodDataTable
     * @return Response
     */
    public function index(OfferDataTable $offerDataTable)
    {
        return $offerDataTable->render('offer.index');
    }

    /**
     * Show the form for creating a new Food.
     *
     * @return Response
     */
    public function create()
    {

        $type = [1 => 'Restaurant',2 => 'Category',3 => 'Food',4 => 'Custom URL'];

        $hasCustomField = in_array($this->offerRepository->model(), setting('custom_field_models', []));
        if ($hasCustomField) {
            $customFields = $this->customFieldRepository->findByField('custom_field_model', $this->offerRepository->model());
            $html = generateCustomField($customFields);
        }
        return view('offer.create')->with("customFields", isset($html) ? $html : false)->with("type", $type);
    }

    public function getType(Request $request)
    {
        $input = $request->all();

        if ($input['id'] == 1){
            $data = $this->restaurantRepository->pluck('name', 'id');            
        }else if($input['id'] == 2){
            $data = $this->categoryRepository->pluck('name', 'id');
        }else if($input['id'] == 3){
            $data = $this->foodRepository->pluck('name', 'id');
        } else {
            $data = '';
        }   
        echo $data;
    }
    /**
     * Store a newly created Food in storage.
     *
     * @param CreateBannersRequest $request
     *
     * @return Response
     */
    public function store(CreateOfferRequest $request)
    {
        $input = $request->all();
           
        $customFields = $this->customFieldRepository->findByField('custom_field_model', $this->offerRepository->model());
        try {
            $getAllOffer = $this->offerRepository->get();

            if($input['is_active'] != 0)
            {
                foreach ($getAllOffer as $key => $value) {
                    $offers = $this->offerRepository->update(['is_active' => '0'],$value->id);
                }    
            }
            
            
            $food = $this->offerRepository->create($input);
            
        } catch (ValidatorException $e) {
            Flash::error($e->getMessage());
        }

        Flash::success(__('lang.saved_successfully', ['operator' => __('lang.food')]));

        return redirect(route('offer.index'));
    }

    /**
     * Display the specified Food.
     *
     * @param int $id
     *
     * @return Response
     */
    public function show($id)
    {
        $offers = $this->offerRepository->findWithoutFail($id);

        if (empty($offers)) {
            Flash::error('Food not found');

            return redirect(route('offer.index'));
        }

        return view('offer.show')->with('banners', $offers);
    }

    /**
     * Show the form for editing the specified Food.
     *
     * @param int $id
     *
     * @return Response
     */
    public function edit($id)
    {
        $offers = $this->offerRepository->findWithoutFail($id);
        
        if (empty($offers)) {
            Flash::error(__('lang.not_found', ['operator' => __('lang.banners')]));
            return redirect(route('offer.index'));
        }
        
        $type = [1 => 'Restaurant',2 => 'Category',3 => 'Food',4 => 'Custom URL'];

        $customFieldsValues = $offers->customFieldsValues()->with('customField')->get();
        $customFields = $this->customFieldRepository->findByField('custom_field_model', $this->offerRepository->model());
        $hasCustomField = in_array($this->offerRepository->model(), setting('custom_field_models', []));
        if ($hasCustomField) {
            $html = generateCustomField($customFields, $customFieldsValues);
        }
        
        return view('offer.edit')->with('offer', $offers)->with("customFields", isset($html) ? $html : false)->with("type", $type);
    }

    /**
     * Update the specified Food in storage.
     *
     * @param int $id
     * @param UpdateFoodRequest $request
     *
     * @return Response
     */
    public function update($id, UpdateOfferRequest $request)
    {
        $offers = $this->offerRepository->findWithoutFail($id);

        if (empty($offers)) {
            Flash::error('Food not found');
            return redirect(route('offer.index'));
        }
        $input = $request->all();
        
        try {

            $getAllOffer = $this->offerRepository->all();

            if($input['is_active'] != 0)
            {
                foreach ($getAllOffer as $key => $value) {

                    $offers = $this->offerRepository->update(['is_active' => '0'],$value->id);   
                }
            }
            $offers = $this->offerRepository->update($input, $id);           
        } catch (ValidatorException $e) {
            Flash::error($e->getMessage());
        }

        Flash::success(__('lang.updated_successfully', ['operator' => __('lang.banners')]));

        return redirect(route('offer.index'));
    }

    /**
     * Remove the specified Food from storage.
     *
     * @param int $id
     *
     * @return Response
     */
    public function destroy($id)
    {
        $offers = $this->offerRepository->findWithoutFail($id);

        if (empty($offers)) {
            Flash::error('Food not found');

            return redirect(route('offer.index'));
        }

        $this->offerRepository->delete($id);

        Flash::success(__('lang.deleted_successfully', ['operator' => __('lang.banners')]));

        return redirect(route('offer.index'));
    }

    /**
     * Remove Media of Food
     * @param Request $request
     */
    public function removeMedia(Request $request)
    {
        $input = $request->all();
        $offers = $this->offerRepository->findWithoutFail($input['id']);
        try {
            if ($offers->hasMedia($input['collection'])) {
                $offers->getFirstMedia($input['collection'])->delete();
            }
        } catch (\Exception $e) {
            Log::error($e->getMessage());
        }
    }
}
