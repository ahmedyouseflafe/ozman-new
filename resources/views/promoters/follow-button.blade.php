@if(auth()->id() !== $profile->user_id)
<div class="follow-control">
    @auth
    <form id="promoterFollowForm" method="post" action="{{ route('promoters.follow.store', $profile) }}" data-login="{{ route('promoters.follow.account', $profile) }}">
        @csrf @method($following ? 'DELETE' : 'PUT')
        <button class="button {{ $following ? 'outline' : '' }}" type="submit" aria-pressed="{{ $following ? 'true' : 'false' }}">{{ $following ? 'إلغاء المتابعة' : 'متابعة +' }}</button>
    </form>
    @else
    <a class="button" href="{{ route('promoters.follow.account', $profile) }}">متابعة +</a>
    @endauth
    <p id="promoterFollowStatus" role="status" aria-live="polite"></p>
</div>
@endif
