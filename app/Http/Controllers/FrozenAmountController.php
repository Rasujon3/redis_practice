<?php

namespace App\Http\Controllers;

use App\Http\Requests\FrozenAmountRequest;
use App\Http\Requests\PackageRequest;
use App\Models\FrozenAmount;
use App\Models\Product;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use DataTables;
use DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class FrozenAmountController extends Controller
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

               $packages = FrozenAmount::with('user')->select('*')->latest();

                    return Datatables::of($packages)
                        ->addIndexColumn()

                        ->addColumn('name', function($row){
                            return $row->user->name;
                        })

                        ->addColumn('amount', function($row){
                            return $row->amount;
                        })

                        ->addColumn('action', function($row){

                            $btn = "";
                            $btn .= '&nbsp;';

                            $btn .= ' <a href="'.route('frozen-amounts.show',$row->id).'" class="btn btn-primary btn-sm action-button edit-frozenAmount" data-id="'.$row->id.'"><i class="fa fa-edit"></i></a>';

                            $btn .= '&nbsp;';


                            $btn .= ' <a href="#" class="btn btn-danger btn-sm delete-frozenAmount action-button" data-id="'.$row->id.'"><i class="fa fa-trash"></i></a>';

                            return $btn;
                        })
                        ->rawColumns(['name', 'amount', 'action'])
                        ->make(true);
            }

            return view('admin.frozenAmounts.index');
        }catch(Exception $e){
            return response()->json(['status'=>false, 'code'=>$e->getCode(), 'message'=>$e->getMessage()],500);
        }
    }
    public function create()
    {
        $users = User::where('role', 'user')->get();
        return view('admin.frozenAmounts.create', compact('users'));
    }
    public function store(FrozenAmountRequest $request)
    {
        DB::beginTransaction();
        try
        {   

            $user = User::findorfail($request->user_id);

            $frozenAmount = new FrozenAmount();
            $frozenAmount->user_id = $request->user_id;
            $frozenAmount->amount = $request->amount;
            $frozenAmount->save();

            $user->main_balance = $user->main_balance == NULL?$frozenAmount->amount:$user->main_balance+$frozenAmount->amount;
            $user->update();

            $notification=array(
                'message' => 'Successfully a frozen Amount has been added',
                'alert-type' => 'success',
            );
            DB::commit();

            return redirect()->route('frozen-amounts.index')->with($notification);

        } catch(Exception $e) {
            DB::rollback();
            // Log the error
            Log::error('Error in storing frozen Amount: ', [
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
    public function show(FrozenAmount $frozenAmount)
    {
        $users = User::where('role', 'user')->get();
        return view('admin.frozenAmounts.edit', compact('frozenAmount', 'users'));
    }
    public function edit(FrozenAmount $frozenAmount)
    {
        //
    }
    public function update(FrozenAmountRequest $request, FrozenAmount $frozenAmount)
    {   
        DB::beginTransaction();
        try
        {   
            $user = User::findorfail($request->user_id);
            $frozenAmount->user_id = $request->user_id;
            $frozenAmount->amount = $request->amount;
            $frozenAmount->save();

            $user->main_balance = $user->main_balance == NULL?$frozenAmount->amount:$user->main_balance+$frozenAmount->amount;
            $user->update();

            $notification=array(
                'message'=>'Successfully the frozen Amount has been updated',
                'alert-type'=>'success',
            );

            DB::commit();

            return redirect()->route('frozen-amounts.index')->with($notification);

        }catch(Exception $e) {
            // Log the error
            Log::error('Error in updating frozen Amount: ', [
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            $notification=array(
                'message' => 'Something went wrong!!!',
                'alert-type' => 'error'
            );
            DB::rollback();
            return redirect()->back()->with($notification);
        }
    }
    public function destroy(FrozenAmount $frozenAmount)
    {
        try
        {
            $frozenAmount->delete();
            return response()->json(['status'=>true, 'message'=>'Successfully the frozen Amount has been deleted']);
        } catch(Exception $e) {
            DB::rollback();
            // Log the error
            Log::error('Error in deleting frozen Amount: ', [
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
