<?php

namespace App\Http\Controllers;

use App\Http\Requests\Category\StoreRequest;
use App\Http\Requests\Category\UpdateRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Exception;
use Illuminate\Support\Facades\DB;

class CategoryController extends Controller
{
    /**
     * @OA\Get(
     *     path="/categories",
     *     summary="get list of categories",
     *     description="categories list",tags={"categories"},
     *     @OA\Response(response="200", description="Success")
     * )
     */
    public function index()
    {
        return CategoryResource::collection(Category::paginate());
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * @OA\Post(
     *   path="/categories",
     *   summary="create category",
     *   description="create category",
     *   operationId="createcategory",
     *   tags={"categories"},
     *   @OA\RequestBody(
     *       required=true,
     *       description="Pass user credentials",
     *       @OA\JsonContent(
     *           required={"name","description", "image_ids", "category_ids"},
     *           @OA\Property(property="name", type="string", format="name", example="category name"),
     *       ),
     *   ),
     *   @OA\Response(
     *       response=201,
     *       description="Successfully Created"
     *   ),
     *   @OA\Response(
     *      response=422,
     *      description="Unprocessable Content - Validation",
     *   )
     * )
     */
    public function store(StoreRequest $request)
    {
        DB::beginTransaction();
		try {
            $data = $request->all();
            $data = Category::create($data);

			DB::commit();

            $res = new CategoryResource($data);
            return $res->response()->setStatusCode(201);
        } catch (Exception $e) {
			DB::rollBack();
			return response()->json([
				'success' => false,
				'message' => $e->getMessage()
			], 500);
		}
    }

    /**
     * @OA\Get(
     *      path="/categories/{id}",
     *      operationId="getcategorybyid",
     *      tags={"categories"},
     *      summary="get category by id",
     *      description="get category by id",
     *      @OA\Parameter(
     *          name="id",
     *          description="category id",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Success",
     *       ),
     *      @OA\Response(
     *          response=404,
     *          description="Not Found"
     *      )
     * )
     */
    public function show(Category $category)
    {
        return new CategoryResource($category);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Category  $category
     * @return \Illuminate\Http\Response
     */
    public function edit(Category $category)
    {
        //
    }

    /**
     * @OA\Put(
     *   path="/categories/{id}",
     *   summary="update category by id",
     *   description="update category by id",
     *   operationId="updatecategory",
     *   tags={"categories"},
     *   @OA\Parameter(
     *          name="id",
     *          description="category id",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *   @OA\RequestBody(
     *       required=true,
     *       description="Pass user credentials",
     *       @OA\JsonContent(
     *           required={"name","description", "image_ids", "category_ids"},
     *           @OA\Property(property="name", type="string", format="name", example="category name"),
     *       ),
     *   ),
     *   @OA\Response(
     *       response=200,
     *       description="Success"
     *   ),
     *   @OA\Response(
     *      response=422,
     *      description="Unprocessable Content - Validation",
     *   )
     * )
     */
    public function update(UpdateRequest $request, Category $category)
    {
        DB::beginTransaction();
		try {
            $data = $request->all();

            $category->update($data);

			DB::commit();

            return $this->show($category);
        } catch (Exception $e) {
			DB::rollBack();
			return response()->json([
				'success' => false,
				'message' => $e->getMessage()
			], 500);
		}
    }

    /**
     * @OA\Delete(
     *      path="/categories/{id}",
     *      operationId="deletecategory",
     *      tags={"categories"},
     *      summary="delete category by id",
     *      description="delete category by Id",
     *      @OA\Parameter(
     *          name="id",
     *          description="delete category by id",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *      @OA\Response(
     *          response=204,
     *          description="Success - No Content",
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Not Found",
     *      )
     * )
     */
    public function destroy(Category $category)
    {
        if($category->products->count() > 0) return response()->json([
            'success' => false,
            'message' => 'image stiil used by products'
        ], 422);

        $category->delete();
        return response()->noContent();
    }
}
