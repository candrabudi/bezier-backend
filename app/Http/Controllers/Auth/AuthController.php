<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Carbon\Carbon;
use Validator;

class AuthController extends Controller
{
    
    public function login(Request $request){
    	$validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string|min:6',
        ]);
        if ($validator->fails()) {
            $response = array(
                'meta' => [
                    'status'    => 'success', 
                    'code'      => 422,
                    'message'   => 'Unauthorized'
                ],
                'data' => [
                    'error' => $validator->errors()
                ]
            );
            return response()->json($response, 422);
        }
        if (! $token = auth()->attempt($validator->validated())) {
            $response = array(
                'meta' => [
                    'status'    => 'success', 
                    'code'      => 401,
                    'message'   => 'Unauthorized'
                ],
                'data' => []
            );
            return response()->json($response, 401);
        }
        $response = array(
            'meta' => [
                'status'    => 'success', 
                'code'      => 200,
                'message'   => 'Successfully login'
            ],
            'data' => [
                'access_token'  => $token,
                'token_type'    => 'bearer', 
                'expires_in'    => auth()->factory()->getTTL() * 60,
                'user'          => auth()->user()
            ]
        );
        return response()->json($response, 200);
    }

    public function register(Request $request) {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|between:2,100',
            'last_name' => 'required|string|between:2,100',
            'email' => 'required|string|email|max:100|unique:users',
            'phone' => 'required|numeric',
            'role_id' => 'required|numeric',
            'password' => 'required|string|confirmed|min:6',
        ]);

        if($validator->fails()){
            $response = array(
                'meta' => [
                    'status'    => 'failed', 
                    'code'      => 422, 
                    'message'   => 'Error validation register!'
                ],
                'data'  => [
                    'error' => $validator->errors()
                ]
            );
            return response()->json($response, 422);
        }

        $check_role = Role::where('id', $request->role_id)
            ->select('id')
            ->first();
        if(!$check_role) {
            $response = array(
                'meta' => [
                    'status'    => 'failed', 
                    'code'      => 400, 
                    'message'   => 'No Role Selected!'
                ],
                'data'  => []
            );

            return response()->json($response, 400);
        }

        $user = new User();
        $user->full_name    = $request->first_name.' '.$request->last_name;
        $user->first_name   = $request->first_name;
        $user->last_name    = $request->last_name;
        $user->email        = $request->email;
        $user->phone_number        = $request->phone;
        $user->role_id      = $request->role_id;
        $user->status       = 'Active';
        $user->password     = bcrypt($request->password);
        $user->join_date    = Carbon::now()->format('Y-m-d H:i:s');
        $user->save();
        $user->fresh();

        $response = array(
            'meta' => [
                'status'    => 'success', 
                'code'      => 201, 
                'message'   => 'Successfully Register User!'
            ],
            'data'  => [
                'first_name'    => $user->first_name,
                'last_name'     => $user->last_name, 
                'email'         => $user->email,  
            ]
        );

        return response()->json($response, 201);
    }

    public function getProfile()
    {
        $user = Auth::user();
        $response = array(
            'meta'  => [
                'status'    => 'success', 
                'code'      => 200,
                'message'   => 'Success get profile!'
            ],
            'data'  => [
                'first_name'    => $user->first_name,
                'last_name'     => $user->last_name,
                'email'         => $user->email,
                'phone'         => $user->phone,
                'title'         => $user->role->name,
                'status'        => $user->status,
                'join_date'     => $user->join_date,
                'photo_profile_url' => $user->photo_profile_url
            ]
        );
        return response()->json($response, 200);
    }

    public function logout() {
        auth()->logout();
        $response = array(
            'meta'  => [
                'status'    => 'success', 
                'code'      => 200,
                'message'   => 'Success Logout'
            ],
            'data'  => []
        );
        return response()->json($response, 200);
    }

    public function refresh() {
        $response = array(
            'meta' => [
                'status'    => 'success', 
                'code'      => 200,
                'message'   => 'Successfully Refresh Token'
            ],
            'data' => [
                'access_token'  => auth()->refresh(),
                'token_type'    => 'bearer', 
                'expires_in'    => auth()->factory()->getTTL() * 60,
                'user'          => auth()->user()
            ]
        );
        return response()->json($response, 200);
    }

}
