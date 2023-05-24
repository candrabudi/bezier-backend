<?php

namespace App\Http\Controllers\Planner;

use App\Http\Controllers\Controller;
use App\Models\PlanLibrary;
use App\Models\PlanPost;
use Illuminate\Http\Request;
use Validator;
use DB;
class PlanPostController extends Controller
{
    public function storePostAdmin(Request $request)
    {
        DB::beginTransaction();
        try{
            $validate = Validator::make($request->all(), [
                'plan_library_id' => 'required',
                'planner_post' => 'required',
                'planner_caption' => 'required',
                'planner_hastag' => 'required',
            ]);
    
            if ($validate->fails()) {
                $response = array(
                    'meta' => [
                        'status'    => 'failed',
                        'code'      => 400,
                        'message'   => 'Error request!'
                    ],
                    'data' => [
                        'error' => $validate->errors()
                    ]
                );
                return response()->json($response, 400);
            }
            $check_plan_library = PlanLibrary::where('id', $request->plan_library_id)
                ->select('id', 'member_user_id')
                ->first();
            if(!$check_plan_library) {
                return response()->json([
                    'meta' => [
                        'status'    => 'failed', 
                        'code'      => 400, 
                        'message'   => 'sorry no data plan library!'
                    ],
                    'data'  => []
                ], 400);
            }
            $check_plan_post = PlanPost::where('plan_library_id', $request->plan_library_id)
                ->select('id')
                ->first();
            if($check_plan_post) {
                return response()->json([
                    'meta' => [
                        'status'    => 'failed', 
                        'code'      => 400, 
                        'message'   => 'sorry duplicate data post!'
                    ],
                    'data'  => []
                ], 400);
            }
            $planPost = new PlanPost();
            $planPost->member_user_id = $check_plan_library->member_user_id;
            $planPost->plan_library_id = $request->plan_library_id;
            $planPost->post = $request->planner_post;
            $planPost->caption = $request->planner_caption;
            $planPost->hastag = $request->planner_hastag;
            $planPost->save();

            DB::commit();
            return response()->json([
                'meta' => [
                    'status'    => 'success', 
                    'code'      => 201, 
                    'message'   => 'success store plan library!'
                ],
                'data'  => []
            ], 201);
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
            ], 500);
        }
    }
}
