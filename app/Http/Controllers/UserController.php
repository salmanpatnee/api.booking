<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserController extends Controller
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
        // $sortOrder     = request('sortOrder', 'desc');
        // $orderBy       = request('orderBy', 'created_at');

        $users = User::search($term)->select('id', 'name', 'email', 'location_id')
            ->with([
                'location' => function ($q) {
                    $q->select('id', 'name');
                },
            ])->whereNotIn('name', ['Super Admin'])
            ->orderBy('created_at')
            ->paginate($paginate);

        return UserResource::collection($users);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => ['required', 'confirmed'],
            'location_id' => ['nullable', 'exists:locations,id'],
            'role' => ['required', 'string']
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => $request->password,
            'location_id' => $request->location_id,
        ]);

        $user->assignRole($request->role);

        return response()->json([
            'message'   => 'User created successfully.',
            'data'      => $user,
            'status'    => 'success'
        ], Response::HTTP_CREATED);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {

        if ($id == "me") {

            $user = auth('sanctum')->user();
            return new UserResource($user);
            // $user->load(['role'=>function($q){
            //     $q->select('id','name')->with(['permissions'=>function($q){
            //         $q->select('id', 'name');
            //     }]);
            // }]);
            // // $user->permissions = $user->role->permissions->pluck('description');
            // return response()->json(['data' => $user]);
        }

        $user = User::findOrFail($id);
        return new UserResource($user);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, User $user)
    {
        $request->validate([
            'name'      => 'required|string|max:255',
            'email'     => 'required|string|email|max:255|unique:users,email,' . $request->id,
            'password'  => ['sometimes', 'confirmed'],
            'location_id' => ['nullable', 'exists:locations,id'],
            'role' => ['required', 'string']
        ]);



        $user->update($request->all());

        $user->syncRoles([]);

        $user->assignRole($request->role);

        return response()->json([
            'message'   => 'User updated successfully.',
            'data'      => $user,
            'status'    => 'success'
        ], Response::HTTP_CREATED);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function destroy(User $user)
    {
        $user->delete();

        return response([
            'message' => 'User deleted.',
            'status'  => 'success'
        ], Response::HTTP_OK);
    }
}
