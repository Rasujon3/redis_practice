<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\WithdrawalRequest;
use App\Models\Cashout;
use App\Models\Paymentmethod;
use App\Http\Requests\CashOutRequest;
use App\Models\Cashin;
use App\Http\Requests\CashInRequest;

class CashoutController extends Controller
{
    public function cashout(Request $request)
    {
    	if($request->has('user_id'))
    	{
    		$user = User::findorfail($request->user_id);
    		$payment_methods = Paymentmethod::where('user_id',$user->id)->get();
    		return view('user.cashout', compact('user','payment_methods'));
    	}else{
    		return "Invalid Request";
    	}
    }

    public function saveCashOut(CashOutRequest $request)
    {
    	try
    	{
            if(user()->main_balance >= $request->amount)
            {
            	$cashOut = new Cashout;
	    		$cashOut->user_id = user()->id;
	    		$cashOut->uuid = time().user()->id;
	    		$cashOut->paymentmethod_id = $request->paymentmethod_id;
	    		$cashOut->amount = $request->amount;
	    		$cashOut->date = date('Y-m-d');
	    		$cashOut->time = date('h:i:s a');
                $cashOut->status = 'Pending';
	    		$cashOut->save();
	    		$notification = array(
	                'message' => 'Successfully your cashout request has been sent to admin',
	                'alert-type' => 'success'
	            );

	             return redirect()->back()->with($notification);
            }

            $notification = array(
	                'message' => 'Invalid Withdraw amount',
	                'alert-type' => 'error'
	        );

	        return redirect()->back()->with($notification);

    	}catch(Exception $e){
    		return response()->json(['status'=>false, 'code'=>$e->getCode(), 'message'=>$e->getMessage()],500);
    	}
    }


    public function saveCashIn(CashInRequest $request)
    {
        try
        {
            $cash_in = new Cashin();
            $cash_in->uuid = time().user()->id;
            $cash_in->user_id = user()->id;
            $cash_in->package_id = $request->selected_package_id; 
            $cash_in->bouns_amount = $request->has('selected_package_id')?package($request->selected_package_id)->bonus_amount:null;
            $cash_in->amount = $request->amount;
            $cash_in->date = date('Y-m-d');
            $cash_in->time = date('h:i:s a');
            $cash_in->status = 'Pending';
            $cash_in->save();
            $notification = array(
                    'message' => 'Successfully your cash-in request has been sent to admin',
                    'alert-type' => 'success'
            );

            return redirect()->back()->with($notification);
        }catch(Exception $e){
            return response()->json(['status'=>false, 'code'=>$e->getCode(), 'message'=>$e->getMessage()],500);
        }
    }
}
