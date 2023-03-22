<?php

namespace App\Http\Controllers;

use App\Http\Resources\BrandResource;
use App\Models\Brand;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class BrandController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $paginate = request('paginate', 10);
        $term     = request('search', '');

        $brands = Brand::search($term)->select('id', 'name')
            ->orderBy('name', 'asc')
            ->paginate($paginate);
        return BrandResource::collection($brands);
        // return response()->json(['data' => $brands]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $attributes = $request->validate([
            'name' => 'required|string|unique:brands,name'
        ]);
        $attributes['products_count'] = 0;

        $brand = Brand::create($attributes);

        return response()->json([
            'message'   => 'Brand created successfully.',
            'data'      => $brand,
            'status'    => 'success'
        ], Response::HTTP_CREATED);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Brand  $brand
     * @return \Illuminate\Http\Response
     */
    public function show(Brand $brand, Request $request)
    {
        if ($request->for == 'edit') {
            return response()->json(['data' => $brand]);
        }

        // $category->load(['expenses' => function ($q) {
        //     $q->select('id', 'expense_type_id', 'date', 'description', 'amount')
        //         ->orderBy('date', 'asc')
        //         ->orderBy('created_at', 'asc');
        // }]);
        return response()->json(['data' => $brand]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Brand  $brand
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Brand $brand)
    {
        $attributes = $request->validate([
            'name' => ['required', 'string', Rule::unique('brands', 'name')->ignore($brand->id)]
        ]);

        $brand->update($attributes);

        return response()->json([
            'message' => 'Brand updated successfully.',
            'data'    => $brand,
            'status'  => 'success'
        ], Response::HTTP_OK);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Brand  $brand
     * @return \Illuminate\Http\Response
     */
    public function destroy(Brand $brand)
    {
        if ($brand->products()->count() > 0) {
            // $errors[] = ['order_details.' . $i . '.quantity' => ["Product quantity can not be greater than {$product->quantity}"]];
            // return response()->json(["message" => "The given data was invalid.", "errors" => $errors], 422);
            return response()->json(["message" => "Brand contain products", 'status' => 'failed'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $brand->forceDelete();

        return response()->json([
            'message' => 'Brand deleted successfully.',
            'status' => 'success'
        ]);
    }

    public function import()
    {
        $oldBrands = DB::connection("mysql2")->table("product_information")
            ->select(DB::raw(("DISTINCT(product_model ) as product_model")))
            ->whereNotNull("product_model")
            // ->where("status", 1)
            ->get();            

        foreach ($oldBrands as $oldBrand) {
            $brand = Brand::where("name", $oldBrand->product_model)
                ->first();

            if (!$brand) {
                Brand::create([
                    'name' => $oldBrand->product_model,

                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}
