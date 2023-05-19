<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TaskClient;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function listReportTask(Request $request)
    {
        try{

            $search = $request->search;
            $filter = $request->filter;
            $paginate = $request->paginate ?? 6;
            $taskClients = TaskClient::when($search, function ($query) use ($search) {
                    return $query->where('users.name', 'LIKE', '%' . $search . '%');
                })
                ->join('users', 'users.id', '=', 'task_clients.client_user_id')
                ->when($filter, function ($query) use ($filter) {
                    return $query->where('task_clients.status', $filter);
                })
                ->select('users.id as id', 'users.full_name', 'task_clients.status', 'received_at', 'submit_at')
                ->with('memberAssign')
                ->orderBy('id', 'ASC')
                ->paginate($paginate);
            return response()->json([
                'meta'  => [
                    'status'    => 'success',
                    'code'      => 200,
                    'message'   => 'Successfully get data design library'
                ],
                'data'  => $taskClients
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
