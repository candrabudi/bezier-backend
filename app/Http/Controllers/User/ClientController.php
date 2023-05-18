<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\TaskClient;
use App\Models\TaskPlanMember;
use App\Models\User;
use Illuminate\Http\Request;
use Validator;
use DB;
use Carbon\Carbon;
use Auth;

class ClientController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'full_name' => 'required',
            'email' => 'required|string|email|max:100|unique:users',
            'password' => 'required|string|confirmed|min:6',
            'phone_number' => 'required',
            'whatsapp_number' => 'required',
            'country' => 'required',
            'address' => 'required',
            'photo_profile' => 'required|mimes:jpg,jpeg,png',
            'company_name' => 'required',
            'company_email' => 'required',
            'company_number' => 'required',
            'company_website' => 'required',
            'company_description' => 'required',
        ]);
        if ($validator->fails()) {
            $response = array(
                'meta' => [
                    'status'    => 'success', 
                    'code'      => 422,
                    'message'   => 'Error Validation, Check input!'
                ],
                'data' => [
                    'error' => $validator->errors()
                ]
            );
            return response()->json($response, 422);
        }
        DB::beginTransaction();
        try{

            $first_last_name = explode(" ", $request->full_name);
            $user = new User();
            $user->full_name = $request->full_name;
            $user->first_name = $first_last_name[0];
            $user->last_name = $first_last_name[1];
            $user->email = $request->email;
            $user->password = bcrypt($request->password);
            $user->phone_number = $request->phone_number;
            $user->photo_profile_url = $request->photo_profile->store('public/user_photo_profile');
            $user->role_id = 5;
            $user->join_date = Carbon::now()->format('Y-m-d H:i:s');
            $user->save();
            $user->fresh();

            $client_detail = new Client();
            $client_detail->user_id = $user->id;
            $client_detail->phone_number = $request->phone_number;
            $client_detail->whatsapp_number = $request->whatsapp_number;
            $client_detail->country = $request->country;
            $client_detail->address = $request->address;
            $client_detail->company_name = $request->company_name;
            $client_detail->company_email = $request->company_email;
            $client_detail->company_number = $request->company_number;
            $client_detail->company_website = $request->company_website;
            $client_detail->company_description = $request->company_description;
            $client_detail->save();

            $task_member = new TaskClient();
            $task_member->client_user_id = $user->id;
            $task_member->received_at = Carbon::now()->format('Y-m-d H:i:s');
            $task_member->save();

            DB::commit();
            return response()->json([
                'meta'  => [
                    'status'    => 'success', 
                    'code'      => 201, 
                    'message'   => 'Success register client'
                ],
                'data'  => [
                    'full_name' => $user->full_name,
                    'email' => $user->email,
                    'phone_number' => $user->phone_number,
                ]
            ], 201);
        }catch(\Exception $e){
            DB::rollback();
            return response()->json([
                'meta'  => [
                    'status'    => 'failed', 
                    'code'      => 500, 
                    'message'   => $e->getMessage()
                ],
                'data' => []
                
            ], 500);
        }
    }

    public function createTaskPlan(Request $request)
    {
        $user = Auth::user();
        if($user->role_id != 5){
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
        $task = TaskClient::where('client_user_id', $user->id)
            ->select('id')
            ->first();

        if(!$task){
            $response = array(
                'meta'  => [
                    'status'    => 'failed', 
                    'code'      => 400, 
                    'message'   => 'Sorry No Data Task!'
                ],
                'data'  => []
            );

            return response()->json($response, 400);
        }
        DB::beginTransaction();
        try{

           
            $task_plan_member = new TaskPlanMember();
            $task_plan_member->task_client_user_id = $task->id;
            $task_plan_member->client_user_id = $user->id;
            $task_plan_member->received_at = Carbon::now()->format('Y-m-d H:i:s');
            $task_plan_member->save();

            DB::commit();
            $response = array(
                'meta'  => [
                    'status'    => 'success', 
                    'code'      => 201, 
                    'message'   => 'Success create task'
                ],
                'data'  => []
            );

            return response()->json($response, 201);
        }catch(\Exception $e){
            DB::rollback();
            $response = array(
                'meta'  => [
                    'status'    => 'failed', 
                    'code'      => 500, 
                    'message'   => $e->getMessage(),
                ],
                'data'  => []
            );

            return response()->json($response, 500);
        }
    }
}
