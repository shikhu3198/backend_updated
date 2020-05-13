<?php

namespace App\Http\Controllers;

use App\DataTables\Restautant_CategoryDataTable;
use App\Http\Requests;
use App\Http\Requests\Restautant_CreateCategoryRequest;
use App\Http\Requests\Restautant_UpdateCategoryRequest;
use App\Repositories\Restaurant_CategoryRepository;
use App\Repositories\CustomFieldRepository;
use App\Repositories\UploadRepository;
use Flash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Prettus\Validator\Exceptions\ValidatorException;

class RestaurantCategoryController extends Controller
{
    /** @var  CategoryRepository */
    private $Restaurant_CategoryRepository;

    /**
     * @var CustomFieldRepository
     */
    private $customFieldRepository;

    /**
  * @var UploadRepository
  */
private $uploadRepository;

    public function __construct(Restaurant_CategoryRepository $categoryRepo, CustomFieldRepository $customFieldRepo , UploadRepository $uploadRepo)
    {
        parent::__construct();
        $this->Restaurant_CategoryRepository = $categoryRepo;
        $this->customFieldRepository = $customFieldRepo;
        $this->uploadRepository = $uploadRepo;
    }

    /**
     * Display a listing of the Category.
     *
     * @param Restautant_CategoryDataTable $categoryDataTable
     * @return Response
     */
    public function index(Restautant_CategoryDataTable $categoryDataTable)
    {
        return $categoryDataTable->render('res_categories.index');
    }

    /**
     * Show the form for creating a new Category.
     *
     * @return Response
     */
    public function create()
    {
        
        
        $hasCustomField = in_array($this->Restaurant_CategoryRepository->model(),setting('custom_field_models',[]));
            if($hasCustomField){
                $customFields = $this->customFieldRepository->findByField('custom_field_model', $this->Restaurant_CategoryRepository->model());
                $html = generateCustomField($customFields);
            }
        return view('res_categories.create')->with("customFields", isset($html) ? $html : false);
    }

    /**
     * Store a newly created Category in storage.
     *
     * @param Restautant_CreateCategoryRequest $request
     *
     * @return Response
     */
    public function store(Restautant_CreateCategoryRequest $request)
    {
        $input = $request->all();
        $customFields = $this->customFieldRepository->findByField('custom_field_model', $this->Restaurant_CategoryRepository->model());
        try {
            $category = $this->Restaurant_CategoryRepository->create($input);
            $category->customFieldsValues()->createMany(getCustomFieldsValues($customFields,$request));
            
            if(isset($input['image']) && $input['image']){
                $cacheUpload = $this->uploadRepository->getByUuid($input['image']);

                $mediaItem = $cacheUpload->getMedia('image')->first();
                
                $mediaItem->copy($category, 'image');

            }
        } catch (ValidatorException $e) { 
            Flash::error($e->getMessage());
        }

        Flash::success(__('lang.saved_successfully',['operator' => __('lang.category')]));

        return redirect(route('res_categories.index'));
    }

    /**
     * Display the specified Category.
     *
     * @param  int $id
     *
     * @return Response
     */
    public function show($id)
    {
        $category = $this->Restaurant_CategoryRepository->findWithoutFail($id);

        if (empty($category)) {
            Flash::error('Category not found');

            return redirect(route('res_categories.index'));
        }

        return view('res_categories.show')->with('category', $category);
    }

    /**
     * Show the form for editing the specified Category.
     *
     * @param  int $id
     *
     * @return Response
     */
    public function edit($id)
    {
        $category = $this->Restaurant_CategoryRepository->findWithoutFail($id);

        if (empty($category)) {
            Flash::error(__('lang.not_found',['operator' => __('lang.category')]));

            return redirect(route('res_categories.index'));
        }
        $customFieldsValues = $category->customFieldsValues()->with('customField')->get();
        $customFields =  $this->customFieldRepository->findByField('custom_field_model', $this->Restaurant_CategoryRepository->model());
        $hasCustomField = in_array($this->Restaurant_CategoryRepository->model(),setting('custom_field_models',[]));
        if($hasCustomField) {
            $html = generateCustomField($customFields, $customFieldsValues);
        }

        return view('res_categories.edit')->with('category', $category)->with("customFields", isset($html) ? $html : false);
    }

    /**
     * Update the specified Category in storage.
     *
     * @param  int              $id
     * @param Restautant_UpdateCategoryRequest $request
     *
     * @return Response
     */
    public function update($id, Restautant_UpdateCategoryRequest $request)
    {
        $category = $this->Restaurant_CategoryRepository->findWithoutFail($id);

        if (empty($category)) {
            Flash::error('Category not found');
            return redirect(route('res_categories.index'));
        }
        $input = $request->all();
        $customFields = $this->customFieldRepository->findByField('custom_field_model', $this->Restaurant_CategoryRepository->model());
        try {
            $category = $this->Restaurant_CategoryRepository->update($input, $id);
            
            if (isset($input['image']) && $input['image']) {
                $cacheUpload = $this->uploadRepository->getByUuid($input['image']);
                $mediaItem = $cacheUpload->getMedia('image')->first();
                $mediaItem->copy($category, 'image');
            }
            
            foreach (getCustomFieldsValues($customFields, $request) as $value) {
                $category->customFieldsValues()
                    ->updateOrCreate(['custom_field_id' => $value['custom_field_id']], $value);
            }
        } catch (ValidatorException $e) {
            Flash::error($e->getMessage());
        }

        Flash::success(__('lang.updated_successfully',['operator' => __('lang.category')]));

        return redirect(route('res_categories.index'));
    }

    /**
     * Remove the specified Category from storage.
     *
     * @param  int $id
     *
     * @return Response
     */
    public function destroy($id)
    {
        $category = $this->Restaurant_CategoryRepository->findWithoutFail($id);

        if (empty($category)) {
            Flash::error('Category not found');

            return redirect(route('res_categories.index'));
        }

        $this->Restaurant_CategoryRepository->delete($id);

        Flash::success(__('lang.deleted_successfully',['operator' => __('lang.category')]));

        return redirect(route('res_categories.index'));
    }

        /**
     * Remove Media of Category
     * @param Request $request
     */
    public function removeMedia(Request $request)
    {
        $input = $request->all();
        $category = $this->Restaurant_CategoryRepository->findWithoutFail($input['id']);
        try {
            if($category->hasMedia($input['collection'])){
                $category->getFirstMedia($input['collection'])->delete();
            }
        } catch (\Exception $e) {
            Log::error($e->getMessage());
        }
    }
}
