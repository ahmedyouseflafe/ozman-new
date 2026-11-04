<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/** Instagram-style @usernames: lowercase letters, numbers, dots and underscores. */
class CommunityUsername
{
    public const PATTERN = '/^(?!.*\.\.)(?!\.)(?!.*\.$)[a-z0-9._]{3,30}$/';

    private const RESERVED = ['admin', 'administrator', 'ozman', 'support', 'help', 'root', 'system', 'promoter', 'promoters',
        'community', 'login', 'register', 'account', 'dashboard', 'api', 'www', 'mail', 'noreply', 'info', 'shop', 'shops'];

    public static function normalize(?string $value): string
    {
        return Str::lower(ltrim(trim((string) $value), '@'));
    }

    public static function rules(bool $required = true, ?int $ignoreUserId = null): array
    {
        return [$required ? 'required' : 'nullable', 'string', 'regex:'.self::PATTERN,
            \Illuminate\Validation\Rule::unique('users', 'username')->ignore($ignoreUserId),
            function (string $attribute, $value, $fail) {
                if (in_array($value, self::RESERVED, true)) {
                    $fail(__('اسم المستخدم هذا محجوز. اختار اسم ثاني.'));
                }
            }];
    }

    public static function messages(): array
    {
        return [
            'username.required' => __('اختار اسم مستخدم.'),
            'username.regex' => __('اسم المستخدم من 3 لـ 30 حرف: أحرف إنجليزية صغيرة وأرقام ونقطة (.) وشرطة سفلية (_).'),
            'username.unique' => __('اسم المستخدم مأخوذ. جرّب اسم ثاني.'),
        ];
    }

    /** Page link for a promoter's username (dots and underscores become dashes). */
    public static function slugFor(string $username): string
    {
        return trim(preg_replace('/-+/', '-', str_replace(['.', '_'], '-', $username)), '-');
    }

    public static function slugTaken(string $slug): bool
    {
        return DB::table('promoter_profiles')->where('slug', $slug)->exists()
            || DB::table('promoter_profile_slugs')->where('slug', $slug)->exists();
    }

    /** A free username made from the email, for accounts created without one (e.g. the follow dialog). */
    public static function generate(string $email, ?string $name = null): string
    {
        $base = Str::lower(preg_replace('/[^a-z0-9._]/i', '', Str::before($email, '@')));
        $base = trim(preg_replace('/\.{2,}/', '.', $base), '.');
        if (strlen($base) < 3) {
            $base = 'user'.$base;
        }
        $base = substr($base, 0, 24);
        $candidate = in_array($base, self::RESERVED, true) ? $base.'1' : $base;
        while (User::query()->where('username', $candidate)->exists()) {
            $candidate = $base.random_int(10, 99999);
        }

        return $candidate;
    }
}
