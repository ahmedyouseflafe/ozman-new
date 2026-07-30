<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidPhoneNumber implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $digits = preg_replace('/\D+/', '', (string) $value);
        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }

        $isLocalMobile = preg_match('/^05[02345689]\d{7}$/', $digits) === 1;
        $isPalestinianInternational = preg_match('/^9705[69]\d{7}$/', $digits) === 1;
        $isIsraeliInternational = preg_match('/^9725[023458]\d{7}$/', $digits) === 1;

        if (! ($isLocalMobile || $isPalestinianInternational || $isIsraeliInternational)) {
            $fail('رقم الهاتف غير صحيح. أدخل رقم جوال مثل 0591234567 أو بصيغة دولية صحيحة.');
        }
    }
}
