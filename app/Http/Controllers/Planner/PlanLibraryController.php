<?php

namespace App\Http\Controllers\Planner;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\MemberAssign;
use Illuminate\Http\Request;
use App\Models\PlanLibrary;
use App\Models\TaskClient;
use App\Models\TaskPlanMember;
use App\Models\User;
use Maatwebsite\Excel\Concerns\ToArray;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Validator;
use DB;
use Auth;
use Carbon\Carbon;

class PlanLibraryController extends Controller
{
    public function getAllPlanLibrary(Request $request)
    {
        try {
            $user = Auth::user();
            if($user->role_id != 1){
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
            $search = $request->search;
            $filter = $request->filter;
            $paginate = $request->paginate ?? 4;
            $planLibraries = planLibrary::when($search, function ($query) use ($search) {
                return $query->where('plan_title', 'LIKE', '%' . $search . '%')
                    ->orWhere('plan_description', 'LIKE', '%' . $search . '%')
                    ->orWhere('plan_promp', 'LIKE', '%' . $search . '%');
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
                    'message'   => 'Successfully get data plan library'
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

    public function storePlanLibrary(Request $request)
    {
        
        DB::beginTransaction();
        try{
            $user = Auth::user();
            if($user->role_id != 1){
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
            $validator = Validator::make($request->all(), [
                'task_id' => 'required',
                'client_user_id' => 'required',
                'member_user_id' => 'required',
                'plan_title' => 'required',
                'plan_description'  => 'required', 
                'plan_prompt'   => 'required'
            ]);
            if($validator->fails()){
                $response = array(
                    'meta' => [
                        'status'    => 'failed', 
                        'code'      => 422, 
                        'message'   => 'Error validation store plan library!'
                    ],
                    'data'  => [
                        'error' => $validator->errors()
                    ]
                );
                return response()->json($response, 422);
            }
    
            $task = TaskClient::where('id', $request->task_id)->first();
            if(!$task){
                $response = array(
                    'meta' => [
                        'status'    => 'failed', 
                        'code'      => 400, 
                        'message'   => 'Sorry no data task!'
                    ],
                    'data'  => []
                );
                return response()->json($response, 400);
            }
    
            $client = User::where('id', $request->client_user_id)->first();
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
    
            $member = User::where('id', $request->member_user_id)->first();
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
    
            $task_plan = User::where('id', $client->id)->first();
            if(!$member){
                $response = array(
                    'meta' => [
                        'status'    => 'failed', 
                        'code'      => 400, 
                        'message'   => 'Sorry no data Task Plan!'
                    ],
                    'data'  => []
                );
                return response()->json($response, 400);
            }

            $planLibrary = new PlanLibrary();
            $planLibrary->task_plan_id = $task_plan->id;
            $planLibrary->client_user_id = $request->client_user_id;
            $planLibrary->member_user_id = $request->member_user_id;
            $planLibrary->plan_title = $request->plan_title;
            $planLibrary->plan_description = $request->plan_description;
            $planLibrary->plan_prompt = $request->plan_prompt;
            $planLibrary->status = 'Pending';
            $planLibrary->save();

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

    public function bulkSotrePlanLibrary(Request $request)
    {

        
        DB::beginTransaction();
        try {
            $user = Auth::user();
            if($user->role_id != 1){
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
            $data_bulk = json_decode(file_get_contents('php://input'));
            if (!$data_bulk) {
                return response()->json([
                    'status'    => 'failed',
                    'code'      => 400,
                    'message'   => 'please input bulk plan library data'
                ], 400);
            }
            $error_data = array();
            $error_validation = array();
            foreach ($data_bulk as $index => $row) {
                if (empty($row->plan_title)
                    || empty($row->plan_description)
                    || empty($row->plan_prompt)
                    || empty($row->member_user_id)
                    || empty($row->client_user_id)
                ) {
                    array_push($error_data, $index + 1);
                }
    
                if (!empty($row->plan_title)
                    || !empty($row->plan_description)
                    || !empty($row->plan_prompt)
                    || !empty($row->member_user_id)
                    || !empty($row->client_user_id)
                ) {
                    $client = User::where('id', $row->client_user_id)
                        ->select('id')
                        ->first();
                    $member = User::where('users.id', $row->member_user_id)
                        ->join('roles', 'roles.id', '=', 'users.role_id')
                        ->where('roles.name', 'Planner')
                        ->select('users.id')
                        ->first();
                    if($client){
                        $task = TaskPlanMember::where('client_user_id', $client->id)
                            ->select('id')
                            ->first();
                    }else{
                        $task = '';
                    }
                    if(!$client || !$member || !$task){
                        $data_validation = array(
                            'line'  => $index + 1,
                            'client' => !$client ? 'Sorry Please Check Client Before Submit!' : '',
                            'member' => !$member ? 'Sorry Please Check Member Before Submit!' : '',
                            'task' => !$task ? 'Sorry Please Check Task Before Submit!' : '',
                        );
                        array_push($error_validation, $data_validation);
                    }
                }
            }
    
            if (count($error_data) >= 1 || count($error_validation) >= 1) {
                return response()->json([
                    'meta' => [
                        'status'    => 'failed',
                        'code'      => 400,
                        'message'   => 'Please check your input!',
                    ],
                    'data' => [
                        'error_line'     => $error_data,
                        'error_validation' => $error_validation
                    ]
                ], 400);
            }
            
            foreach ($data_bulk as $row) {
                $task_plan = TaskPlanMember::where('client_user_id', $row->client_user_id)
                    ->select('id')
                    ->first();
                $plan_library = new PlanLibrary();
                $plan_library->task_plan_id = $task_plan->id;
                $plan_library->client_user_id = $row->client_user_id;
                $plan_library->member_user_id = $row->member_user_id;
                $plan_library->plan_title = $row->plan_title;
                $plan_library->plan_description = $row->plan_description;
                $plan_library->plan_prompt = $row->plan_prompt;
                $plan_library->status = "Pending";
                $plan_library->save();
            }

            DB::commit();
            return response()->json([
                'meta' => [
                    'status'    => 'success',
                    'code'      => 201,
                    'message'   => 'Successfully store bulk plan library'
                ],
                'data' => []
            ], 201);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'status'    => 'failed',
                'code'      => 500,
                'message'   => $e->getMessage()
            ], 500);
        }
    }

    public function importExcelPlanLibrary(Request $request)
    {
        DB::beginTransaction();
        try {
            $user = Auth::user();
            if($user->role_id != 1){
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
            $validate = Validator::make($request->all(), [
                'import_file' => 'required|mimes:csv,xls,xlsx',
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
    
            $fileName = time() . '_' . $request->import_file->getClientOriginalName();
            $request->file('import_file')->storeAs('upload_excel_temps', $fileName, 'public');
    
            $file = 'storage/upload_excel_temps/' . $fileName;
            $data = Excel::toArray(new class implements ToArray
            {
                public function array(array $array)
                {
                    return $array;
                }
            }, $file);
    
            $data_import = array_splice($data[0], 1, 9999);
            if (\File::exists(public_path('storage/upload_excel_temps/' . $fileName))) {
                \File::delete(public_path('storage/upload_excel_temps/' . $fileName));
            }
            if (count($data_import) > 150) {
                $response = array(
                    'meta' => [
                        'status'    => 'failed',
                        'code'      => 400,
                        'message'   => 'Sorry max import data excel 150!'
                    ],
                    'data' => []
                );
    
                return response()->json($response, 400);
            }
            $check_error = array();
            $error_validation = array();
            foreach ($data_import as $key => $row) {
                if (!$row[0] || !$row[1] || !$row[2] || !$row[3] || !$row[4]) {
                    array_push($check_error, $key + 1);
                }
    
                if($row[0] || $row[1] || $row[2] || $row[3] || $row[4]){
                    $client = User::where('email', $row[0])
                        ->select('id')
                        ->first();
                    $member = User::where('email', $row[1])
                        ->join('roles', 'roles.id', '=', 'users.id')
                        ->where('name', 'Planner')
                        ->select('users.id')
                        ->first();
    
                    if($client){
                        $task = TaskClient::where('client_user_id', $client->id)
                            ->select('id')
                            ->first();
                    }else{
                        $task = '';
                    }
                    
                    if(!$client || !$member || !$task){
                        $data_validation = array(
                            'line'  => $key + 1,
                            'validation_client' => !$client ? 'Sorry Please Check Client Before Submit!' : '',
                            'validation_member' => !$member ? 'Sorry Please Check Member Before Submit!' : '',
                            'validation_task' => !$task ? 'Sorry Please Check Task Before Submit!' : '',
                        );
                        array_push($error_validation, $data_validation);
                    }
                }
            }
    
            if (count($check_error) > 0 || count($error_validation) > 0) {
                $response = array(
                    'meta' => [
                        'status'    => 'failed',
                        'code'      => 422,
                        'message'   => 'Please check your input!'
                    ],
                    'data' => [
                        'error' => [
                            'error_line' => $check_error,
                            'error_validation' => $error_validation
                        ]
                    ]
                );
    
                return response()->json($response, 422);
            }

            foreach ($data_import as $row) {
                $client = User::where('email', $row[0])
                    ->select('id')
                    ->first();
                $member = User::where('email', $row[1])
                    ->select('id')
                    ->first();
                $task = TaskPlanMember::where('client_user_id', $client->id)
                    ->select('id')
                    ->first();
                $planLibrary = new PlanLibrary();
                $planLibrary->task_plan_id = $task->id;
                $planLibrary->client_user_id = $client->id;
                $planLibrary->member_user_id = $member->id;
                $planLibrary->plan_title = $row['2'];
                $planLibrary->plan_description = $row['3'];
                $planLibrary->plan_prompt = $row['4'];
                $planLibrary->save();
            }

            DB::commit();
            return response()->json([
                'meta' => [
                    'status'    => 'success',
                    'code'      => 200,
                    'message'   => 'Successfully import data plan library!'
                ],
                'data'  => []
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'meta' => [
                    'status'    => 'failed',
                    'code'      => 500,
                    'message'   => $e->getMessage()
                ],
                'data'  => []
            ], 500);
        }
    }

    public function planApprove($id)
    {
        DB::beginTransaction();
        try {
            $user = Auth::user();
            if($user->role_id != 1){
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
            $planLibrary = planLibrary::where('plan_libraries.id', $id)
                ->join('users', 'users.id', '=', 'plan_libraries.member_user_id')
                ->join('roles', 'roles.id', '=', 'users.role_id')
                ->where('roles.name', 'Planner')
                ->select('plan_libraries.id as id', 'plan_libraries.status as status')
                ->first();
            if(!$planLibrary){
                return response()->json([
                    'meta' => [
                        'status'    => 'failed', 
                        'code'      => 422, 
                        'message'   => 'Sorry no data plan library!'
                    ],
                    'data'  => []
                ], 422);
            }
            if($planLibrary->status == 'approved'){
                return response()->json([
                    'meta' => [
                        'status'    => 'failed', 
                        'code'      => 400, 
                        'message'   => 'Sorry plan library has been approve!'
                    ],
                    'data'  => []
                ], 400);
            }
            $brands = User::findOrFail($planLibrary->client_user_id);
            if(!$brands) {
                return response()->json([
                    'meta' => [
                        'status'    => 'failed', 
                        'code'      => 422, 
                        'message'   => 'Sorry no data brands!'
                    ],
                    'data'  => []
                ], 422);
            }
            PlanLibrary::where('id', $id)
                ->update([
                    'status'        => 'approved',
                    'approved_at'   => Carbon::now()->format('Y-m-d H:i:s'),
                    'approved_by'   => Auth::id()
                ]);
            
            $check_member_assign = MemberAssign::where('client_user_id', $planLibrary->client_user_id)
                ->where('member_user_id', $planLibrary->member_user_id)
                ->first();
            if(!$check_member_assign){
                $memberAssign = new MemberAssign();
                $memberAssign->client_user_id = $planLibrary->client_user_id;
                $memberAssign->member_user_id = $planLibrary->member_user_id;
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
    public function deletePlan($id)
    {
        DB::beginTransaction();
        try {
            $user = Auth::user();
            if($user->role_id != 1){
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
            $planLibrary = PlanLibrary::where('id', $id)
                ->select('id', 'status')
                ->first();
            if(!$planLibrary){
                return response()->json([
                    'meta' => [
                        'status'    => 'failed', 
                        'code'      => 400, 
                        'message'   => 'sorry no data plan library!'
                    ],
                    'data'  => []
                ], 400);
            }
            if($planLibrary->status == "approved"){
                return response()->json([
                    'meta' => [
                        'status'    => 'failed', 
                        'code'      => 400, 
                        'message'   => 'sorry can`t delete status has been approved!'
                    ],
                    'data'  => []
                ], 400);
            }

            PlanLibrary::where('id', $id)->delete();
            DB::commit();
            return response()->json([
                'meta' => [
                    'status'    => 'success', 
                    'code'      => 200, 
                    'message'   => 'success delete plan library'
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