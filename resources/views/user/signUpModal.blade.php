<!-- sign up modal start -->
<div
    class="modal fade"
    id="signup-modal"
    tabindex="-1"
    aria-labelledby="exampleModalLabel"
    aria-hidden="true"
>
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="exampleModalLabel">Sign up</h1>
                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal"
                    aria-label="Close"
                ></button>
            </div>
            <div class="modal-body">
                <div>
                    <form action="{{ route('user-sign-up') }}" method="post">
                        @csrf
                        <div class="mb-3">
                            <label for="mobileNumber" class="form-label">Username</label>
                            <input
                                type="text"
                                name="username"
                                class="form-control"
                                id="mobileNumber"
                                aria-describedby="emailHelp"
                                placeholder="Enter your mobile number"
                            />
                        </div>
                        <div class="mb-3">
                            <label for="mobileNumber" class="form-label">Mobile Number</label>
                            <input
                                type="text"
                                name="phone"
                                class="form-control"
                                id="mobileNumber"
                                aria-describedby="emailHelp"
                                placeholder="Enter your mobile number"
                            />
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input
                                type="password"
                                name="password"
                                class="form-control"
                                id="password"
                                placeholder="**********"
                            />
                        </div>
                        <div class="mb-3">
                            <label for="confirmPassword" class="form-label">Confirm Password</label>
                            <input
                                type="password"
                                name="confirm_password"
                                class="form-control"
                                id="confirmPassword"
                                placeholder="**********"
                            />
                        </div>
                        <div class="mb-3">
                            <label for="withdrawalPassword" class="form-label">Withdrawal Password</label>
                            <input
                                type="password"
                                name="withdraw_password"
                                class="form-control"
                                id="withdrawalPassword"
                                placeholder="**********"
                            />
                        </div>

                        <button
                            type="submit"
                            class="btn btn-dark py-2 w-100 d-flex gap-2 align-items-center justify-content-center"
                        >
                            <span>Sign up</span>
                            <iconify-icon
                                icon="maki:arrow"
                                width="1.2em"
                                height="1.2em"
                            ></iconify-icon>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- sign up modal end -->
