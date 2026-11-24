@extends('front.policies._layout')
@section('title', __('نقاط المحل'))
@section('content')
    <div style="text-align:center">
        @if($ok)
            <div style="font-size:56px">🎉</div>
            <h1>{{ __('انضافت :points نقطة', ['points' => $points]) }}</h1>
            <p>{{ __('على شرائك من :shop. شكراً إنك اخترت محل على أوزمان.', ['shop' => $shop->name]) }}</p>
            <p><a href="{{ route('community.account') }}">{{ __('شوف نقاطي ←') }}</a></p>
        @else
            <div style="font-size:56px">⚠️</div>
            <h1>{{ __('ما انضافت النقاط') }}</h1>
            <p>{{ $message }}</p>
        @endif
    </div>
@endsection
