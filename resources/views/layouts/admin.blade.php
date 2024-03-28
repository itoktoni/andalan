<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <title>{{ env('APP_NAME') }} - {{ env('APP_DESCRIPTION') }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @include('partials/head-css')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @yield('head')

    @yield('css')
    @livewireStyles

    <style>
    .max-content
    {
        width: max-content;
        overflow: scroll;
    }
    .column-action,
    .table-action {
        width: 120px;
    }

    .column-active {
        width: 80px;
    }

    .column-status {
        width: 50px;
        text-align: center;
    }

    .column-sort {
        width: 50px;
    }

    .column-checkbox {
        width: 30px;
    }
    .page-action {
        bottom: 0;
        position: fixed;
        right: 0;
        color: #74788d;
        left: 250px;
        z-index:3;
        height: 60px;
        background-color: #fff !important;
        border-top: 1px solid #e9e9ef;
    }

    .page-action .action-container{
        margin-top:10px;
        margin-right:10px;
        text-align:right;
    }

    .form-group{
        margin-bottom: 10px;
    }

    @media (max-width: 800px) {
        .page-action {
            left: 0px;
        }

        .st-key{
            width: 40%;
        }

        .text-center.column-action{
            text-align: left !important;
        }
    }
    </style>

</head>

<body>

    <!-- Begin page -->
    <div id="layout-wrapper">

        @include('partials/menu')

        <!-- ============================================================== -->
        <!-- Start right Content here -->
        <!-- ============================================================== -->
        <div class="main-content">

            <div class="page-content">

                @yield('container')
                @include('layouts.alert')

            </div>
            <!-- End Page-content -->

            @include('partials/footer')
        </div>
        <!-- end main content-->

    </div>
    <!-- END layout-wrapper -->

    @include('partials/right-sidebar')

    @include('partials/vendor-scripts')

    <!-- App js -->
    <script src="{{ url('assets/js/app.js') }}"></script>

    @stack('footer')
    @livewireScripts

</body>

</html>
