<?php

namespace ProcessMaker\Http\Controllers\Api\Administration;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

// TODO remove
use ProcessMaker\Facades\ProcessCategoryManager;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Validator as ValidatorImplementation;
use ProcessMaker\Exception\ValidationException;

use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Model\Permission;
use ProcessMaker\Model\ProcessCategory;
use ProcessMaker\Transformers\ProcessCategoryTransformer;
use Ramsey\Uuid\Uuid;

/**
 * Implements endpoints to manage the process categories.
 *
 */
class ProcessCategoryController extends Controller
{

    /**
     * List of process categories.
     *
     * @param Request $request
     *
     * @return array
     */
    public function index(Request $request)
    {
        $this->authorize('has-permission', Permission::PM_SETUP_PROCESS_CATEGORIES);
        $query = ProcessCategory::where('uid', '!=', '')
                 ->withCount('processes');

        $filter = $request->input("filter");
        $filter === null ? : $query->where(
            'name', 'like', '%' . $filter . '%'
        );

        $orderBy = $request->input('order_by', 'name');
        $orderDirection = $request->input('order_direction', 'ASC');
        $orderBy === null ? : $query->orderBy($orderBy, $orderDirection);

        $perPage = $request->input('per_page', 10);
        $result = $query->paginate($perPage);
        return fractal($result, new ProcessCategoryTransformer())->respond();
    }

    /**
     * Stores a new process category.
     *
     * @param Request $request
     *
     * @return array
     */
    public function store(Request $request)
    {
        $this->authorize('has-permission', Permission::PM_SETUP_PROCESS_CATEGORIES);
        $data = $request->json()->all();

        $processCategory = new ProcessCategory();
        $processCategory->uid = str_replace('-', '', Uuid::uuid4());
        $processCategory->fill($data);
        $processCategory->saveOrFail();


        return fractal($processCategory, new ProcessCategoryTransformer())->respond(201);
    }

    /**
     * Update a process category.
     *
     * @param Request $request
     * @param ProcessCategory $processCategory
     *
     * @return array
     */
    public function update(Request $request, ProcessCategory $processCategory)
    {
        $data = $request->json()->all();
        $processCategory->fill($data);
        $processCategory->saveOrFail();
        
        return fractal($processCategory, new ProcessCategoryTransformer())->respond(200);
    }

    /**
     * Remove a process category.
     *
     * @param ProcessCategory $processCategory
     *
     * @return array
     */
    public function destroy(ProcessCategory $processCategory)
    {
        $validator = Validator::make([
            'processCategory' => $processCategory,
        ], [
            'processCategory' => 'process_category_manager.category_does_not_have_processes',
        ]);
        $validator->addExtension(
            'process_category_manager.category_does_not_have_processes',
            function ($attribute, $processCategory, $parameters, ValidatorImplementation $validator) {
                return $processCategory->processes()->count() === 0;
            }
        );

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $processCategory->delete();
        return response('', 204);
    }

    /**
     * Show the properties of a process category.
     *
     * @param ProcessCategory $processCategory
     *
     * @return array
     */
    public function show(ProcessCategory $processCategory)
    {
        return fractal($processCategory, new ProcessCategoryTransformer())
               ->respond();
    }
}
