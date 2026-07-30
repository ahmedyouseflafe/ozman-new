<?php

namespace Tests\Unit;

use App\Rules\ValidPhoneNumber;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class ValidPhoneNumberTest extends TestCase
{
    public function test_it_accepts_supported_local_and_international_mobile_numbers(): void
    {
        foreach (['0591234567', '0561234567', '0541234567', '+970591234567', '00972541234567'] as $phone) {
            $validator = Validator::make(['phone' => $phone], [
                'phone' => ['required', new ValidPhoneNumber()],
            ]);

            $this->assertFalse($validator->fails(), "Expected {$phone} to be accepted.");
        }
    }

    public function test_it_rejects_random_or_malformed_phone_values(): void
    {
        foreach (['123456', 'abcdefghij', '050123', '0571234567', '0000000000', '05912345678'] as $phone) {
            $validator = Validator::make(['phone' => $phone], [
                'phone' => ['required', new ValidPhoneNumber()],
            ]);

            $this->assertTrue($validator->fails(), "Expected {$phone} to be rejected.");
        }
    }
}
