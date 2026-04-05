<?php

use App\Http\Controllers\Api\DeleteAccountController;
use App\Http\Controllers\Api\LoginController;
use App\Http\Controllers\Api\LogoutController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\PasswordController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\PlanController;
use App\Http\Controllers\Api\SubscriptionController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\RegisterController;
use Illuminate\Support\Facades\Route;

Route::group(['middleware' => ['lang']], function () {
    // register
    Route::post("/register", [RegisterController::class, 'register']);

    // verify
    Route::post('/verify', [RegisterController::class, 'verify']);
    Route::post('/otp', [RegisterController::class, 'otp']);
    //login
    Route::post("/login", [LoginController::class, 'login']);
    //forget-password
    Route::post('/forget-password', [PasswordController::class, 'forgetPassword']);
    //confirmationOtp
    Route::post('/confirmation-otp', [PasswordController::class, 'confirmationOtp']);
    //reset-password
    Route::post('/reset-password', [PasswordController::class, 'resetPassword']);

    //plans
    Route::get('/plans', [PlanController::class, 'index']);
    Route::get('/plans/{plan}', [PlanController::class, 'show']);

    Route::middleware('auth:sanctum')->group(function () {
        //profile
        Route::get('/profile', [ProfileController::class, 'profile']);
        Route::post('/profile', [ProfileController::class, 'updateProfile']);
        Route::post('/change-password', [PasswordController::class, 'changePassword']);
        Route::post('/logout', [LogoutController::class, 'logout']);
        Route::get('/notifications', [NotificationController::class, 'index']);
        //plans
        Route::post('/plans', [PlanController::class, 'store']);
        Route::put('/plans/{plan}', [PlanController::class, 'update']);
        Route::delete('/plans/{plan}', [PlanController::class, 'destroy']);
        //delete account
        Route::delete('/delete_account', [DeleteAccountController::class, 'deleteAccount']);

        // Subscriptions
        Route::get('/subscriptions', [SubscriptionController::class, 'index']);
        Route::post('/subscriptions', [SubscriptionController::class, 'store']);
        Route::get('/subscriptions/{subscription}', [SubscriptionController::class, 'show']);
        Route::post('/subscriptions/{subscription}/cancel', [SubscriptionController::class, 'cancel']);

        // Payments
        Route::get('/subscriptions/{subscription}/payments', [PaymentController::class, 'index']);
        Route::post('/subscriptions/{subscription}/payments', [PaymentController::class, 'store']);

    });
});
