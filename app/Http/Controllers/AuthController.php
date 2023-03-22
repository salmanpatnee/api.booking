<?php

namespace App\Http\Controllers;

use App\Http\Requests\AuthRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends Controller
{
    public function login(AuthRequest $request)
    {
        try {

            if (!Auth::attempt($request->all())) {

                return response()->json([
                    'status_code' => Response::HTTP_UNAUTHORIZED,
                    'message' => 'Email or Password is not valid.'
                ]);
            }

            $user = Auth::user();
            /* Checking active or not
            if (!$user->status) {

                Auth::logout();

                return response()->json([
                    'status_code' => Response::HTTP_UNAUTHORIZED,
                    'message' => 'Account is not active.'
                ]);
            }
            */


            $token = $user->createToken('authToken')->accessToken;

            return $token;
            /* Updating last login time and ip
            $user->update([
                'last_login_at' => Carbon::now()->toDateTimeString(),
                'last_login_ip' => $request->getClientIp()
            ]);
            */


            return response()->json([
                'status_code'  => Response::HTTP_OK,
                'access_token' => $token,
                'token_type'   => 'Bearer'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message'     => 'Something went wrong.',
                'error'       => $th
            ]);
        }
    }
}
