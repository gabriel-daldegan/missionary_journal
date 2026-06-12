<?php

use App\Http\Controllers\Auth\OAuthController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\InvitationController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\MemoryMediaController;
use App\Http\Controllers\PaymentProviders\PaddleController;
use App\Http\Controllers\ProductCheckoutController;
use App\Http\Controllers\RoadmapController;
use App\Http\Controllers\SubscriptionCheckoutController;
use App\Http\Controllers\SubscriptionController;
use App\Livewire\Memory\MemoryProfileSetup;
use App\Livewire\Memory\MemoryRecordDetail;
use App\Livewire\Memory\MemoryRecordEditor;
use App\Livewire\Memory\MemoryTimeline;
use App\Models\MemoryRecord;
use App\Models\Tenant;
use App\Models\User;
use App\Services\MemoryProfileService;
use App\Services\PlanService;
use App\Services\SessionService;
use App\Services\TenantCreationService;
use App\Services\UserDashboardService;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
| If you want the URL to be added to the sitemap, add a "sitemapped" middleware to the route (it has to GET route)
|
*/

Route::get('/', function () {
    return view('home');
})->name('home')->middleware('sitemapped');

Route::get('/dashboard', function (UserDashboardService $dashboardService) {
    return redirect($dashboardService->getUserDashboardUrl(Auth::user()));
})->name('dashboard')->middleware('auth');

Route::middleware(['auth', 'verified', 'tenant.member'])
    ->prefix('/memories/{tenant:uuid}')
    ->name('memories.')
    ->group(function () {
        Route::get('/', function (Tenant $tenant, MemoryProfileService $memoryProfileService): RedirectResponse {
            /** @var User|null $user */
            $user = Auth::user();

            if ($user === null) {
                abort(404);
            }

            if ($memoryProfileService->isComplete($user)) {
                return redirect()->route('memories.timeline', [
                    'tenant' => $tenant,
                ]);
            }

            return redirect()->route('memories.profile.setup', [
                'tenant' => $tenant,
            ]);
        })->name('entry');

        Route::livewire('/profile/setup', MemoryProfileSetup::class)->name('profile.setup');

        Route::middleware('memory.profile.complete')->group(function () {
            Route::livewire('/timeline', MemoryTimeline::class)->name('timeline');
            Route::livewire('/records/create/{type}', MemoryRecordEditor::class)
                ->whereIn('type', MemoryRecord::ACTIVE_TYPES)
                ->name('records.create');
            Route::livewire('/records/{record:uuid}', MemoryRecordDetail::class)
                ->name('records.show');
            Route::livewire('/records/{record:uuid}/edit', MemoryRecordEditor::class)
                ->name('records.edit');
            Route::get('/media/{media:uuid}', [MemoryMediaController::class, 'show'])
                ->withoutScopedBindings()
                ->name('media.show');
        });
    });

Auth::routes();

Route::get('/plan/start', function (
    TenantCreationService $tenantCreationService,
    SessionService $sessionService,
    PlanService $planService,
) {
    if ($planService->getDefaultProduct() !== null) {
        if (! auth()->check()) {
            $sessionService->setCreateTenantForFreePlanUser(true);
        } else {
            $tenantCreationService->createTenantForFreePlanUser(auth()->user());
        }
    }

    return redirect()->route('register');
})->name('plan.start');

Route::get('/email/verify', function () {
    return view('auth.verify');
})->middleware('auth')->name('verification.notice');

Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
    $request->fulfill();

    $user = $request->user();
    if ($user->hasVerifiedEmail()) {
        return redirect()->route('registration.thank-you');
    }

    return redirect('/');
})->middleware(['auth', 'signed'])->name('verification.verify');

Route::get('/phone/verify', function () {
    return view('verify.sms-verification');
})->name('user.phone-verify')
    ->middleware('auth');

Route::get('/phone/verified', function () {
    return view('verify.sms-verification-success');
})->name('user.phone-verified')
    ->middleware('auth');

Route::post('/email/verification-notification', function (Request $request) {
    $request->user()->sendEmailVerificationNotification();

    return back()->with('sent');
})->middleware(['auth', 'throttle:6,1'])->name('verification.send');

