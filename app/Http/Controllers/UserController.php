<?php

namespace App\Http\Controllers;

use App\Models\AboutUs;
use App\Models\AssignedTrialTask;
use App\Models\AssignTask;
use App\Models\CashInImg;
use App\Models\ContactSectionContent;
use App\Models\CourierSectionContent;
use App\Models\CreditRule;
use App\Models\DeliverySectionContent;
use App\Models\Event;
use App\Models\GlobalInfo;
use App\Models\GrowthSectionContent;
use App\Models\HelpCenter;
use App\Models\HeroSectionContent;
use App\Models\Level;
use App\Models\LoginPageContent;
use App\Models\Order;
use App\Models\Package;
use App\Models\Product;
use App\Models\SetOffVideoSectionContent;
use App\Models\Setting;
use App\Models\ShippingSectionContent;
use App\Models\Slider;
use App\Models\TrialTask;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use App\Models\Cashin;
use App\Models\Cashout;

class UserController extends Controller
{
    public function index()
    {
        $heroSection = HeroSectionContent::first();
        $growthSection = GrowthSectionContent::first();
        $shippingSection = ShippingSectionContent::first();
        $courierSection = CourierSectionContent::first();
        $deliverySection = DeliverySectionContent::first();
        $contactSection = ContactSectionContent::first();
        $globalSection = GlobalInfo::first();

        return view('user.index', compact(
            'heroSection',
            'growthSection',
            'shippingSection',
            'courierSection',
            'deliverySection',
            'contactSection',
            'globalSection'
        ));
    }
    public function profile()
    {
        return view('user.profile');
    }
    public function setoff()
    {
        $user = User::with('frozenAmount', 'assignTrialTask')->where('id', Auth::user()->id)->first();
        $frozenAmount = $user?->frozenAmount?->amount ?? '0';
        $orderCompletedCount = Order::where('user_id', Auth::user()->id)->count();
        $commissionSum = Order::where('user_id', Auth::user()->id)
            ->join('products', 'orders.product_id', '=', 'products.id')
            ->sum(DB::raw('CAST(products.commission AS DECIMAL(10,2))'));

        $completedOrders = Order::where('user_id', Auth::user()->id)->with('product')->latest()->get();

        // Ordered product IDs
        $orderedProductIds = $completedOrders->pluck('product_id')->toArray();

        // Pending products (not ordered yet)
        $pendingProducts = Product::whereNotIn('id', $orderedProductIds)->get();

        // Task count
        $totalTaskCount = 0;
        $trialTaskInfo = TrialTask::first();
        $totalTaskCount += $trialTaskInfo?->num_of_task ? (int)$trialTaskInfo?->num_of_task : 0;

        $assignTasks = AssignTask::with('task')->where('user_id', Auth::user()->id)->get();

        if (count($assignTasks) > 0) {
            foreach ($assignTasks as $assignTask) {
                $num = (int) ($assignTask->task->num_of_task ?? 0);
                $totalTaskCount += $num;
            }
        }

        $video = SetOffVideoSectionContent::first();
        $sliders = Slider::get();

        return view('user.setoff', compact(
            'frozenAmount',
            'orderCompletedCount',
            'commissionSum',
            'completedOrders',
            'totalTaskCount',
            'pendingProducts',
            'video',
            'sliders'
        ));
    }
    public function event()
    {
        $events = Event::latest()->get();
        $packages = Package::latest()->get();
        return view('user.event', compact('events','packages'));
    }
    public function creditScore()
    {
        $rules = CreditRule::first();
        return view('user.credit-score', compact('rules'));
    }
    public function userLogin()
    {
        $loginPageContents = LoginPageContent::first();
        return view('user.login', compact('loginPageContents'));
    }
    public function loginUser(Request $request)
    {
        try {
            $data = $request->all();

            if (Auth::attempt(['username' => $data['username'], 'password' => $data['password']])) {
                $user = Auth::user();

                if ($user->role === 'user') {
                    $notification = array(
                        'message' => 'Successfully Logged In',
                        'alert-type' => 'success'
                    );
                    return redirect()->route('user-index')->with($notification);
                } else {
                    Auth::logout();
                    $notification = array(
                        'message' => 'You are not authorized to login here.',
                        'alert-type' => 'error'
                    );
                    return Redirect()->back()->with($notification);
                }

            } else {
                $notification = array(
                    'message' => 'Username or Password Invalid',
                    'alert-type' => 'error'
                );
                return Redirect()->back()->with($notification);
            }
        } catch (Exception $e) {
            Log::error('Error in Login: ', [
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            $notification = array(
                'message' => 'Something went wrong!!!',
                'alert-type' => 'error'
            );
            return Redirect()->back()->with($notification);
        }
    }
    public function signUp(Request $request)
    {
        try {
            // Validation
            $request->validate([
                # 'name' => 'required|string|max:255',
                'username' => 'required|string|max:255|unique:users,username',
                'phone' => 'nullable|string|max:20',
                'password' => 'required|string|min:6',
                # 'withdraw_acc_number' => 'required|string',
                'withdraw_password' => 'required|string|min:6',
            ]);

            // Create new user
            $user = new User();
            $user->uid = $this->generateUniqueUid();
            $user->name = $request->name;
            $user->username = $request->username;
            $user->phone = $request->phone;
            $user->role = 'user'; // default role user
            $user->password = $request->password; // auto hashed via mutator
            $user->withdraw_acc_number = $request->withdraw_acc_number;
            $user->withdraw_password = $request->withdraw_password;
            # $user->balance = setting()->trial_amount;
            $user->status = 'Inactive';
            $user->save();

            $notification = [
                'message' => 'Account created successfully. Please contact administrator for active your account.',
                'alert-type' => 'success'
            ];

            return redirect()->route('login-user')->with($notification);

        } catch (\Exception $e) {
            Log::error('SignUp Error: '.$e->getMessage());

            $notification = [
                'message' => 'Something went wrong!!!',
                'alert-type' => 'error'
            ];
            return redirect()->back()->with($notification);
        }
    }
    private function generateUniqueUid()
    {
        do {
            $lastUser = User::orderBy('id', 'desc')->first();

            if (!$lastUser || !$lastUser->uid) {
                $uid = 1000;
            } else {
                $uid = (int) $lastUser->uid + 1;
            }

        } while (User::where('uid', $uid)->exists());

        return (string) $uid;
    }
    public function productOrder()
    {
        try
        {
            $user = auth()->user();

            // check trial task assign
            $checkTrialTaskAssign = AssignedTrialTask::where('user_id', $user->id)->first();
            if (!$checkTrialTaskAssign) {
                $notification = [
                    'message' => 'You are not assigned on Trial Task. Please contact with support team.',
                    'alert-type' => 'error'
                ];

                return redirect()->back()->with($notification);
            }

            // Complete trial task & another task assigned or not
            $anotherPendingTaskAssigned = AssignTask::where('user_id', $user->id)->where('is_completed', false)->exists();
            if ($checkTrialTaskAssign && $checkTrialTaskAssign->status === 'completed' && !$anotherPendingTaskAssigned) {
                $notification = [
                    'message' => 'You are not assigned on another Task. Please contact with support team.',
                    'alert-type' => 'error'
                ];

                return redirect()->back()->with($notification);
            }

            // Total Task Count
            $totalTaskCount = 0;
            $trialTaskInfo = TrialTask::first();
            if (!$trialTaskInfo) {
                $notification = [
                    'message' => 'Trial Task not found. Please contact with support team.',
                    'alert-type' => 'error'
                ];

                return redirect()->back()->with($notification);
            }
            $totalTaskCount += (int)$trialTaskInfo->num_of_task;

            $assignTasks = AssignTask::with('task')->where('user_id', Auth::user()->id)->get();

            if (count($assignTasks) > 0) {
                foreach ($assignTasks as $assignTask) {
                    $num = (int) ($assignTask->task->num_of_task ?? 0);
                    $totalTaskCount += $num;
                }
            }

            // Completed Task Count
            $completedTaskCount = Order::where('user_id', Auth::user()->id)->count();
            if ($completedTaskCount === $totalTaskCount) {
                $notification = [
                    'message' => 'You are completed assigned Task. Please contact with support team for new task.',
                    'alert-type' => 'error'
                ];

                return redirect()->back()->with($notification);
            }

            $limit = $totalTaskCount - $completedTaskCount;

            $orderedProductIds = Order::where('user_id', $user->id)->pluck('product_id')->toArray();

            $products = Product::latest()
                ->whereNotIn('id', $orderedProductIds)
//                ->limit($limit)
                ->limit(1)
                ->get();

            User::where('id', Auth::user()->id)->update(['balance' => 0]);

            if ($checkTrialTaskAssign && $checkTrialTaskAssign->status !== 'completed') {
                $checkTrialTaskAssign->started_at = Carbon::now();
                $checkTrialTaskAssign->save();
            }

            return view('user.orderProduct', compact('products', 'orderedProductIds'));
        } catch(Exception $e) {
            DB::rollback();
            // Log the error
            Log::error('Error in showing productOrder: ', [
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
    public function cashIn()
    {
        $user = User::with('paymentmethod')->find(user()->id);
        $packages = Package::get();
        $img = CashInImg::first();
        return view('user.profile.cashIn', compact('user','packages', 'img'));
    }
    public function accountDetails()
    {
        $fiveDeposits = Cashin::where('user_id',user()->id)->take(5)->latest()->get();
        $fiveWithdraws = Cashout::where('user_id',user()->id)->take(5)->latest()->get();
        $withdraws = Cashout::where('user_id',user()->id)->latest()->get();
        $deposits = Cashin::where('user_id',user()->id)->latest()->get();
        $commissions = Cashin::where('user_id',user()->id)->where('bouns_amount','!=',NULL)->latest()->get();

        return view('user.profile.accountDetails', compact('fiveDeposits','fiveWithdraws','withdraws','deposits','commissions'));
    }
    public function level()
    {
//        $levels = Level::with('assignLevel')->get();
//        return view('user.profile.level', compact('levels'));
        return view('user.profile.level_new');
    }
    public function levelDetails()
    {
        return view('user.profile.levelDetails');
    }
    public function signInDetails()
    {
        return view('user.profile.signInDetails');
    }
    public function technicalSupport()
    {
        $settings = Setting::first();
        return view('user.profile.technicalSupport', compact('settings'));
    }
    public function helpCenter()
    {
        $helpCenter = HelpCenter::get();
        return view('user.profile.helpCenter', compact('helpCenter'));
    }
    public function problemHelp()
    {
        return view('user.profile.problemHelp');
    }
    public function aboutUs()
    {
        return view('user.profile.aboutUs');
    }
    public function settings()
    {
        return view('user.profile.settings');
    }
    public function userAgreement()
    {
        $aboutUs = AboutUs::first();
        return view('user.aboutUs.userAgreement', compact('aboutUs'));
    }
    public function userPrivacy()
    {
        $aboutUs = AboutUs::first();
        return view('user.aboutUs.userPrivacy', compact('aboutUs'));
    }
    public function modifyWithdrawPassword()
    {
        return view('user.password.withdrawPassword');
    }
    public function modifyLoginPassword()
    {
        return view('user.password.loginPassword');
    }
    public function updateWithdrawPassword(Request $request)
    {
        $request->validate([
            'current_withdraw_password' => 'required',
            'new_withdraw_password' => 'required|min:6|confirmed',
        ], [
            'current_withdraw_password.required' => 'Current withdraw Password is required.',
            'new_withdraw_password.required' => 'New withdraw Password is required.',
            'new_withdraw_password.min' => 'Current withdraw Password required minimum 6 characters.',
            'new_withdraw_password.confirmed' => 'New withdraw Password does not match.',
        ]);

        $user = Auth::user();

        if (!Hash::check($request->current_withdraw_password, $user->withdraw_password)) {
            return back()->withErrors(['current_withdraw_password' => 'Current withdraw Password is incorrect.']);
        }

        if (Hash::check($request->new_withdraw_password, $user->withdraw_password)) {
            return back()->withErrors(['new_withdraw_password' => 'The new withdrawal password is the same as the previous one. Enter a new password.']);
        }

        $user->withdraw_password = $request->new_withdraw_password;
        $user->save();

        $notification = [
            'message' => 'Withdrawal password has been changed successfully.',
            'alert-type' => 'success'
        ];

        return redirect()->route('user-profile')->with($notification);
    }
    public function updateLoginPassword(Request $request)
    {
        $request->validate([
            'current_login_password' => 'required',
            'new_login_password' => 'required|min:6|confirmed',
        ], [
            'current_login_password.required' => 'Current login Password is required.',
            'new_login_password.required' => 'New login Password is required.',
            'new_login_password.min' => 'Current login Password required minimum 6 characters.',
            'new_login_password.confirmed' => 'New login Password does not match.',
        ]);

        $user = Auth::user();

        if (!Hash::check($request->current_login_password, $user->password)) {
            return back()->withErrors(['current_login_password' => 'Current login Password is incorrect.']);
        }

        if (Hash::check($request->new_login_password, $user->password)) {
            return back()->withErrors(['new_login_password' => 'The new login password is the same as the previous one. Enter a new password.']);
        }

        $user->password = $request->new_login_password;
        $user->save();

        $notification = [
            'message' => 'Login password has been changed successfully.',
            'alert-type' => 'success'
        ];

        return redirect()->route('user-profile')->with($notification);
    }
}
