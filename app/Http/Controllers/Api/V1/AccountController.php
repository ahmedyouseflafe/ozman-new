<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ApiToken;
use App\Services\{CommunityUsername, CustomerAvatar};
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

/** "My account" in the app: name, phone, username, photo and password, with the website's rules. */
class AccountController extends Controller
{
    public function update(Request $request): JsonResponse
    {
        $user = $request->user();
        $rules = ['name' => ['required', 'string', 'max:100'], 'phone' => ['nullable', 'string', 'max:30']];
        // Customers may pick a @username; promoters keep theirs (it is their page link), staff have none.
        if ($user->isCustomer()) {
            $request->merge(['username' => CommunityUsername::normalize($request->input('username', $user->username)) ?: null]);
            $rules['username'] = CommunityUsername::rules((bool) $user->username, $user->id);
        }
        $user->update($request->validate($rules, CommunityUsername::messages()));

        return response()->json(['user' => Presenter::user($user->fresh()), 'message' => 'تم حفظ بياناتك.']);
    }

    public function avatar(Request $request, CustomerAvatar $avatars): JsonResponse
    {
        abort_unless($request->user()->isCustomer(), 403, 'الصورة الشخصية متاحة لحسابات الزبائن.');
        $avatars->replace($request->user(), $request->validate(CustomerAvatar::rules(true), CustomerAvatar::messages())['avatar']);

        return response()->json(['user' => Presenter::user($request->user()->fresh()), 'message' => 'تم تحديث صورتك.']);
    }

    public function destroyAvatar(Request $request, CustomerAvatar $avatars): JsonResponse
    {
        abort_unless($request->user()->isCustomer(), 403);
        $avatars->remove($request->user());

        return response()->json(['user' => Presenter::user($request->user()->fresh()), 'message' => 'تم حذف صورتك.']);
    }

    /** Changes the password and signs every other device out; this phone stays signed in. */
    public function password(Request $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->validate([
            'current_password' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'confirmed', 'max:128', 'different:current_password', Password::defaults()],
        ], ['password.different' => 'اختار كلمة مرور جديدة مختلفة عن الحالية.', 'password.confirmed' => 'تأكيد كلمة المرور مش مطابق.']);
        if (! Hash::check($data['current_password'], $user->password)) {
            return response()->json(['message' => 'كلمة المرور الحالية غير صحيحة.', 'errors' => ['current_password' => ['كلمة المرور الحالية غير صحيحة.']]], 422);
        }
        $user->forceFill(['password' => $data['password'], 'remember_token' => Str::random(60)])->save();
        $current = $request->attributes->get('api_token');
        ApiToken::where('user_id', $user->id)->when($current, fn ($q) => $q->whereKeyNot($current->id))->delete();

        return response()->json(['message' => 'تم تغيير كلمة المرور.']);
    }
}
