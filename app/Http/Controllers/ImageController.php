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
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
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
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
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
     * Display the specified resource.
     *
     * @param  \App\Models\Image  $image
     * @return \Illuminate\Http\Response
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
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Image  $image
     * @return \Illuminate\Http\Response
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
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Image  $image
     * @return \Illuminate\Http\Response
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
