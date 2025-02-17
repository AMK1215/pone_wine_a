<?php

use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\Bank\BankController;
use App\Http\Controllers\Api\V1\BannerController;
use App\Http\Controllers\Api\V1\ContactController;
use App\Http\Controllers\Api\V1\DepositRequestController;
use App\Http\Controllers\Api\V1\GetAdminSiteLogoNameController;
use App\Http\Controllers\Api\V1\GetBalanceController;
use App\Http\Controllers\Api\V1\NewVersion\PlaceBetWebhookController;
use App\Http\Controllers\Api\V1\PoneWineBetController;
use App\Http\Controllers\Api\V1\PromotionController;
use App\Http\Controllers\Api\V1\Slot\GameController;
use App\Http\Controllers\Api\V1\Slot\GetDaySummaryController;
use App\Http\Controllers\Api\V1\Slot\GetGameListByProviderController;
use App\Http\Controllers\Api\V1\Slot\GetGameProviderController;
use App\Http\Controllers\Api\V1\Slot\LaunchGameController;
use App\Http\Controllers\Api\V1\TransactionController;
use App\Http\Controllers\Api\V1\WagerController;
use App\Http\Controllers\Api\V1\Webhook\AdjustmentController;
use App\Http\Controllers\Api\V1\Webhook\BetController;
use App\Http\Controllers\Api\V1\Webhook\BetNResulNewController;
use App\Http\Controllers\Api\V1\Webhook\BetNResultController;
use App\Http\Controllers\Api\V1\Webhook\BetResultController;
use App\Http\Controllers\Api\V1\Webhook\CancelBetController;
use App\Http\Controllers\Api\V1\Webhook\CancelBetNewVersionController;
use App\Http\Controllers\Api\V1\Webhook\CancelBetNResultController;
use App\Http\Controllers\Api\V1\Webhook\NewBetController;
use App\Http\Controllers\Api\V1\Webhook\NewBetNResultController;
use App\Http\Controllers\Api\V1\Webhook\NewBetResultController;
use App\Http\Controllers\Api\V1\Webhook\RewardController;
use App\Http\Controllers\Api\V1\WithDrawRequestController;
use App\Http\Controllers\Api\Webhook\TestingController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/player-change-password', [AuthController::class, 'playerChangePassword']);
Route::post('/logout', [AuthController::class, 'logout']);
Route::get('contact', [ContactController::class, 'get']);

// sameless route
Route::post('Seamless/Test', [TransactionController::class, 'SystemWalletTest']);
Route::post('GetBalance', [GetBalanceController::class, 'getBalance']);
//Route::post('BetNResult', [BetNResultController::class, 'handleBetNResult']);
Route::post('BetNResult', [BetNResulNewController::class, 'handleBetNResult']);

//Route::post('BetNResult', [NewBetNResultController::class, 'handleBetNResult']);

Route::post('CancelBetNResult', [CancelBetNResultController::class, 'handleCancelBetNResult']);
//Route::post('Bet', [BetController::class, 'handleBet']);
Route::post('Bet', [NewBetController::class, 'handleBet']);
Route::delete('TestBet', [PlaceBetWebhookController::class, 'BetTest']);
//Route::post('Result', [BetResultController::class, 'handleResult']);
Route::post('Result', [NewBetResultController::class, 'handleResult']);

//Route::post('CancelBet', [CancelBetController::class, 'handleCancelBet']);
Route::post('CancelBet', [CancelBetNewVersionController::class, 'handleCancelBet']);

Route::post('Adjustment', [AdjustmentController::class, 'handleAdjustment']);
Route::post('Reward', [RewardController::class, 'handleReward']);
Route::post('GetGameProvider', [GetGameProviderController::class, 'fetchGameProviders']);
Route::post('GetGameListByProvider', [GetGameListByProviderController::class, 'fetchGameListByProvider']);

// for slot
Route::post('/transaction-details/{tranId}', [TransactionController::class, 'getTransactionDetails']);

Route::group(['middleware' => ['auth:sanctum']], function () {
    Route::get('user', [AuthController::class, 'getUser']);
    Route::get('contact', [AuthController::class, 'getContact']);
    Route::get('agent', [AuthController::class, 'getAgent']);
    Route::post('logout', [AuthController::class, 'logout']);
    Route::post('change-password', [AuthController::class, 'changePassword']);
    Route::post('profile', [AuthController::class, 'profile']);
    Route::get('agentPaymentType', [BankController::class, 'all']);
    Route::post('deposit', [DepositRequestController::class, 'deposit']);
    Route::get('depositlog', [DepositRequestController::class, 'log']);
    Route::get('paymentType', [BankController::class, 'paymentType']);
    Route::post('withdraw', [WithDrawRequestController::class, 'withdraw']);
    Route::get('withdrawlog', [WithDrawRequestController::class, 'log']);
    Route::get('sitelogo-name', [GetAdminSiteLogoNameController::class, 'GetSiteLogoAndSiteName']);
    Route::get('banner', [BannerController::class, 'index']);
    Route::get('videoads', [BannerController::class, 'ApiVideoads']);
    Route::get('toptenwithdraw', [BannerController::class, 'TopTen']);
    Route::post('bet', [PoneWineBetController::class, 'index']);

    Route::get('promotion', [PromotionController::class, 'index']);
    Route::get('bannerText', [BannerController::class, 'bannerText']);
    Route::get('winnerText', [BannerController::class, 'winnerText']);
    Route::get('banner_Text', [BannerController::class, 'bannerTest']);
    Route::get('popup-ads-banner', [BannerController::class, 'AdsBannerIndex']);
    Route::get('ads-banner', [BannerController::class, 'AdsBannerTest']);
});
