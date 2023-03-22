<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email',
            'password' => 'required|min:6'
        ]);

        if (!auth()->attempt($data)) {
            return response()->json(["message" => "Credentials not match"], 401);
        }

        // $token = auth()->user()->createToken('API Token')->accessToken;
        $token = Auth::user()->createToken('API Token')->plainTextToken;

        // $user = auth()->user();
        // $user->load(['role'=>function($q){
        //     $q->select('id','name')->with(['permissions'=>function($q){
        //         $q->select('description');
        //     }]);
        // }]);
        // $user->permissions = $user->role->permissions->pluck('description');

        return response()->json([
            'token' => $token
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Destroy an authenticated session.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Request $request)
    {
        auth()->user()->tokens()->delete();

        return response()->json(["message" => "Logged out successfully"], 200);
    }
}
