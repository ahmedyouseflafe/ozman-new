<div class="story-avatar">
    <div class="avatar-ring large">@include('promoters.avatar')</div>
    @if(auth()->user()?->is_active && (auth()->user()?->isSuperAdmin() || (auth()->user()?->isPromoter() && auth()->id() === $profile->user_id)))
        <a class="story-add" href="{{ route('promoter.manage', ['profile' => $profile, 'compose' => 'story']) }}#storyComposer" aria-label="إضافة ستوري" title="إضافة ستوري"><span aria-hidden="true">+</span></a>
    @endif
</div>
