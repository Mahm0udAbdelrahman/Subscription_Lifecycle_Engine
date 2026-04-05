<?php
namespace App\Services\Api;

use Carbon\Carbon;
use App\Models\User;
use App\Helpers\OTPHelper;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Exceptions\NotFoundException;

class PasswordService
{



    public function __construct(public User $model){}

    public function forgetPassword($data)
    {
        $user = $this->model->where('phone', $data['phone'])->first();
        if ($user) {
            $otp = rand(1000, 9999);
            $user->update(['code' => $otp , 'expire_at' => Carbon::now()->addMinutes(1), 'email_verified_at' => null]);
             OTPHelper::sendOtp($data['phone'], $otp);
            return $user;
        }
        throw new NotFoundException(__('Email not registered', [], request()->header('Accept-language')), 400);
    }

    public function confirmationOtp($data)
    {
        $user = $this->model->where('phone', $data['phone'])->where('code', $data['otp'])
            ->where('expire_at', '>', now())
            ->first();
        if (! $user) {
            throw new NotFoundException(__('Otp not found', [], request()->header('Accept-language')), 400);

        }
        return $user;
    }

    public function resetPassword($data)
    {
        $user = $this->model->where('phone', $data['phone'])->first();
        if ($user->code == $data['otp']) {
            $user->update(['password' => Hash::make($data['password']), 'code' => null, 'expire_at' => null, 'email_verified_at' => Carbon::now()]);
            Auth::login($user);
            return $user;
        }
        throw new NotFoundException(__('These credentials do not match our records.', [], Request()->header('Accept-language')), 400);
    }

    public function changePassword($data)
    {
        $user = auth('sanctum')->user();
        $user->update(['password' => Hash::make($data['password'])]);
        return $user;
    }

}
