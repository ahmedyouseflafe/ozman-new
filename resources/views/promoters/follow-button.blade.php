@if(! $profile->isOwnedBy(auth()->user()) && ! $profile->canBeManagedBy(auth()->user()))
<div class="follow-control">
    {{-- Same button for guests and customers: guests follow with a device cookie, no account needed. --}}
    <form id="promoterFollowForm" method="post" action="{{ route('promoters.follow.store', $profile) }}"
        data-i18n="{{ json_encode([
            'followed' => __('تمت متابعة الحساب.'),
            'unfollowed' => __('تم إلغاء المتابعة.'),
            'expired' => __('انتهت الجلسة. حدّث الصفحة وحاول مجددًا.'),
            'tooMany' => __('انتظر قليلًا ثم حاول مجددًا.'),
            'failed' => __('تعذّر حفظ المتابعة. حاول مجددًا.'),
            'unconfirmed' => __('تعذّر تأكيد المتابعة. حدّث الصفحة.'),
            'timeout' => __('تأخر الاتصال. حاول مجددًا للتأكد من حفظ المتابعة.'),
            'guestPrompt' => __('تابعت :name ✓ — احفظ متابعاتك على كل أجهزتك', ['name' => $profile->display_name]),
            'createAccount' => __('إنشاء حساب'),
            'later' => __('لاحقاً'),
        ], JSON_UNESCAPED_UNICODE) }}"
        data-signup="{{ route('community.login', ['tab' => 'register', 'return' => parse_url($profile->publicUrl(), PHP_URL_PATH)]) }}#register">
        @csrf @method($following ? 'DELETE' : 'PUT')
        <button class="button follow-toggle {{ $following ? 'outline is-following' : '' }}" type="submit" aria-pressed="{{ $following ? 'true' : 'false' }}">
            <span class="follow-label-off">{{ __('متابعة +') }}</span>
            <span class="follow-label-on">{{ __('متابَع ✓') }}</span>
            <span class="follow-label-unfollow">{{ __('إلغاء المتابعة') }}</span>
        </button>
    </form>
    <p id="promoterFollowStatus" role="status" aria-live="polite"></p>
</div>
@endif
