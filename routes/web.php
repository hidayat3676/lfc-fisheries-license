<?php

use App\Http\Controllers\Admin\ApplicationController as AdminApplicationController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\LicenseCategoryController;
use App\Http\Controllers\Admin\OfficeController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\ReservoirController;
use App\Http\Controllers\Admin\SetupController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\ViolationController as AdminViolationController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Citizen\ApplicationController as CitizenApplicationController;
use App\Http\Controllers\Citizen\DashboardController as CitizenDashboardController;
use App\Http\Controllers\Citizen\LicenseCardController;
use App\Http\Controllers\Citizen\ProfileController;
use App\Http\Controllers\Portal\CatalogueController;
use App\Http\Controllers\Portal\LicenseVerifyController;
use App\Http\Controllers\Portal\ViolationReportController;
use App\Http\Middleware\EnsureCitizen;
use App\Http\Middleware\EnsureStaff;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    $featuredReservoirs = \App\Models\Reservoir::query()
        ->where('is_active', true)
        ->with('district')
        ->orderBy('name')
        ->limit(6)
        ->get();

    $categories = \App\Models\LicenseCategory::query()
        ->where('is_active', true)
        ->orderBy('fee_amount')
        ->get();

    $districts = \App\Models\District::query()
        ->where('is_active', true)
        ->orderBy('name')
        ->get();

    $totalReservoirs = \App\Models\Reservoir::query()->where('is_active', true)->count();
    $totalDistricts = \App\Models\District::query()->where('is_active', true)->count();

    return view('welcome', compact('featuredReservoirs', 'categories', 'districts', 'totalReservoirs', 'totalDistricts'));
})->name('home');

Route::get('/water-bodies', [CatalogueController::class, 'index'])->name('catalogue.index');
Route::get('/water-bodies/{reservoir}', [CatalogueController::class, 'show'])->name('catalogue.show');
Route::get('/verify/licence/{token}', LicenseVerifyController::class)->name('license.verify');
Route::get('/verify/license/{token}', LicenseVerifyController::class);

Route::get('/report-violation', [ViolationReportController::class, 'create'])->name('violations.create');
Route::post('/report-violation', [ViolationReportController::class, 'store'])->name('violations.store');
Route::get('/report-violation/{report}/thanks', [ViolationReportController::class, 'thanks'])->name('violations.thanks');

Route::middleware('guest')->group(function () {
    Route::get('/register', [RegisterController::class, 'show'])->name('register');
    Route::post('/register/otp', [RegisterController::class, 'sendOtp'])->name('register.otp');
    Route::get('/register/verify', [RegisterController::class, 'showVerify'])->name('register.verify');
    Route::post('/register/verify', [RegisterController::class, 'verify'])->name('register.verify.submit');
    Route::post('/register/resend', [RegisterController::class, 'resend'])->name('register.resend');

    Route::get('/login', [LoginController::class, 'showCitizen'])->name('login');
    Route::post('/login', [LoginController::class, 'loginCitizen'])->name('login.submit');
    Route::get('/login/pattern-verify', [LoginController::class, 'showPatternVerify'])->name('pattern.verify');
    Route::post('/login/pattern-verify', [LoginController::class, 'verifyPattern'])->name('pattern.verify.submit');

    Route::get('/staff/login', [LoginController::class, 'showStaff'])->name('staff.login');
    Route::post('/staff/login', [LoginController::class, 'loginStaff'])->name('staff.login.submit');

    Route::get('/forgot-password', [ForgotPasswordController::class, 'show'])->name('password.request');
    Route::post('/forgot-password', [ForgotPasswordController::class, 'sendOtp'])->name('password.email');
    Route::get('/reset-password', [ForgotPasswordController::class, 'showReset'])->name('password.reset');
    Route::post('/reset-password', [ForgotPasswordController::class, 'reset'])->name('password.update');
    Route::post('/reset-password/resend', [ForgotPasswordController::class, 'resend'])->name('password.resend');
});

