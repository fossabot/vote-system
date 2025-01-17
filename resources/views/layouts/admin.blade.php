<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8"/>
    <title>{{ config('app.title') }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="{{ mix('css/app.css') }}"/>
    <script defer src="{{ mix('js/app.js') }}" nonce="{{ Bepsvpt\SecureHeaders\SecureHeaders::nonce('script') }}"></script>
    @include('components.custom-styling')
</head>
<body>
<div id="app" class="container mx-auto flex flex-col min-h-screen">
    @include('components.header-admin')
    @section('content')@show
    <div class="flex-1"></div>
    @include('components.footer')
</div>
</body>
</html>
