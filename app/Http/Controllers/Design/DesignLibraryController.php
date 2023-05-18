<?php

namespace App\Http\Controllers\Design;

use App\Http\Controllers\Controller;
use App\Models\DesignCtageory;
use App\Models\DesignLibrary;
use App\Models\MemberAssign;
use App\Models\User;
use Illuminate\Http\Request;
use Carbon\Carbon;
use DB;
use Auth;
use Validator;

class DesignLibraryController extends Controller
{

    public function getAllDesignLibrary(Request $request)
    {
        try {
            $user = Auth::user();
            $search = $request->search;
            $filter = $request->filter;
            $paginate = $request->paginate ?? 4;
            $planLibraries = DesignLibrary::when($search, function ($query) use ($search) {
                    return $query->where('design_title', 'LIKE', '%' . $search . '%');
                })
                ->when($user->role_id != 1, function($query) use ($user) {
                    return $query('member_user_id', $user->id);
                })
                ->when($filter, function ($query) use ($filter) {
                    return $query->where('status', $filter);
                })
                ->with('member', 'client', 'approvedBy')
                ->orderBy('id', 'ASC')
                ->paginate($paginate);

            return response()->json([
                'meta'  => [
                    'status'    => 'success',
                    'code'      => 200,
                    'message'   => 'Successfully get data design library'
                ],
                'data'  => $planLibraries
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'meta' => [
                    'status'    => 'failed',
                    'code'      => 500,
                    'message'   => 'Internal Server Error'
                ],
                'data' => [
                    'error' => $e->getMessage()
                ]
            ], 500);
        }
    }
    public function store(Request $request)
    {
        $user = Auth::user();
        if($user->role_id != 1){
            if($user->role_id != 2){
                if($user->role_id != 3){
                    $response = array(
                        'meta'  => [
                            'status'    => 'failed', 
                            'code'      => 400, 
                            'message'   => 'Sorry you not have access!'
                        ],
                        'data'  => []
                    );
        
                    return response()->json($response, 400);
                }
            }
        }

        DB::beginTransaction();
        try{
            $validator = Validator::make($request->all(), [
                'client_user_id' => 'required',
                'member_user_id' => 'required',
                'design_title' => 'required',
                'category'  => 'required', 
                'image' => 'required|mimes:jpg,jpeg,png'
            ]);
            if($validator->fails()){
                $response = array(
                    'meta' => [
                        'status'    => 'failed', 
                        'code'      => 422, 
                        'message'   => 'Error validation store design library!'
                    ],
                    'data'  => [
                        'error' => $validator->errors()
                    ]
                );
                return response()->json($response, 422);
            }

            $client = User::where('id', $request->client_user_id)
                ->select('id')
                ->first();
            if(!$client){
                $response = array(
                    'meta' => [
                        'status'    => 'failed', 
                        'code'      => 400, 
                        'message'   => 'Sorry no data client!'
                    ],
                    'data'  => []
                );
                return response()->json($response, 400);
            }

            $member = User::where('users.id', $request->member_user_id)
                ->join('roles', 'roles.id', '=', 'users.role_id')
                ->where('roles.name', 'Designer')
                ->select('users.id')
                ->first();
            if(!$member){
                $response = array(
                    'meta' => [
                        'status'    => 'failed', 
                        'code'      => 400, 
                        'message'   => 'Sorry no data member!'
                    ],
                    'data'  => []
                );
                return response()->json($response, 400);
            }

            $task_design = User::where('id', $request->client_user_id)
                ->select('id')
                ->first();
            if(!$task_design){
                $response = array(
                    'meta' => [
                        'status'    => 'failed', 
                        'code'      => 400, 
                        'message'   => 'Sorry no data task design!'
                    ],
                    'data'  => []
                );
                return response()->json($response, 400);
            }

            $check_category = DesignCtageory::where('name', $request->category)
                ->select('id')
                ->first();
            if(!$check_category){
                $category = new DesignCtageory();
                $category->name = $request->category;
                $category->save();
                $category->fresh();

                $category_id = $category->id;
            }else{
                $category_id = $check_category->id;
            }

            $designLibrary = new DesignLibrary();
            $designLibrary->task_design_id = 
            $designLibrary->client_user_id = $request->client_user_id;
            $designLibrary->member_user_id = $request->member_user_id;
            $designLibrary->category_id = $category_id;
            $designLibrary->design_title = $request->design_title;
            $designLibrary->image_path = $request->image->store('public/design_library');
            $designLibrary->save();

            DB::commit();
            return response()->json([
                'meta' => [
                    'status'    => 'success',
                    'code'      => 200, 
                    'message'   => 'Successfully store plan library!'
                ],
                'data'  => []
            ]);

        }catch(\Exception $e){
            DB::rollback();
            return response()->json([
                'meta' => [
                    'status'    => 'failed', 
                    'code'      => 500, 
                    'message'   => 'Internal Server Error!'
                ],
                'data'  => [
                    'error' => $e->getMessage()
                ]
            ]);
        }
    }

