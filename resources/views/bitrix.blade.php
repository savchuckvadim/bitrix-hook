<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'April App') }}</title>
    <meta content="April Apps Manager Admin & Dashboard" name="description" />
    <meta content="Savchuk" name="author" />
    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <!-- App favicon -->
    <link rel="shortcut icon" href="{{ URL::asset('build/images/favicon.ico') }}">
    <script>
         localStorage.setItem("initialBxRequest", JSON.stringify({!! $initialData !!}));
    </script>
    <!-- Scripts -->
    @viteReactRefresh
    @vite(['resources/scss/theme.scss', 'resources/js/app.js'])

</head>

<body>
    <div id="react-app"></div>
</body>

</html>
