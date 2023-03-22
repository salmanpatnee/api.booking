<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AttendanceController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $paginate = request('paginate', 10);
        // $term     = request('search', '');
        // $sortOrder     = request('sortOrder', 'desc');
        // $orderBy       = request('orderBy', 'created_at');
        $date = $request->date ?? now()->format("Y-m-d");
        $attendances = Attendance::select('id', 'user_id', 'date', 'time_in', 'time_out')
            ->where("date", $date)
            ->with([
                'user' => function ($q) {
                    $q->select('id', 'name');
                },
            ])
            ->orderBy('date')
            ->paginate($paginate);
        return response()->json(['data' => $attendances]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // Attendance::truncate();
        /* In future it should be done with auth */
        $request->validate([
            'user_id' => "required|exists:users,id",
        ]);
        // $user = User::find($request->user_id);
        $attendance = Attendance::where('user_id', $request->user_id)
            ->where("date", now()->format('Y-m-d'))
            ->first();


        if ($attendance) {
            /* time out */
            if ($attendance->time_out != null) {
                return response()->json([
                    "message" => "This user is already timed out",
                    "errors" => []
                ], 422);
            }
            $attendance->time_out = now();
            $attendance->save();
            return response()->json([
                'message'   => 'User timed out successfully.',
                'data'      => $attendance,
                'status'    => 'success'
            ], Response::HTTP_OK);
        } else {
            /* time in */
            $attendance = Attendance::create([
                "user_id" => $request->user_id,
                "date" => now(),
                "time_in" => now()
            ]);
            return response()->json([
                'message'   => 'Attendance marked successfully.',
                'data'      => $attendance,
                'status'    => 'success'
            ], Response::HTTP_CREATED);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Attendance  $attendance
     * @return \Illuminate\Http\Response
     */
    public function show(Attendance $attendance)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Attendance  $attendance
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Attendance $attendance)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Attendance  $attendance
     * @return \Illuminate\Http\Response
     */
    public function destroy(Attendance $attendance)
    {
        //
    }
}
