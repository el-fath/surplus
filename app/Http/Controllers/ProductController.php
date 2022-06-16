<?php

namespace App\Http\Controllers;

use App\Http\Requests\Product\StoreRequest;
use App\Http\Requests\Product\UpdateRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Exception;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{    
    /**
     * @OA\Get(
     *     path="/products",
     *     summary="get list of products",
     *     description="products list",tags={"products"},
     *     @OA\Response(response="200", description="Success")
     * )
     */
    public function index()
    {
        return ProductResource::collection(Product::paginate());
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
     *   path="/products",
     *   summary="create product",
     *   description="create product",
     *   operationId="createproduct",
     *   tags={"products"},
     *   @OA\RequestBody(
     *       required=true,
     *       description="Pass user credentials",
     *       @OA\JsonContent(
     *           required={"name","description", "image_ids", "category_ids"},
     *           @OA\Property(property="name", type="string", format="name", example="product name"),
     *           @OA\Property(property="description", type="string", format="description", example="test desc"),
     *           @OA\Property(
     *               property="image_ids",
     *               description="image_ids",
     *               type="array",
     *               @OA\Items(type="integer", format="id", example=1)
     *           ),
     *           @OA\Property(
     *               property="category_ids",
     *               description="category_ids",
     *               type="array",
     *               @OA\Items(type="integer", format="id", example=1)
     *           ),
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
            $data = $request->except(['image_ids', 'category_ids']);

            $product = Product::create($data);
            if ($request->image_ids) $product->images()->sync($request->image_ids);
            if ($request->category_ids) $product->categories()->sync($request->category_ids);

			DB::commit();

            $res = new ProductResource($product);
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
     *      path="/products/{id}",
     *      operationId="getproductbyid",
     *      tags={"products"},
     *      summary="get product by id",
     *      description="get product by id",
     *      @OA\Parameter(
     *          name="id",
     *          description="product id",
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
    public function show(Product $product)
    {
        return new ProductResource($product);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function edit(Product $product)
    {
        //
    }

    /**
     * @OA\PUT(
     *   path="/products/{id}",
     *   summary="update product by id",
     *   description="update product by id",
     *   operationId="updateproduct",
     *   tags={"products"},
     *   @OA\Parameter(
     *          name="id",
     *          description="product id",
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
     *           @OA\Property(property="name", type="string", format="name", example="product name"),
     *           @OA\Property(property="description", type="string", format="description", example="test desc"),
     *           @OA\Property(
     *               property="image_ids",
     *               description="image_ids",
     *               type="array",
     *               @OA\Items(type="integer", format="id", example=1)
     *           ),
     *           @OA\Property(
     *               property="category_ids",
     *               description="category_ids",
     *               type="array",
     *               @OA\Items(type="integer", format="id", example=1)
     *           ),
     *       ),
     *   ),
     *   @OA\Response(
     *       response=200,
     *       description="Success"
     *   ),
     *   @OA\Response(
     *      response=422,
     *      description="Unprocessable Content - Validation",
     *   ),
     *      @OA\Response(
     *          response=404,
     *          description="Not Found"
     *      )
     * )
     */
    public function update(UpdateRequest $request, Product $product)
    {
        DB::beginTransaction();
		try {
            $data = $request->except(['image_ids', 'category_ids']);

            $product->update($data);
            if (is_array($request->image_ids)) $product->images()->sync($request->image_ids);
            if (is_array($request->category_ids)) $product->categories()->sync($request->category_ids);

			DB::commit();

            return $this->show($product);
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
     *      path="/products/{id}",
     *      operationId="deleteproduct",
     *      tags={"products"},
     *      summary="delete product by id",
     *      description="delete product by Id",
     *      @OA\Parameter(
     *          name="id",
     *          description="delete product by id",
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
    public function destroy(Product $product)
    {
        $product->categories()->detach();
        $product->images()->detach();
        $product->delete();

        return response()->noContent();
    }
}
