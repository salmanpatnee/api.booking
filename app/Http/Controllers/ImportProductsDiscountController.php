<?php

namespace App\Http\Controllers;

use App\Imports\ProductsDiscountImport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ImportProductsDiscountController extends Controller
{
    public function index()
    {
        Excel::import(new ProductsDiscountImport, request()->file('products'));
    }
}
