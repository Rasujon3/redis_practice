<?php

namespace App\Http\Controllers;

use App\Http\Requests\TaskAssignRequest;
use App\Http\Requests\TrialTaskAssignRequest;
use App\Models\AssignedTrialTask;
use App\Models\AssignTask;
use App\Models\Task;
use App\Models\TrialTask;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use DataTables;
use DB;
use Illuminate\Support\Facades\Log;

class TrialTaskAssignController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    public function __construct()
    {
        $this->middleware('auth_check');
    }

    public function index(Request $request)
    {
        try
        {
            if($request->ajax()){

               $packages = AssignedTrialTask::with('user', 'trialTask')->select('*')->latest();

                    return Datatables::of($packages)
                        ->addIndexColumn()

                        ->addColumn('name', function($row){
                            return $row->user->name;
                        })

                        ->addColumn('task', function($row){
                            return "Trial Task";
                        })

                        ->addColumn('status', function($row){
                            return $row->status;
                        })

                        ->addColumn('action', function($row){

                            $btn = "";
                            $btn .= '&nbsp;';

                            #$btn .= ' <a href="'.route('trial-task-assign.show',$row->id).'" class="btn btn-primary btn-sm action-button edit-frozenAmount" data-id="'.$row->id.'"><i class="fa fa-edit"></i></a>';

                            $btn .= '&nbsp;';


                            $btn .= ' <a href="#" class="btn btn-danger btn-sm delete-AssignedTrialTask action-button" data-id="'.$row->id.'"><i class="fa fa-trash"></i></a>';

                            return $btn;
                        })
                        ->rawColumns(['name', 'task', 'status', 'action'])
                        ->make(true);
            }

            return view('admin.trialTaskAssign.index');
        }catch(Exception $e){
            return response()->json(['status'=>false, 'code'=>$e->getCode(), 'message'=>$e->getMessage()],500);
        }
    }
    public function create()
    {
        $users = User::where('role', 'user')->get();
        $tasks = TrialTask::latest()->get();
        return view('admin.trialTaskAssign.create', compact('users', 'tasks'));
    }
    public function store(TrialTaskAssignRequest $request)
    {
        $assignTaskCount = AssignTask::where('user_id', $request->user_id)->count();
        if($assignTaskCount > 0){
            $notification=array(
                'message' => 'Already assigned another task.',
                'alert-type' => 'error'
            );
            return redirect()->back()->with($notification);
        }

        DB::beginTransaction();
        try
        {
            $assignLevel = new AssignedTrialTask();
            $assignLevel->user_id = $request->user_id;
            $assignLevel->trial_task_id = $request->trial_task_id;
            $assignLevel->save();

            $trialTaskInfo = TrialTask::first();
            User::where('id', $assignLevel->user_id)->update(['balance' => $trialTaskInfo->trial_balance]);

            $notification=array(
                'message' => 'Successfully assign trial task.',
                'alert-type' => 'success',
            );
            DB::commit();

            return redirect()->route('trial-task-assign.index')->with($notification);

        } catch(Exception $e) {
            DB::rollback();
            // Log the error
            Log::error('Error in storing assign trial Task: ', [
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            $notification=array(
                'message' => 'Something went wrong!!!',
                'alert-type' => 'error'
            );
            return redirect()->back()->with($notification);
        }
    }
    public function show(AssignedTrialTask $assignedTrialTask)
    {
        $users = User::where('role', 'user')->get();
        $tasks = TrialTask::latest()->get();
        return view('admin.trialTaskAssign.edit', compact('tasks', 'users', 'assignedTrialTask'));
    }
    public function edit(AssignedTrialTask $assignLevel)
    {
        //
    }
    public function update(TrialTaskAssignRequest $request, AssignedTrialTask $assignedTrialTask)
    {
        try
        {
            $assignedTrialTask->user_id = $request->user_id;
            $assignedTrialTask->trial_task_id = $request->trial_task_id;
            $assignedTrialTask->save();

            $notification=array(
                'message'=>'Successfully the Assign Trial Task has been updated',
                'alert-type'=>'success',
            );

            return redirect()->route('trial-task-assign.index')->with($notification);

        } catch(Exception $e) {
            // Log the error
            Log::error('Error in updating Assign Trial Task: ', [
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            $notification=array(
                'message' => 'Something went wrong!!!',
                'alert-type' => 'error'
            );
            return redirect()->back()->with($notification);
        }
    }
    public function destroy(AssignedTrialTask $assignedTrialTask)
    {
        try
        {
            $assignedTrialTask->delete();
            return response()->json(['status'=>true, 'message'=>'Successfully the Assign Trial Task has been deleted']);
        } catch(Exception $e) {
            DB::rollback();
            // Log the error
            Log::error('Error in deleting Assign Task: ', [
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            $notification=array(
                'message' => 'Something went wrong!!!',
                'alert-type' => 'error'
            );
            return redirect()->back()->with($notification);
        }
    }
}
