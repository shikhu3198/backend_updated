<?php

namespace App\Http\Controllers;

use App\Models\Media;
use App\DataTables\BannersDataTable;
use App\Http\Requests;
use App\Http\Requests\CreateBannersRequest;
use App\Http\Requests\UpdateBannersRequest;
use App\Repositories\BannersRepository;
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

class BannersController extends Controller
{
    private $bannersRepository;

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

    public function __construct(BannersRepository $bannersRepo, CustomFieldRepository $customFieldRepo, UploadRepository $uploadRepo
        , RestaurantRepository $restaurantRepo
        , CategoryRepository $categoryRepo
        , FoodRepository $foodRepo)
    {
        parent::__construct();
        $this->bannersRepository = $bannersRepo;
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
    public function index(BannersDataTable $bannersDataTable)
    {
        return $bannersDataTable->render('banners.index');
    }

    /**
     * Show the form for creating a new Food.
     *
     * @return Response
     */
    public function create()
    {

        $type = [1 => 'Restaurant',2 => 'Category',3 => 'Food',4 => 'Custom URL'];

        $hasCustomField = in_array($this->bannersRepository->model(), setting('custom_field_models', []));
        if ($hasCustomField) {
            $customFields = $this->customFieldRepository->findByField('custom_field_model', $this->bannersRepository->model());
            $html = generateCustomField($customFields);
        }
        return view('banners.create')->with("customFields", isset($html) ? $html : false)->with("type", $type);
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
    public function store(CreateBannersRequest $request)
    {
        $input = $request->all();
        
        $customFields = $this->customFieldRepository->findByField('custom_field_model', $this->bannersRepository->model());
        try {
            // dd($input);
            $food = $this->bannersRepository->create($input);
            $food->customFieldsValues()->createMany(getCustomFieldsValues($customFields, $request));
            if (isset($input['image']) && $input['image']) {
                $cacheUpload = $this->uploadRepository->getByUuid($input['image']);
                $mediaItem = $cacheUpload->getMedia('image')->first();
                $mediaItem->copy($food, 'image');
            }
        } catch (ValidatorException $e) {
            Flash::error($e->getMessage());
        }

        Flash::success(__('lang.saved_successfully', ['operator' => __('lang.food')]));

        return redirect(route('banners.index'));
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
        $banners = $this->bannersRepository->findWithoutFail($id);

        if (empty($banners)) {
            Flash::error('Food not found');

            return redirect(route('banners.index'));
        }

        return view('banners.show')->with('banners', $banners);
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
        $banners = $this->bannersRepository->findWithoutFail($id);
        
        if (empty($banners)) {
            Flash::error(__('lang.not_found', ['operator' => __('lang.banners')]));

            return redirect(route('banners.index'));
        }
        
        $type = [1 => 'Restaurant',2 => 'Category',3 => 'Food',4 => 'Custom URL'];

        $customFieldsValues = $banners->customFieldsValues()->with('customField')->get();
        $customFields = $this->customFieldRepository->findByField('custom_field_model', $this->bannersRepository->model());
        $hasCustomField = in_array($this->bannersRepository->model(), setting('custom_field_models', []));
        if ($hasCustomField) {
            $html = generateCustomField($customFields, $customFieldsValues);
        }

        return view('banners.edit')->with('banners', $banners)->with("customFields", isset($html) ? $html : false)->with("type", $type);
    }

    /**
     * Update the specified Food in storage.
     *
     * @param int $id
     * @param UpdateFoodRequest $request
     *
     * @return Response
     */
    public function update($id, UpdateBannersRequest $request)
    {
        $banners = $this->bannersRepository->findWithoutFail($id);

        $input = $request->all();
        
        if (isset($input['image']) && $input['image']) {
            $media = Media::where('model_type','App\Models\Banners')->where('model_id',$id)->delete();
        }

        if (empty($banners)) {
            Flash::error('Food not found');
            return redirect(route('banners.index'));
        }
        
        $customFields = $this->customFieldRepository->findByField('custom_field_model', $this->bannersRepository->model());
        try {
            $banners = $this->bannersRepository->update($input, $id);

            if (isset($input['image']) && $input['image']) {
                $cacheUpload = $this->uploadRepository->getByUuid($input['image']);
                $mediaItem = $cacheUpload->getMedia('image')->first();
                $mediaItem->copy($banners, 'image');
            }
            foreach (getCustomFieldsValues($customFields, $request) as $value) {
                $banners->customFieldsValues()
                    ->updateOrCreate(['custom_field_id' => $value['custom_field_id']], $value);
            }
        } catch (ValidatorException $e) {
            Flash::error($e->getMessage());
        }

        Flash::success(__('lang.updated_successfully', ['operator' => __('lang.banners')]));

        return redirect(route('banners.index'));
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
        $banners = $this->bannersRepository->findWithoutFail($id);

        if (empty($banners)) {
            Flash::error('Food not found');

            return redirect(route('banners.index'));
        }

        $this->bannersRepository->delete($id);

        Flash::success(__('lang.deleted_successfully', ['operator' => __('lang.banners')]));

        return redirect(route('banners.index'));
    }

    /**
     * Remove Media of Food
     * @param Request $request
     */
    public function removeMedia(Request $request)
    {
        $input = $request->all();
        $banners = $this->bannersRepository->findWithoutFail($input['id']);
        try {
            if ($banners->hasMedia($input['collection'])) {
                $banners->getFirstMedia($input['collection'])->delete();
            }
        } catch (\Exception $e) {
            Log::error($e->getMessage());
        }
    }
}
