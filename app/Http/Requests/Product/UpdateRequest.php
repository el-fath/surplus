<?php

namespace App\Http\Requests\Product;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Response;

class UpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => 'string',
            'description' => 'string',
            'image_ids' => 'array',
            'image_ids.*' => 'integer|exists:images,id',
            'category_ids' => 'array',
            'category_ids.*' => 'integer|exists:categories,id',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        $response = response()->json([
            'success' => false,
            'message' => 'The given data was invalid.',
            'errors' => $validator->messages()
        ], Response::HTTP_UNPROCESSABLE_ENTITY);
        
        throw new HttpResponseException($response);
    }
}
