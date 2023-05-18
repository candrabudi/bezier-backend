<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Role;
use Auth;
use Validator;
use DB;

class UserController extends Controller
{

    public function getAllTeamMembers(Request $request)
    {
        $user = Auth::user();
        if($user->role_id != 1){
            $response = array(
                'meta' => [
                    'status'    => 'success', 
                    'code'      => 403,
                    'message'   => 'Your not have to access API!'
                ],
                'data'  => []
            );

            return response()->json($response, 403);
        }

        try {
            $search = $request->search;
            $filter = $request->filter;
            $paginate = $request->paginate ?? 4;
            $planLibraries = User::when($search, function ($query) use ($search) {
                return $query->where('full_name', 'LIKE', '%' . $search . '%')
                    ->orWhere('last_name', 'LIKE', '%' . $search . '%');
                })
                ->when($filter, function ($query) use ($filter) {
                    return $query->where('status', $filter);
                })
                ->orderBy('id', 'ASC')
                ->paginate($paginate);
            return response()->json([
                'meta'  => [
                    'status'    => 'success',
                    'code'      => 200,
                    'message'   => 'Successfully get data plan library'
                ],
                'data'  => $planLibraries
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status'    => 'failed',
                'code'      => 500,
                'message'   => $e->getMessage()
            ], 500);
        }
    }

    public function updateProfile(Request $request)
    {
        DB::beginTransaction();
        try{
            $user = Auth::user();
            if($request->email){
                if($user->email != $request->email){
                    $validator = Validator::make($request->all(), [
                        'email' => 'required|string|email|max:100|unique:users',
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
                }
            }else if($request->password){
                $validator = Validator::make($request->all(), [
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
            }
            $check_role = Role::where('id', $request->id)
                ->select('id')
                ->first();
            if($request->role_id != null || $request->role_id != 0){
                if(!$check_role){
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
            }

            User::where('id', $user->id)
                ->update([
                    'first_name' => $request->first_name ?? $user->first_name,
                    'last_name' => $request->last_name ?? $user->last_name,
                    'email' => $request->email ?? $user->email,
                    'phone' => $request->phone ?? $user->phone,
                    'role_id' => $request->role_id ?? $user->role_id,
                    'photo_profile_url' => $request->photo_profile_url->store('public/photo_profile')
                ]);
            
            $response = array(
                'meta' => [
                    'status'    => 'success', 
                    'code'      => 200, 
                    'message'   => 'Sucessfully update profile!'
                ],
                'data'  => []
            );

            return response()->json($response, 200);

        }catch(\Exception $e){
            $response = array(
                'meta' => [
                    'status' => 'failed', 
                    'code'  => 500,
                    'message'   => 'Internal Server Error'
                ],
                'data'  => [
                    'error' => $e->getMessage()
                ]
            );
            return response()->json($response, 500);
        }
    }
}
