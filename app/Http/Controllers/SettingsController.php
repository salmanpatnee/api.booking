<?php

namespace App\Http\Controllers;

use App\Http\Resources\SettingResource;
use App\Models\Setting;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SettingsController extends Controller
{
    public function index()
    {
        $settings =  Setting::all();

        return SettingResource::collection($settings);
    }

    public function update(Request $request)
    {
        $attributes = $request->all();

        $company_name = Setting::where('name', 'company_name');
        $company_name->update(['value' => $attributes['company_name']]);

        $address = Setting::where('name', 'address');
        $address->update(['value' => $attributes['address']]);

        $phone = Setting::where('name', 'phone');
        $phone->update(['value' => $attributes['phone']]);

        $reviewLink = Setting::where('name', 'review_link');
        $reviewLink->update(['value' => $attributes['review_link']]);

        return response()->json([
            'message'   => 'Settings updated.',
            'status'    => 'success'
        ], Response::HTTP_OK);

    }
}
