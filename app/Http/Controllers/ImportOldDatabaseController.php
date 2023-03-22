<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ImportOldDatabaseController extends Controller
{
    public function index() {
        (new SupplierController)->import();
        (new CategoryController)->import();
        (new BrandController)->import();
        (new ProductController)->import();
    }
}
