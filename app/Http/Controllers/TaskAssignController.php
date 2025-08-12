<?php

namespace App\Http\Controllers;

use App\Http\Requests\TaskAssignRequest;
use App\Models\AssignedTrialTask;
use App\Models\AssignTask;
use App\Models\Task;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use DataTables;
use DB;
use Illuminate\Support\Facades\Log;

class TaskAssignController extends Controller
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

               $packages = AssignTask::with('user', 'task')->select('*')->latest();

                    return Datatables::of($packages)
                        ->addIndexColumn()

                        ->addColumn('name', function($row){
                            return $row->user->name;
                        })

                        ->addColumn('task', function($row){
                            return $row->task->title;
                        })

                        ->addColumn('is_completed', function($row){
                            return $row->is_completed ? 'Completed' : 'Incomplete';
                        })

                        ->addColumn('action', function($row){

                            $btn = "";
                            $btn .= '&nbsp;';

                            $btn .= ' <a href="'.route('task-assign.show',$row->id).'" class="btn btn-primary btn-sm action-button edit-frozenAmount" data-id="'.$row->id.'"><i class="fa fa-edit"></i></a>';

                            $btn .= '&nbsp;';


                            $btn .= ' <a href="#" class="btn btn-danger btn-sm delete-AssignTask action-button" data-id="'.$row->id.'"><i class="fa fa-trash"></i></a>';

                            return $btn;
                        })
                        ->rawColumns(['name', 'task', 'is_completed', 'action'])
                        ->make(true);
            }

            return view('admin.taskAssign.index');
        }catch(Exception $e){
            return response()->json(['status'=>false, 'code'=>$e->getCode(), 'message'=>$e->getMessage()],500);
        }
    }
    public function create()
    {
        $users = User::where('role', 'user')->get();
        $tasks = Task::latest()->get();
        return view('admin.taskAssign.create', compact('users', 'tasks'));
    }
    public function store(TaskAssignRequest $request)
    {
        $trialTaskInfo = AssignedTrialTask::where('user_id', $request->user_id)->first();

        if(!$trialTaskInfo) {
            $notification=array(
                'message' => "First assign Trial task.",
                'alert-type' => 'error'
            );
            return redirect()->back()->with($notification);
        }

        if($trialTaskInfo && $trialTaskInfo->status !== "completed") {
            $notification=array(
                'message' => "Trial task not completed yet.",
                'alert-type' => 'error'
            );
            return redirect()->back()->with($notification);
        }

        $existingTask = AssignTask::where('user_id', $request->user_id)
            ->where('is_completed', false)
            ->first();

        if ($existingTask) {
            $notification=array(
                'message' => "This user already has an incomplete task assigned.",
                'alert-type' => 'error'
            );
            return redirect()->back()->with($notification);
        }

        $existingCompletedTask = AssignTask::where('user_id', $request->user_id)
            ->where('is_completed', true)
            ->first();

        if ($existingCompletedTask && $existingCompletedTask->task_id == $request->task_id) {
            $notification=array(
                'message' => "This user already has assigned this task.",
                'alert-type' => 'error'
            );
            return redirect()->back()->with($notification);
        }

        DB::beginTransaction();
        try
        {
            $assignLevel = new AssignTask();
            $assignLevel->user_id = $request->user_id;
            $assignLevel->task_id = $request->task_id;
            $assignLevel->save();

            $notification=array(
                'message' => 'Successfully assign task.',
                'alert-type' => 'success',
            );
            DB::commit();

            return redirect()->route('task-assign.index')->with($notification);

        } catch(Exception $e) {
            DB::rollback();
            // Log the error
            Log::error('Error in storing assign Task: ', [
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
    public function show(AssignTask $taskAssign)
    {
        $users = User::where('role', 'user')->get();
        $tasks = Task::latest()->get();
        return view('admin.taskAssign.edit', compact('tasks', 'users', 'taskAssign'));
    }
    public function edit(AssignTask $assignLevel)
    {
        //
    }
    public function update(TaskAssignRequest $request, AssignTask $taskAssign)
    {
        try
        {
            $taskAssign->user_id = $request->user_id;
            $taskAssign->task_id = $request->task_id;
            $taskAssign->save();

            $notification=array(
                'message'=>'Successfully the Assign Task has been updated',
                'alert-type'=>'success',
            );

            return redirect()->route('task-assign.index')->with($notification);

        } catch(Exception $e) {
            // Log the error
            Log::error('Error in updating Assign Task: ', [
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
    public function destroy(AssignTask $taskAssign)
    {
        try
        {
            $taskAssign->delete();
            return response()->json(['status'=>true, 'message'=>'Successfully the Assign Task has been deleted']);
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
