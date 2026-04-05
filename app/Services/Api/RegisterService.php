<?php
namespace App\Services\Api;

use App\Exceptions\NotFoundException;
use App\Helpers\OTPHelper;
use App\Models\User;
use App\Traits\HasImage;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;

class RegisterService
{
    use HasImage;

    public function __construct(public User $model)
    {}

    public function register($data)
    {

        if (isset($data['image'])) {
            $data['image'] = $this->saveImage($data['image'], 'user');
        } else {
            $data['image'] = asset('default/default.png');
        }

        $data['password']  = Hash::make($data['password']);
        $data['code']      = rand(1000, 9999);
        $data['expire_at'] = Carbon::now()->addMinutes(1);
        OTPHelper::sendOtp($data['phone'], $data['code']);

        $user = $this->model->create($data);

        return $user;
    }

    public function verify($data)
    {
        $user = $this->model->where('phone', $data['phone'])->first();

        if (! $user) {
            throw new NotFoundException(__('Phone not registered', [], request()->header('Accept-language')), 400);

        }

        if ($user->email_verified_at) {
            throw new NotFoundException(__('The user account has already been verified', [], request()->header('Accept-language')), 400);
        }

        if ($user->code !== $data['otp']) {
            throw new NotFoundException(__('Wrong OTP code', [], request()->header('Accept-language')), 400);
        }

        if (Carbon::parse($user->expire_at)->lt(Carbon::now())) {
            throw new NotFoundException(__('The OTP code has expired', [], request()->header('Accept-language')), 400);
        }

        $token = $user->createToken("API TOKEN")->plainTextToken;
        $user->update([
            'email_verified_at' => Carbon::now(),
            'code'              => null,
            'expire_at'         => null,
        ]);

        Auth::login($user);

        return [$user, $token];
    }

    public function otp($data)
    {
        $user = $this->model->where('phone', $data['phone'])
            ->whereNotNull('code')
            ->whereNotNull('expire_at')
            ->first();

        if ($user) {
            if (now()->greaterThan($user->expire_at)) {
                $newCode       = rand(1000, 9999);
                $user->update([
                    'code'      => $newCode,
                    'expire_at' => now()->addMinutes(1),
                ]);

                OTPHelper::sendOtp($data['phone'], $newCode);
            }
            return $user;
        }
        throw new NotFoundException(__('The Phone is Verify', [], request()->header('Accept-language')), 400);


    }

}
