<div class="story-avatar">
    @if(isset($stories) && $stories->isNotEmpty())
        <button type="button" class="avatar-ring large avatar-story" data-story="0" aria-label="مشاهدة الستوري">@include('promoters.avatar')</button>
    @else
        <div class="avatar-ring large">@include('promoters.avatar')</div>
    @endif
    @if($profile->canBeManagedBy(auth()->user()))
        @if($inlineComposer ?? false)
            <button type="button" class="story-add" data-compose="story" aria-label="إضافة ستوري" title="إضافة ستوري"><span aria-hidden="true">+</span></button>
        @else
            <a class="story-add" href="{{ route('promoter.manage', ['profile' => $profile, 'compose' => 'story']) }}#storyComposer" aria-label="إضافة ستوري" title="إضافة ستوري"><span aria-hidden="true">+</span></a>
        @endif
    @endif
</div>
