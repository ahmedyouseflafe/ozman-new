{{-- Decorative: the user's name is always shown next to it. --}}
@php($avatarUrl = $user->avatarUrl())
<span class="customer-avatar {{ $class ?? '' }}" aria-hidden="true" @unless($avatarUrl) style="background: {{ $user->avatarColor() }}" @endunless>@if($avatarUrl)<img src="{{ $avatarUrl }}" alt="" loading="lazy" decoding="async">@else{{ $user->avatarInitial() }}@endif</span>
