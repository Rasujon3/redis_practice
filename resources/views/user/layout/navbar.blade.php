<!-- header section start -->
<header class="shadow-sm py-3 px-3 mx-auto">
    <div class="d-flex justify-content-between align-items-center">
{{--        <div class="">--}}
            @if(!empty(setting()->company_logo))
                <img src="{{ asset(setting()->company_logo) }}" alt="Logo" style="max-height: 50px !important;">
            @endif
            <h5 class="fs-5">{{ setting()->company_name ?? '' }}</h5>
{{--        </div>--}}
{{--        <div>--}}
            <a href="/user/profile" class="bg-transparent border-0">
                <iconify-icon
                    icon="heroicons:bars-3-bottom-right-20-solid"
                    width="1.5em"
                    height="1.5em"
                ></iconify-icon>
            </a>
{{--        </div>--}}

    </div>
</header>
<!-- header section end-->
