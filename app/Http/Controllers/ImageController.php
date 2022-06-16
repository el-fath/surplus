<?php

namespace App\Http\Controllers;

use App\Http\Requests\Image\StoreRequest;
use App\Http\Requests\Image\UpdateRequest;
use App\Http\Resources\ImageResource;
use App\Models\Image;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ImageController extends Controller
{
    private $location = 'files/images/';

    /**
     * @OA\Get(
     *     path="/images",
     *     summary="get list of images",
     *     description="images list",tags={"images"},
     *     @OA\Response(response="200", description="Success")
     * )
     */
    public function index()
    {
        return ImageResource::collection(Image::paginate());
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
     *      path="/images",
     *      operationId="createimage",
     *      tags={"images"},
     *      summary="create images",
     *      description="create images",
     *      @OA\RequestBody(
     *          required=true,
     *          description="create images",
     *          @OA\MediaType(
     *              mediaType="multipart/form-data",
     *              @OA\Schema(
     *                  type="object",
     *                  required={
     *                      "file",
     *                  },
     *                  @OA\Property(property="file", type="file", format="file", example=""),
     *              ),
     *          ),
     *      ),
     *      @OA\Response(
     *          response=201,
     *          description="Successfully Created",
     *       ),
     *      @OA\Response(
     *          response=422,
     *          description="Unprocessable Content - Validation",
     *       )
     * )
     */
    public function store(StoreRequest $request)
    {
        DB::beginTransaction();
		try {
            $data = $request->except('name', 'file');
            // get & rename file
            $file = $request->file;
            $filename = time() . "-" . $file->getClientOriginalName();

            // upload file
            Storage::disk('local')->put($this->location . $filename, file_get_contents($file));

            // save file
            $data['name'] = $filename;
            $data['file'] = Storage::url($this->location . $filename);
            $image = Image::create($data);

			DB::commit();

            $res = new ImageResource($image);
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
     *      path="/images/{id}",
     *      operationId="getimagebyid",
     *      tags={"images"},
     *      summary="get image by id",
     *      description="get image by id",
     *      @OA\Parameter(
     *          name="id",
     *          description="image id",
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
    public function show(Image $image)
    {
        return new ImageResource($image);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Image  $image
     * @return \Illuminate\Http\Response
     */
    public function edit(Image $image)
    {
        //
    }

    /**
     * @OA\Post(
     *      path="/images/{id}",
     *      operationId="updateimage",
     *      tags={"images"},
     *      summary="update image by id",
     *      description="update image by id",
     *      @OA\Parameter(
     *          name="id",
     *          description="image id",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *      @OA\RequestBody(
     *          required=true,
     *          description="update image by id",
     *          @OA\MediaType(
     *              mediaType="multipart/form-data",
     *              @OA\Schema(
     *                  type="object",
     *                  required={
     *                      "file",
     *                  },
     *                  @OA\Property(property="file", type="file", format="file", example=""),
     *              ),
     *          ),
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Success",
     *       ),
     *      @OA\Response(
     *          response=422,
     *          description="Unprocessable Content - Validation",
     *       ),
     *      @OA\Response(
     *          response=404,
     *          description="Not Found"
     *      )
     * )
     */
    public function update(UpdateRequest $request, Image $image)
    {
        DB::beginTransaction();
		try {
            $disk = Storage::disk('local');
            $data = $request->except('name', 'file');

            // delete old file when existing
            if ($disk->exists($this->location . $image->name)) $disk->delete($this->location . $image->name);

            // get & rename file
            $file = $request->file;
            $filename = time() . "-" . $file->getClientOriginalName();

            // upload file
            Storage::disk('local')->put($this->location . $filename, file_get_contents($file));

            // save file
            $data['name'] = $filename;
            $data['file'] = Storage::url($this->location . $filename);
            $image->update($data);

			DB::commit();

            return $this->show($image);
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
     *      path="/images/{id}",
     *      operationId="deleteimage",
     *      tags={"images"},
     *      summary="delete image by id",
     *      description="delete image by Id",
     *      @OA\Parameter(
     *          name="id",
     *          description="delete image by id",
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
    public function destroy(Image $image)
    {
        if($image->products->count() > 0) return response()->json([
            'success' => false,
            'message' => 'image stiil used by products'
        ], 422);

        $disk = Storage::disk('local');
        if ($disk->exists($this->location . $image->name)) $disk->delete($this->location . $image->name);

        $image->delete();
        return response()->noContent();
    }
}
