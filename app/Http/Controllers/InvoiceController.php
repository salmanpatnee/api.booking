<?php

namespace App\Http\Controllers;

use App\Http\Resources\InvoiceResource;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class InvoiceController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $invoices = Invoice::search($request->search)->orderBy('created_at', 'desc')->paginate();
        return InvoiceResource::collection($invoices);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $attributes = $request->all();
        $invoice = [];

        if(!isset($attributes['invoice_no'])){
            $attributes['invoice_no'] = str_pad(mt_rand(1, 999999), 6, '0', STR_PAD_LEFT);
        } 

        // DB::transaction(function () use ($attributes, $invoice){
            $invoice = Invoice::create($attributes);

            foreach($attributes['invoice_item_details'] as $invoice_item){

                $invoice->invoiceItems()->create([
                    'item' => $invoice_item['item'], 
                    'amount' => $invoice_item['amount'], 
                    'qty' => $invoice_item['qty'], 
                    'vat' => $invoice_item['vat'], 
                    'sub_total' => $invoice_item['sub_total'], 
                    'net_total' => $invoice_item['net_total'], 
                ]);
            }

            return response()->json([
                'message'   => 'Invoice generated successfully.',
                'data' => $invoice,
                'status'    => 'success'
            ], Response::HTTP_CREATED);
            
        // });
        
        
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Invoice $invoice)
    {
        return new InvoiceResource($invoice);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Invoice $invoice)
    {
        $attributes = $request->all();

        $invoice->update($attributes);

        return response()->json([
            'message'   => 'Invoice updated successfully.',
            'data'      =>  new InvoiceResource($invoice),
            'status'    => 'success'
        ], Response::HTTP_OK);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
