{{-- A bigger face and a line saying what the assistant is doing; shown only while she listens, thinks or talks. --}}
<div class="oz-av-stage" data-state="idle" aria-live="polite">
    @include('front.partials.assistant_avatar', ['size' => 76])
    <span class="s-listening">{{ __('عم بسمعك…') }}</span><span class="s-thinking">{{ __('عم بفكّر…') }}</span><span class="s-speaking">{{ __('عم بحكيلك…') }}</span>
</div>