Route::post('/logout', [LoginController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware(['auth', EnsureCitizen::class])->prefix('account')->name('citizen.')->group(function () {
    Route::get('/', CitizenDashboardController::class)->name('dashboard');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/profile/pattern-lock', [ProfileController::class, 'updatePatternLock'])->name('profile.pattern-lock');
    Route::get('/applications', [CitizenApplicationController::class, 'index'])->name('applications.index');
    Route::get('/applications/{application}', [CitizenApplicationController::class, 'show'])->name('applications.show');
    Route::get('/applications/{application}/success', [CitizenApplicationController::class, 'success'])->name('applications.success');
    Route::get('/licenses/{license}/card', LicenseCardController::class)->name('licenses.card');
    Route::get('/water-bodies/{reservoir}/apply', [CitizenApplicationController::class, 'create'])->name('applications.create');
    Route::post('/water-bodies/{reservoir}/apply', [CitizenApplicationController::class, 'store'])->name('applications.store');
});

Route::middleware(['auth', EnsureStaff::class])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', AdminDashboardController::class)->name('dashboard');

    Route::middleware('module:users,view')->group(function () {
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
    });
    Route::middleware('module:users,create')->group(function () {
        Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
    });
    Route::middleware('module:users,edit')->group(function () {
        Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
    });

    Route::middleware('module:offices,view')->group(function () {
        Route::get('/offices', [OfficeController::class, 'index'])->name('offices.index');
    });
    Route::middleware('module:offices,create')->group(function () {
        Route::get('/offices/create', [OfficeController::class, 'create'])->name('offices.create');
        Route::post('/offices', [OfficeController::class, 'store'])->name('offices.store');
    });
    Route::middleware('module:offices,edit')->group(function () {
        Route::get('/offices/{office}/edit', [OfficeController::class, 'edit'])->name('offices.edit');
        Route::put('/offices/{office}', [OfficeController::class, 'update'])->name('offices.update');
    });

    Route::middleware('module:reservoirs,view')->group(function () {
        Route::get('/reservoirs', [ReservoirController::class, 'index'])->name('reservoirs.index');
    });
    Route::middleware('module:reservoirs,create')->group(function () {
        Route::get('/reservoirs/create', [ReservoirController::class, 'create'])->name('reservoirs.create');
        Route::post('/reservoirs', [ReservoirController::class, 'store'])->name('reservoirs.store');
    });
    Route::middleware('module:reservoirs,edit')->group(function () {
        Route::get('/reservoirs/{reservoir}/edit', [ReservoirController::class, 'edit'])->name('reservoirs.edit');
        Route::put('/reservoirs/{reservoir}', [ReservoirController::class, 'update'])->name('reservoirs.update');
    });

    Route::middleware('module:license_categories,view')->group(function () {
        Route::get('/categories', [LicenseCategoryController::class, 'index'])->name('categories.index');
    });
    Route::middleware('module:license_categories,create')->group(function () {
        Route::get('/categories/create', [LicenseCategoryController::class, 'create'])->name('categories.create');
        Route::post('/categories', [LicenseCategoryController::class, 'store'])->name('categories.store');
    });
    Route::middleware('module:license_categories,edit')->group(function () {
        Route::get('/categories/{category}/edit', [LicenseCategoryController::class, 'edit'])->name('categories.edit');
        Route::put('/categories/{category}', [LicenseCategoryController::class, 'update'])->name('categories.update');
    });

    Route::middleware('module:applications,create')->group(function () {
        Route::get('/applications/create', [AdminApplicationController::class, 'create'])->name('applications.create');
        Route::post('/applications', [AdminApplicationController::class, 'store'])->name('applications.store');
    });
    Route::middleware('module:applications,view')->group(function () {
        Route::get('/applications', [AdminApplicationController::class, 'index'])->name('applications.index');
        Route::get('/applications/{application}', [AdminApplicationController::class, 'show'])->name('applications.show');
    });
    Route::middleware('module:applications,approve')->group(function () {
        Route::post('/applications/{application}/approve', [AdminApplicationController::class, 'approve'])->name('applications.approve');
        Route::post('/applications/{application}/reject', [AdminApplicationController::class, 'reject'])->name('applications.reject');
        Route::post('/applications/{application}/request-info', [AdminApplicationController::class, 'requestInfo'])->name('applications.request-info');
    });

    Route::middleware('module:violations,view')->group(function () {
        Route::get('/violations', [AdminViolationController::class, 'index'])->name('violations.index');
        Route::get('/violations/{violation}', [AdminViolationController::class, 'show'])->name('violations.show');
    });
    Route::middleware('module:violations,status')->group(function () {
        Route::post('/violations/{violation}/status', [AdminViolationController::class, 'updateStatus'])->name('violations.status');
    });

    Route::middleware('module:reports,view')->group(function () {
        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('/reports/export', [ReportController::class, 'export'])->name('reports.export');
    });

    Route::middleware('module:settings,view')->group(function () {
        Route::get('/setup', [SetupController::class, 'index'])->name('setup.index');
        Route::get('/setup/fees', [SetupController::class, 'fees'])->name('setup.fees');
        Route::get('/setup/eligibility', [SetupController::class, 'eligibility'])->name('setup.eligibility');
        Route::get('/setup/templates', [SetupController::class, 'templates'])->name('setup.templates');
        Route::get('/setup/qr', [SetupController::class, 'qr'])->name('setup.qr');
    });
    Route::middleware('module:settings,edit')->group(function () {
        Route::post('/setup/fees', [SetupController::class, 'saveFees'])->name('setup.fees.save');
        Route::post('/setup/eligibility', [SetupController::class, 'saveEligibility'])->name('setup.eligibility.save');
        Route::post('/setup/templates', [SetupController::class, 'saveTemplates'])->name('setup.templates.save');
        Route::post('/setup/qr', [SetupController::class, 'saveQr'])->name('setup.qr.save');
    });
});
