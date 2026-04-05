<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Login\ChangePassword;
use App\Http\Requests\Api\Login\ResetPasswordRequest;
use App\Http\Requests\Api\Register\ConfirmationOtpRequest;
use App\Http\Requests\Api\Register\SendCodeRequest;
use App\Http\Resources\RegisterResource;
use App\Services\Api\PasswordService;
use App\Traits\HttpResponse;

class PasswordController extends Controller
{
    use HttpResponse;
    public function __construct(public PasswordService $passwordService)
    {}

    public function forgetPassword(SendCodeRequest $sendCodeRequest)
    {
        $user     = $this->passwordService->forgetPassword($sendCodeRequest->validated());
        $response = ['user' => RegisterResource::make($user), 'otp' => $user->code];
        return $this->okResponse($response, __('Code sent successfully', [], Request()->header('Accept-language')));

    }
    public function confirmationOtp(ConfirmationOtpRequest $confirmationOtpRequest)
    {
        $user     = $this->passwordService->confirmationOtp($confirmationOtpRequest->validated());
        $response = ['otp' => $user->code];
        return $this->okResponse($response, __('Code successfully', [], Request()->header('Accept-language')));

    }

    public function resetPassword(ResetPasswordRequest $resetPasswordRequest)
    {
        $user = $this->passwordService->resetPassword($resetPasswordRequest->validated());
        return $this->okResponse(RegisterResource::make($user), __('The password has been reset successfully', [], Request()->header('Accept-language')));
    }

    public function changePassword(ChangePassword $changePassword)
    {
        $user = $this->passwordService->changePassword($changePassword->validated());
        return $this->okResponse(RegisterResource::make($user), __('The password has been changed successfully', [], Request()->header('Accept-language')));

    }

}
