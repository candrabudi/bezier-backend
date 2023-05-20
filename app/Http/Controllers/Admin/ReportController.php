<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TaskClient;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
class ReportController extends Controller
{
    public function listReportTask(Request $request)
    {
        try{
            $search = $request->search;
            $filter = $request->filter;
            $page = $request->page ? $request->page : 1;
            $limits = $request->total_per_page ? $request->total_per_page : 6;
            $offset = ($page - 1) * $limits;
            $taskClients = TaskClient::when($search, function ($query) use ($search) {
                    return $query->where('users.name', 'LIKE', '%' . $search . '%');
                })
                ->join('users', 'users.id', '=', 'task_clients.client_user_id')
                ->when($filter, function ($query) use ($filter) {
                    return $query->where('task_clients.status', $filter);
                })
                ->select('users.id as id', 'users.full_name', 'task_clients.status', 'received_at', 'submit_at')
                ->with('memberAssign');

            $tasksCount = $taskClients->count();
            $taskData = $taskClients->orderBy('id', 'ASC')
                                ->limit($limits)
                                ->offset($offset)
                                ->get();

            $datas = [];
            foreach($taskData as $task){
                $member_assigns = [];
                foreach($task->memberAssign->toArray() as $member_assign){
                    $data_member = array(
                        'full_name' => $member_assign['full_name'],
                        'photo_profile_url' => !empty($member_assign['photo_profile_url']) ? url(\Storage::url($member_assign['photo_profile_url'])) : null
                    );

                    array_push($member_assigns, $data_member);
                }
                $data = array(
                    'id' => $task->id,
                    'full_name' => $task->full_name, 
                    'status' => $task->status,
                    'received_at' => $task->received_at,
                    'submit_at' => $task->submit_at,
                    'member_assign' => $member_assigns
                );

                array_push($datas, $data);
            }
            $allTasks = new LengthAwarePaginator($datas, $tasksCount, $limits);
            return response()->json([
                'meta'  => [
                    'status'    => 'success',
                    'code'      => 200,
                    'message'   => 'Successfully get data design library'
                ],
                'data'  => $allTasks
            ], 200);

        }catch(\Exception $e){
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
