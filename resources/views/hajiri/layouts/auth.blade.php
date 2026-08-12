<!DOCTYPE html>
<html  lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Laravel') }}</title>
    <script src="{{ asset('erp/hajiri/js/app.js') }}" defer></script>
    <link rel="dns-prefetch" href="//fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css?family=Nunito" rel="stylesheet">
    <link href="{{ asset('erp/hajiri/css/app.css') }}" rel="stylesheet">
    <link href="{{url('/erp/hajiri/admin/css/style.css')}}" rel="stylesheet">
    <style>
    .footer {
            position: fixed;
            bottom: 0;
            width: 100%;
            padding-left:0px !important;
            padding:4px;
            font-size: 1rem;
        }
    </style>
</head>
<body>
    <div id="preloader">
        <div class="loader">
            <svg class="circular" viewBox="25 25 50 50">
                <circle class="path" cx="50" cy="50" r="20" fill="none" stroke-width="3" stroke-miterlimit="10" />
            </svg>
        </div>
    </div>

    <div class="login-form-bg h-100">
        <div class="container h-100 mt-5">
            <div class="row justify-content-center h-100">
                <div class="col-xl-12">
                    <!-- <div class="form-input-content">
                        <div class="card login-form mb-0">
                            <div class="card-body pt-5"> -->
                                @yield('content')
                            <!-- </div>
                        </div>
                    </div> -->
                </div>
            </div>
        </div>
        <div class="footer text-center">
        <div  class="p-0 m-0 text-center copyright">
            <p>Copyright &copy; {{ $siteSettings->localized('site_name', config('app.name')) }}</p>
            </div>
        </div>
    </div>

    <script src="{{url('/erp/hajiri/admin/plugins/common/common.min.js')}}"></script>
    <script src="{{url('/erp/hajiri/admin/js/custom.min.js')}}"></script>
    <script src="{{url('/erp/hajiri/admin/js/settings.js')}}"></script>
    <script src="{{url('/erp/hajiri/admin/js/gleek.js')}}"></script>
    <script src="{{url('/erp/hajiri/admin/js/styleSwitcher.js')}}"></script>
</body>
</html>
