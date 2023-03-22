<?php

namespace App\Http\Controllers;

use App\Models\ProductInventoryEntry;
use App\Models\ProductInventoryOutflow;
use Illuminate\Http\Request;

class ProductInventoryEntryController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        
        $paginate = request('paginate', 20);
        $term     = request('search', '');
        // $sortOrder     = request('sortOrder', 'desc');
        // $orderBy       = request('orderBy', 'created_at');

        $productInventoryEntries = ProductInventoryEntry::search($term)
            ->where('reference_type', ProductInventoryOutflow::class)
            ->orderBy('date')
            ->with([
                'location' => function ($q) {
                    $q->select('id', 'name');
                },
                'product' => function ($q) {
                    $q->select('id', 'name');
                }
            ])
            ->paginate($paginate);
        return response()->json(['data' => $productInventoryEntries]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\ProductInventoryEntry  $productInventoryEntry
     * @return \Illuminate\Http\Response
     */
    public function show(ProductInventoryEntry $productInventoryEntry)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\ProductInventoryEntry  $productInventoryEntry
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, ProductInventoryEntry $productInventoryEntry)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\ProductInventoryEntry  $productInventoryEntry
     * @return \Illuminate\Http\Response
     */
    public function destroy(ProductInventoryEntry $productInventoryEntry)
    {
        //
    }
}
