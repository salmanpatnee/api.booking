<?php

namespace App\Http\Controllers;

use App\Http\Resources\LocationResource;
use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class LocationController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $locations = Location::orderBy('name', 'asc')->get();
        return LocationResource::collection($locations);
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
            'name' => "required|string|unique:locations,name",
            'address' => 'required',
            'city' => 'required',
            'phone' => 'required',
            'email' => 'nullable|email|unique:locations,email',
            'is_head_office' => 'nullable|boolean'
        ]);

        $location = Location::create($attributes);

        return response()->json([
            'message'   => 'Location created successfully.',
            'data'      => $location,
            'status'    => 'success'
        ], Response::HTTP_CREATED);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Location  $location
     * @return \Illuminate\Http\Response
     */
    public function show(Location $location)
    {
        return response()->json(['data' => $location]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Location  $location
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Location $location)
    {
        $attributes = $request->validate([
            'name' => ['required', 'string', Rule::unique('locations', 'name')->ignore($location->id)],
            'address' => 'required',
            'city' => 'required',
            'phone' => 'required',
            'email' => ['nullable', 'email', Rule::unique('locations', 'email')->ignore($location->id)],
        ]);

        $location->update($attributes);

        return response()->json([
            'message' => 'Location updated successfully.',
            'data'    => $location,
            'status'  => 'success'
        ], Response::HTTP_OK);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Location  $location
     * @return \Illuminate\Http\Response
     */
    public function destroy(Location $location)
    {
        //
    }
}
