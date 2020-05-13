<?php

namespace App\Http\Controllers\API;


use App\Models\Restautant_category;
use App\Repositories\Restaurant_CategoryRepository;
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

class RestaurantCategoryAPIController extends Controller
{
    /** @var  CategoryRepository */
    private $Restaurant_CategoryRepository;

    public function __construct(Restaurant_CategoryRepository $categoryRepo)
    {
        $this->Restaurant_CategoryRepository = $categoryRepo;
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
        
        try{
            $this->Restaurant_CategoryRepository->pushCriteria(new RequestCriteria($request));
            $this->Restaurant_CategoryRepository->pushCriteria(new LimitOffsetCriteria($request));
        } catch (RepositoryException $e) {
            Flash::error($e->getMessage());
        }
        $categories = $this->Restaurant_CategoryRepository->all();

        return $this->sendResponse($categories->toArray(), 'Restaurant Categories retrieved successfully');
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
        if (!empty($this->Restaurant_CategoryRepository)) {
            $category = $this->Restaurant_CategoryRepository->findWithoutFail($id);
        }

        if (empty($category)) {
            return $this->sendError('Restaurant category not found');
        }

        return $this->sendResponse($category->toArray(), 'Restaurant category retrieved successfully');
    }
}
