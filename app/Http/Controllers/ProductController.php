<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProductResource;
use App\Models\Image;
use App\Models\Product;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{    
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
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
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
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
     * Display the specified resource.
     *
     * @param  \App\Models\Product  $product
     * @return \Illuminate\Http\Response
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
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Product $product)
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
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function destroy(Product $product)
    {
        $product->categories()->detach();
        $product->images()->detach();
        $product->delete();
        
        return response()->noContent();
    }
}
