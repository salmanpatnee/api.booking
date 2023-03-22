<?php

namespace App\Http\Controllers;

use App\Exports\ProductsExport;
use App\Http\Resources\ProductResource;
use App\Imports\ProductsImport;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\Response;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $paginate  = request('paginate', 10);
        $term      = request('search', '');

        $sortOrder = request('sortOrder', 'desc');
        $orderBy   = request('orderBy', 'created_at');


        if ($request->for == 'purchases.create') {
            $products = Product::select('id', 'name', 'quantity', 'default_purchase_price', 'default_selling_price', 'barcode', 'uom_of_boxes', 'uom_of_strips', 'default_box_sale_price')
                ->where('name', 'like', "%{$request->search}%")
                ->with([
                    'category' => function ($q) {
                        $q->select('id', 'name');
                    },
                    'brand' => function ($q) {
                        $q->select('id', 'name');
                    },
                ]);
            $products = $products->limit(27)->get();
            return response()->json(['data' => $products]);
        }

        if ($request->for == 'purchase-orders.create') {
            $products = Product::select('id', 'name', 'quantity', 'quantity_threshold');
            if (!empty($request->alerted))
                $products->where('quantity', '<=', 'quantity_threshold');
            $products = $products->limit(10)->get();
            return $products;
        }

        if ($request->for == 'products.discount') {

            $category_id  = request('category', '');

            $products = Product::where('category_id', $category_id)->get();

            return ProductResource::collection($products);
        }

        if ($request->for == 'sales.create') {

            $products = Product::select('id', 'name', 'quantity as stock', 'default_selling_price', 'discount_rate_cash', 'discount_rate_card', 'discount_rate_shipment', 'barcode', 'default_selling_price_old');

            if (!empty($request->barcode)) {
                $products->where('barcode', $request->barcode);
                $products = $products->first();

                if (!$products) {
                    return response()->json(["message" => "No product found"], 422);
                }

                if ($products->stock === 0) {
                    return response()->json(["message" => "Product is out of stock"], 422);
                }
            } else {
                $products->where('name', 'like', "%{$request->search}%");
                if (!empty($request->category)) {
                    $products->where('category_id', '=', $request->category);
                }
                $products = $products->limit(27)->get();
            }
            // ->with([
            //     'category' => function ($q) {
            //         $q->select('id', 'name');
            //     },
            //     'brand' => function ($q) {
            //         $q->select('id', 'name');
            //     },
            // ]);

            return response()->json(['data' => $products]);
        }

        if ($request->for == 'products.stock') {

            $products = Product::select('id', 'name', 'quantity')
                ->where('quantity', '<=', $request->quantity)->orderBy($orderBy, $sortOrder)->paginate($paginate);

           
            return response()->json(['data' => $products]);

        }


       
        $products = Product::search($term)->orderBy($orderBy, $sortOrder)->paginate($paginate);

        return ProductResource::collection($products);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $userId = auth()->user()->id;
        $attributes = $request->validate([
            'name' => 'required|string|unique:products,name',
            'category_id' => 'required|exists:categories,id',
            'brand_id' => 'nullable|exists:brands,id',
            'quantity' =>  'nullable',
            'barcode' => 'required|unique:products,barcode',
            'sku' => 'nullable|unique:products,sku',
            'image' => 'nullable',
            'uom_of_boxes' => 'nullable',
            'uom_of_strips' => 'nullable',
            'description' => 'nullable',
            'vat_amount' => 'nullable',
            'quantity_threshold' => 'nullable|numeric',
            'default_purchase_price' => 'nullable|numeric',
            'default_selling_price' => 'nullable|numeric',
            'is_active' => 'required|boolean',
            'discount_rate_cash' => 'nullable|numeric',
            'discount_rate_card' => 'nullable|numeric',
            'discount_rate_shipment' => 'nullable|numeric',
        ]);

        $attributes['created_by'] =  $userId;

        DB::beginTransaction();

        $product = Product::create($attributes);

        $category = Category::find($product->category_id);
        $category->products_count = $category->products_count + 1;
        $category->save();

        DB::commit();

        return response()->json([
            'message'   => 'Product created successfully.',
            'data'      => $product,
            'status'    => 'success'
        ], Response::HTTP_CREATED);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function show(Product $product, Request $request)
    {
        if ($request->for == 'edit') {
            $product->load(['category' => function ($q) {
                $q->select('id', 'name');
            }]);
            return response()->json(['data' => $product]);
        }

        $product->load([
            'category' => function ($q) {
                $q->select('id', 'name');
            },
            'brand' => function ($q) {
                $q->select('id', 'name');
            },
            'productInventoryEntries' => function ($q) {
                $q->select('id', 'product_id', 'location_id', 'purchased_price', 'initial_quantity', 'available_quantity', 'sold_quantity', 'transferred_quantity', 'adjusted_quantity', 'expiry_date')
                    ->whereNotNull('purchased_price')
                    ->with(['location' => function ($q) {
                        $q->select('id', 'name');
                    }]);
            },
            'purchaseDetails' => function ($q) {
                $q->select('id', 'product_id', 'purchase_id', 'price', 'quantity', 'amount', 'expiry_date')
                    ->with(['purchase' => function ($q) {
                        $q->select('id', 'account_id', 'date')
                            ->with(['account' => function ($q) {
                                $q->select('id', 'name');
                            }]);
                    }]);
            },
            'saleDetails' => function ($q) {
                $q->select('id', 'product_id', 'sale_id', 'price', 'quantity', 'amount')
                    ->with(['sale' => function ($q) {
                        $q->select('id', 'account_id', 'date')
                            ->with(['account' => function ($q) {
                                $q->select('id', 'name');
                            }]);
                    }]);
            },
        ]);
        return response()->json(['data' => $product]);
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
        $oldCategoryId = $product->category_id;
        $attributes = $request->validate([
            'name' => ['required', 'string', Rule::unique('products', 'name')->ignore($product->id)],
            'category_id' => 'required|exists:categories,id',
            'brand_id' => 'nullable|exists:brands,id',
            'quantity' =>  'nullable',
            'barcode' => ['required', Rule::unique('products', 'barcode')->ignore($product->id)],
            'sku' => ['nullable', Rule::unique('products', 'sku')->ignore($product->id)],
            'image' => 'nullable',
            'uom_of_boxes' => 'nullable',
            'uom_of_strips' => 'nullable',
            'description' => 'nullable',
            'vat_amount' => 'nullable',
            'quantity_threshold' => 'nullable|numeric',
            'default_purchase_price' => 'nullable|numeric',
            'default_selling_price' => 'nullable|numeric',
            'is_active' => 'required|boolean',
            'discount_rate_cash' => 'nullable|numeric',
            'discount_rate_card' => 'nullable|numeric',
            'discount_rate_shipment' => 'nullable|numeric',
        ]);

        DB::beginTransaction();
        $product->update($attributes);

        $newCategoryId = $attributes['category_id'];
        if ($oldCategoryId != $newCategoryId) {
            $oldCategory = Category::find($oldCategoryId);
            $oldCategory->products_count = $oldCategory->products_count - 1;
            $oldCategory->save();

            $newCategory = Category::find($newCategoryId);
            $newCategory->products_count = $newCategory->products_count + 1;
            $newCategory->save();
        }


        DB::commit();

        return response()->json([
            'message'   => 'Product updated successfully.',
            'data'      => $product,
            'status'    => 'success'
        ], Response::HTTP_OK);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function destroy(Product $product)
    {
        //
    }

    public function export()
    {
        // return (new ProductsExport($students));

        return Excel::download(new ProductsExport, 'products.xlsx');
    }

    public function import()
    {
        DB::connection("mysql2")->table("product_information")
            ->select("id", "barcode", "product_id", "category_id", "product_name", "price", "cash_discount_rate", "card_discount_rate", "delivery_discount_rate", "discount_locked", "discount_assigned_at", "low_stock_threshold", "product_model", "status")
            // ->where("status", 1)
            ->orderBy('id')->chunk(500, function ($oldProducts) {
                foreach ($oldProducts as $oldProduct) {
                    $product = Product::where('ref_id', $oldProduct->product_id)
                        ->first();

                    if (!$product) {
                        $category = Category::where('ref_id', $oldProduct->category_id)->first();
                        $brandId = null;
                        if (!empty($oldProduct->product_model)) {
                            $brand = Brand::where('name', $oldProduct->product_model)->first();
                            if (!$brand) {
                                dd($oldProduct);
                            }
                            $brandId = $brand->id;
                        }

                        Product::create([
                            'name' => $oldProduct->product_name,
                            'category_id' => $category ? $category->id : Category::UNCATEGORIZED_ID,
                            'brand_id' => $brandId,
                            'barcode' => $oldProduct->product_id,
                            'quantity_threshold' => $oldProduct->low_stock_threshold,
                            'default_purchase_price' => null,
                            'default_selling_price' => $oldProduct->price,
                            'is_active' => true,

                            'discount_rate_cash' => $oldProduct->cash_discount_rate,
                            'discount_rate_card' => $oldProduct->card_discount_rate,
                            'discount_rate_shipment' => $oldProduct->delivery_discount_rate,
                            'discount_assigned_at' => $oldProduct->discount_assigned_at,
                            'is_locked' => $oldProduct->discount_locked,

                            'ref_id' => $oldProduct->product_id,

                            'created_at' => now(),
                            'updated_at' => now(),

                            'created_by' => 1,
                        ]);
                    }
                }
            });
    }

    public function excelImport(Request $request)
    {
        // dd($request->all());
        Excel::import(new ProductsImport, request()->file('products'));
    }

    public function fixQuantity()
    {
        $products = Product::select("id", "name", "quantity", DB::raw("(select sum(available_quantity) from product_inventory_entries where product_id = products.id) as stock"))
            ->havingRaw("quantity != stock")
            ->get();
        foreach ($products as $product) {
            $product->quantity = $product->stock;
            $product->save();
        }
        unset($product);
        return response()->json([
            'message'   => 'Stock fixed successfully.',
            'data'      => $products,
            'status'    => 'success'
        ], Response::HTTP_OK);
    }
}
