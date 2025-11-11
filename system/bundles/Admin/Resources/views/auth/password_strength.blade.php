@extends('auth')

@php
    $version = \System::d('version');
@endphp

@section('js')
    <script src="/admin/assets/js/zxcvbn.js?v={{ $version }}"></script>
@endsection