<?php

namespace App\Http\Controllers;

use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class CategoryController extends Controller
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

        $categories = Category::search($term)->select('id', 'name')
            ->orderBy('name', 'asc');

        if (request('for') == 'sales.create') {
            $categories = $categories->has('products')->get();
            return CategoryResource::collection($categories);
        }

        $categories = $categories->paginate($paginate);
        return CategoryResource::collection($categories);
        // return response()->json(['data' => $categories]);
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
            'name' => 'required|string|unique:categories,name'
        ]);
        $attributes['products_count'] = 0;

        $category = Category::create($attributes);

        return response()->json([
            'message'   => 'Category created successfully.',
            'data'      => $category,
            'status'    => 'success'
        ], Response::HTTP_CREATED);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Category  $category
     * @return \Illuminate\Http\Response
     */
    public function show(Category $category, Request $request)
    {
        if ($request->for == 'edit') {
            return response()->json(['data' => $category]);
        }

        // $category->load(['expenses' => function ($q) {
        //     $q->select('id', 'expense_type_id', 'date', 'description', 'amount')
        //         ->orderBy('date', 'asc')
        //         ->orderBy('created_at', 'asc');
        // }]);
        return response()->json(['data' => $category]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Category  $category
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Category $category)
    {
        $attributes = $request->validate([
            'name' => ['required', 'string', Rule::unique('categories', 'name')->ignore($category->id)]
        ]);

        $category->update($attributes);

        return response()->json([
            'message' => 'Category updated successfully.',
            'data'    => $category,
            'status'  => 'success'
        ], Response::HTTP_OK);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Category  $category
     * @return \Illuminate\Http\Response
     */
    public function destroy(Category $category)
    {
        if ($category->products()->count() > 0) {
            // $errors[] = ['order_details.' . $i . '.quantity' => ["Product quantity can not be greater than {$product->quantity}"]];
            // return response()->json(["message" => "The given data was invalid.", "errors" => $errors], 422);
            return response()->json(["message" => "Category contain products", 'status' => 'failed'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $category->forceDelete();

        return response()->json([
            'message' => 'Category deleted successfully.',
            'status' => 'success'
        ]);
    }

    public function import()
    {
        $oldCategories = DB::connection("mysql2")->table("product_category")
            ->select("category_id", "category_name")
            ->orderBy('category_id')
            ->get();

        foreach ($oldCategories as $oldCategory) {



            $category = Category::where("ref_id", $oldCategory->category_id)
                ->first();

            if (!$category) {
                Category::create([
                    'name' => $oldCategory->category_name,

                    'created_at' => now(),
                    'updated_at' => now(),

                    'ref_id' => $oldCategory->category_id
                ]);
            }
        }
    }
}
