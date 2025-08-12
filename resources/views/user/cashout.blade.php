@extends('user.layout.master')
@section('content')
    <main class="main-body overflow-hidden bg-secondary-subtle">
      <!-- header section start -->
      <header class="shadow-sm py-3 px-3 mx-auto bg-white">
        <div class="d-flex align-items-center justify-content-between">
          <div class="d-flex gap-3 align-items-center">
            <button class="bg-transparent border-0">
              <iconify-icon
                icon="qlementine-icons:user-16"
                width="3rem"
                height="3rem"
              ></iconify-icon>
            </button>
            <div>
              <p>UID: {{ Auth::user()->uid ?? '' }}</p>
              <h5 class="fs-5">{{ Auth::user()->username ?? '' }}</h5>
            </div>
          </div>
          <div class="mt-3">
              <a href="{{ url('/logout') }}" class="underline-none text-decoration-none">
                  <button
                      class="btn btn-outline-dark d-flex align-content-center gap-2 my-2"
                  >
                      <iconify-icon
                          icon="mage:logout"
                          width="1.2em"
                          height="1.2em"
                      ></iconify-icon>
                      <span> Logout</span>
                  </button>
              </a>
          </div>
        </div>
      </header>
      <!-- header section end-->

      <!-- back button start -->
      <div class="px-3 bg-white py-1 m-2 rounded-2">
          <a href="{{ route('user-index') }}" class="underline-none text-decoration-none">
              <button
                  class="btn btn-outline-dark d-flex align-content-center gap-2 my-2"
              >
                  <iconify-icon
                      icon="ep:back"
                      width="1.2em"
                      height="1.2em"
                  ></iconify-icon>
                  <span> Back</span>
              </button>
          </a>
      </div>
      <!-- back button end -->

      <!-- hero section start -->
      <section class="hero">
        <div class="px-3 bg-white py-3 m-2 rounded-2">
          <h3 class="fw-semibold fs-5">Cashout</h3> 
          <div class="mt-3">
             <form action="{{url('save-cashout')}}" method="POST">
              @csrf
              <input type="hidden" name="user_id" value="{{$user->id}}"/>
              <input type="hidden" name="paymentmethod_id" value="{{$user->paymentmethod->id ?? ''}}"/>
               <div class="form-group">
               	 <label for="paymentmethod" class="my-2" style="font-weight: bold;">My Bank</label>
               	 <input type="text" class="form-control" name="paymentmethod" id="paymentmethod" value="{{$user->paymentmethod->bank_name ?? ''}}" readonly="" />
               </div>


               <div class="form-group">
                 <label for="account_holder" class="my-2" style="font-weight: bold;">Account Holder</label>
                 <input type="text" class="form-control my-2" name="account_holder" id="account_holder" value="{{$user->paymentmethod->account_holder ?? ''}}" readonly="" />
               </div>


               <div class="form-group">
                 <label for="account_number" class="my-2" style="font-weight: bold;">Account Number</label>
                 <input type="text" class="form-control my-2" name="account_number" id="account_number" value="{{$user->paymentmethod->account_number ?? ''}}" readonly="" />
               </div>


               <div class="form-group">
               	 <label for="amount" class="my-2" style="font-weight: bold;">Amount: </label>
               	 <input type="text" class="form-control my-2" id="amount" name="amount" placeholder="Amount" value="{{old('amount')}}">
               </div>

               <div class="form-group my-2">
               	 <button type="submit" class="btn btn-submit btn-success">Submit</button>
               </div>

             </form>
          </div>

      </section>
    </main>
@endsection