    public function designApprove($id)
    {
        DB::beginTransaction();
        try {
            $user = Auth::user();
            if($user->role_id != 1){
                if($user->role_id != 2){
                    $response = array(
                        'meta'  => [
                            'status'    => 'failed', 
                            'code'      => 400, 
                            'message'   => 'Sorry you not have access!'
                        ],
                        'data'  => []
                    );
    
                    return response()->json($response, 400);
                }
            }
            $designLibrary = DesignLibrary::where('design_libraries.id', $id)
                ->join('users', 'users.id', '=', 'design_libraries.member_user_id')
                ->join('roles', 'roles.id', '=', 'users.role_id')
                ->where('roles.name', 'Designer')
                ->select('design_libraries.id as id', 'design_libraries.status as status', 'client_user_id', 'member_user_id')
                ->first();
            if(!$designLibrary){
                return response()->json([
                    'meta' => [
                        'status'    => 'failed', 
                        'code'      => 422, 
                        'message'   => 'Sorry no data design library!'
                    ],
                    'data'  => []
                ], 422);
            }
            if($designLibrary->status == 'approved'){
                return response()->json([
                    'meta' => [
                        'status'    => 'failed', 
                        'code'      => 400, 
                        'message'   => 'Sorry design library has been approve!'
                    ],
                    'data'  => []
                ], 400);
            }
            $client = User::findOrFail($designLibrary->client_user_id);
            if(!$client) {
                return response()->json([
                    'meta' => [
                        'status'    => 'failed', 
                        'code'      => 422, 
                        'message'   => 'Sorry no data brands!'
                    ],
                    'data'  => []
                ], 422);
            }
            DesignLibrary::where('id', $id)
                ->update([
                    'status'        => 'approved',
                    'approved_at'   => Carbon::now()->format('Y-m-d H:i:s'),
                    'approved_by'   => Auth::id()
                ]);
            
            $check_member_assign = MemberAssign::where('client_user_id', $designLibrary->client_user_id)
                ->where('member_user_id', $designLibrary->member_user_id)
                ->first();
            if(!$check_member_assign){
                $memberAssign = new MemberAssign();
                $memberAssign->client_user_id = $designLibrary->client_user_id;
                $memberAssign->member_user_id = $designLibrary->member_user_id;
                $memberAssign->save();
            }

            DB::commit();
            return response()->json([
                'meta'  => [
                    'status'    => 'success', 
                    'code'      => 200, 
                    'message'   => 'Successfully Approve plan library!'
                ],
                'data' => []
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'meta'  => [
                    'status'    => 'failed', 
                    'code'      => 500, 
                    'message'   => 'Internal Server Error!'
                ],
                'data' => [
                    'error' => $e->getMessage()
                ]
            ], 500);
        }
    }

    public function deleteDesign($id)
    {
        DB::beginTransaction();
        try {
            $user = Auth::user();
            if($user->role_id != 1){
                if($user->role_id != 2){
                    $response = array(
                        'meta'  => [
                            'status'    => 'failed', 
                            'code'      => 400, 
                            'message'   => 'Sorry you not have access!'
                        ],
                        'data'  => []
                    );
    
                    return response()->json($response, 400);
                }
            }
            $designLibrary = DesignLibrary::where('id', $id)
                ->select('id', 'status', 'image_path')
                ->first();
            if(!$designLibrary){
                return response()->json([
                    'meta' => [
                        'status'    => 'failed', 
                        'code'      => 400, 
                        'message'   => 'sorry no data design library!'
                    ],
                    'data'  => []
                ], 400);
            }
            if($designLibrary->status == "approved"){
                return response()->json([
                    'meta' => [
                        'status'    => 'failed', 
                        'code'      => 400, 
                        'message'   => 'sorry can`t delete status has been approved!'
                    ],
                    'data'  => []
                ], 400);
            }
            $image_path = str_replace(env('APP_URL')."/","",$designLibrary->image_path);
            if (\File::exists(public_path($image_path))) {
                \File::delete(public_path($image_path));
            }
            DesignLibrary::where('id', $id)->delete();
            DB::commit();
            return response()->json([
                'meta' => [
                    'status'    => 'success', 
                    'code'      => 200, 
                    'message'   => 'success delete design library'
                ],
                'data'  => []
            ], 200);
        }catch(\Exception $e) {
            DB::rollback();
            return response()->json([
                'meta' => [
                    'status'    => 'failed', 
                    'code'      => 500, 
                    'message'   => 'Internal Server Error!'
                ],
                'data'  => [
                    'error' => $e->getMessage()
                ]
            ], 500);
        }
    }
}
