@extends('layouts.app')

{{--
  Lightweight lecturer layout that reuses the main app layout.
  Child views should use @section('content') as usual.
--}}
@extends('layouts.app')

@section('content')
    @yield('content')
@endsection