Route::get('/registration/thank-you', function () {
    return view('auth.thank-you');
})->middleware('auth')->name('registration.thank-you');

Route::get('/auth/{provider}/redirect', [OAuthController::class, 'redirect'])
    ->where('provider', 'google|github|facebook|twitter-oauth-2|linkedin-openid|bitbucket|gitlab')
    ->name('auth.oauth.redirect');

Route::get('/auth/{provider}/callback', [OAuthController::class, 'callback'])
    ->where('provider', 'google|github|facebook|twitter-oauth-2|linkedin-openid|bitbucket|gitlab')
    ->name('auth.oauth.callback');

Route::get('/checkout/plan/{planSlug}', [
    SubscriptionCheckoutController::class,
    'subscriptionCheckout',
])->name('checkout.subscription');

Route::get('/checkout/convert-subscription/{subscriptionUuid}', [
    SubscriptionCheckoutController::class,
    'convertLocalSubscriptionCheckout',
])->name('checkout.convert-local-subscription');

Route::get('/already-subscribed', function () {
    return view('checkout.already-subscribed');
})->name('checkout.subscription.already-subscribed');

Route::get('/checkout/subscription/success', [
    SubscriptionCheckoutController::class,
    'subscriptionCheckoutSuccess',
])->name('checkout.subscription.success')->middleware('auth');

Route::get('/checkout/convert-subscription-success', [
    SubscriptionCheckoutController::class,
    'convertLocalSubscriptionCheckoutSuccess',
])->name('checkout.convert-local-subscription.success')->middleware('auth');

Route::get('/payment-provider/paddle/payment-link', [
    PaddleController::class,
    'paymentLink',
])->name('payment-link.paddle');

Route::get('/subscription/{subscriptionUuid}/change-plan/{planSlug}/tenant/{tenantUuid}', [
    SubscriptionController::class,
    'changePlan',
])->name('subscription.change-plan')->middleware('auth');

Route::post('/subscription/{subscriptionUuid}/change-plan/{planSlug}/tenant/{tenantUuid}', [
    SubscriptionController::class,
    'changePlan',
])->name('subscription.change-plan.post')->middleware('auth');

Route::get('/subscription/change-plan-thank-you', [
    SubscriptionController::class,
    'success',
])->name('subscription.change-plan.thank-you')->middleware('auth');

// blog
Route::controller(BlogController::class)
    ->prefix('/blog')
    ->group(function () {
        Route::get('/', 'all')->name('blog')->middleware('sitemapped');
        Route::get('/category/{slug}', 'category')->name('blog.category');
        Route::get('/{slug}', 'view')->name('blog.view');
    });

Route::get('/terms-of-service', function () {
    return view('pages.terms-of-service');
})->name('terms-of-service')->middleware('sitemapped');

Route::get('/privacy-policy', function () {
    return view('pages.privacy-policy');
})->name('privacy-policy')->middleware('sitemapped');

// Product checkout routes

Route::get('/buy/product/{productSlug}/{quantity?}', [
    ProductCheckoutController::class,
    'addToCart',
])->name('buy.product');

Route::get('/checkout/product', [
    ProductCheckoutController::class,
    'productCheckout',
])->name('checkout.product');

Route::get('/checkout/product/success', [
    ProductCheckoutController::class,
    'productCheckoutSuccess',
])->name('checkout.product.success')->middleware('auth');

// roadmap

Route::controller(RoadmapController::class)
    ->prefix('/roadmap')
    ->group(function () {
        Route::get('/', 'index')->name('roadmap');
        Route::get('/i/{itemSlug}', 'viewItem')->name('roadmap.viewItem');
        Route::get('/suggest', 'suggest')->name('roadmap.suggest')->middleware('auth');
    });

// Invitations

Route::get('/invitations', [
    InvitationController::class,
    'index',
])->name('invitations')->middleware('auth');

// Invoice

Route::controller(InvoiceController::class)
    ->prefix('/invoice')
    ->group(function () {
        Route::get('/generate/{transactionUuid}', 'generate')->name('invoice.generate');
        Route::get('/preview', 'preview')->name('invoice.preview');
    });
