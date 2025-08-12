@extends('user.layout.master')
@section('content')
    <section>
        <div class="py-3 px-4">
          <div>
            {!! $loginPageContents->name ?? '' !!}
          </div>
            {!! $loginPageContents->title ?? '' !!}
            {!! $loginPageContents->description ?? '' !!}
        </div>
        <div class="bg-dark mt-auto px-5 py-5">
          <div>
              <form action="{{ route('user-login') }}" method="post">
              @csrf
              <div class="mb-3">
                <input
                  type="text"
                  name="username"
                  class="form-control"
                  id="exampleInputEmail1"
                  aria-describedby="emailHelp"
                  placeholder="Enter username"
                />
              </div>
              <div class="mb-3">
                <input
                  type="password"
                  name="password"
                  class="form-control"
                  id="exampleInputPassword1"
                  placeholder="Password"
                />
              </div>

              <div class="text-end">
                  <a href="javascript:void(0)" data-bs-toggle="modal" data-bs-target="#forgotPasswordModal" class="text-white">
                      Forgot password?
                  </a>
              </div>

                  <button type="submit" class="btn btn-primary">Log in</button>

              <div id="emailHelp" class="form-text text-white text-center">
                Don't have any account,
                <button
                  type="button"
                  data-bs-toggle="modal"
                  data-bs-target="#signup-modal"
                  class="btn btn-link text-primary fw-medium"
                >
                  Sign up
                </button>
              </div>
            </form>
          </div>
        </div>
      </section>
      <!-- login end -->
      @include('user.signUpModal')
      @include('user.forgetPasswordModal')
      <footer class="">
        <div class="text-center py-3">
          <small
            >&copy; {{ date('Y') }} Copyright Right Reserved by
            <a href="{{ route('user-index') }}" class="text-primary text-decoration-none">{{ setting()->company_name ?? '' }}</a></small
          >
        </div>
      </footer>
@endsection
