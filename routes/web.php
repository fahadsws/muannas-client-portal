<?php

use App\Http\Controllers\AgencyController as A;
use App\Http\Controllers\AuthController as Au;
use App\Http\Controllers\BoardController as B;
use App\Http\Controllers\DocController as D;
use App\Http\Controllers\HelpController as H;
use App\Http\Controllers\RoleController as RC;
use App\Http\Controllers\TeamController as T;
use App\Http\Controllers\SuperController as S;
use App\Http\Controllers\SuperReportController as SR;
use App\Http\Controllers\Portal as P;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect('/portal'));
Route::get('/login', [Au::class, 'form'])->name('login');
Route::post('/login', [Au::class, 'login'])->middleware('throttle:10,1');
Route::get('/forgot-password', [Au::class, 'forgotForm'])->name('password.request');
Route::post('/forgot-password', [Au::class, 'sendResetLink'])->middleware('throttle:5,1');
Route::get('/reset-password/{token}', [Au::class, 'resetForm'])->name('password.reset');
Route::post('/reset-password', [Au::class, 'reset'])->middleware('throttle:10,1');
Route::post('/logout', [Au::class, 'logout']);
Route::post('/impersonate/stop', [Au::class, 'stopImpersonating'])->middleware('auth');

Route::get('/brand/{kind}', [\App\Http\Controllers\BrandController::class, 'show']);
Route::get('/share/{token}', [B::class, 'share']);

Route::middleware('auth')->group(function () {
    Route::get('/agency-logo/{agency}', [A::class, 'logo']);
    Route::get('/help', [H::class, 'page']);
    Route::get('/help/pdf', [H::class, 'pdf']);
    Route::get('/docs/preview', [D::class, 'preview'])->name('docs.preview');
    Route::get('/docs/export/{format}', [D::class, 'export']);
});

// WhatsApp review link: signs the client in first, then lands on the batch
Route::get('/r/{token}', [P\ContentController::class, 'batch'])->middleware(['auth', 'portal'])->name('portal.batch');

Route::middleware(['auth', 'portal'])->prefix('portal')->group(function () {
    Route::get('/', [P\DashboardController::class, 'home']);
    Route::get('admin', [P\ContentController::class, 'approvals']);
    Route::get('approvals', fn () => redirect('/portal/admin'));

    Route::get('c/{account}', [P\DashboardController::class, 'show']);
    Route::get('c/{account}/logo', [P\DashboardController::class, 'logo'])->name('portal.logo');
    Route::get('c/{account}/calendar', [P\CalendarController::class, 'show']);

    Route::get('c/{account}/content', [P\ContentController::class, 'index']);
    Route::get('c/{account}/content/new', [P\ContentController::class, 'create']);
    Route::post('c/{account}/content', [P\ContentController::class, 'store']);
    Route::post('c/{account}/content/send', [P\ContentController::class, 'send']);
    Route::get('c/{account}/content/bulk', [P\ContentController::class, 'bulkForm']);
    Route::post('c/{account}/content/bulk', [P\ContentController::class, 'bulkStore'])->middleware('throttle:20,1');
    Route::get('bulk-template.csv', [P\ContentController::class, 'bulkTemplate']);
    Route::get('posts/{post}', [P\ContentController::class, 'show']);
    Route::get('posts/{post}/edit', [P\ContentController::class, 'edit']);
    Route::put('posts/{post}', [P\ContentController::class, 'update']);
    Route::delete('posts/{post}', [P\ContentController::class, 'destroy']);
    Route::get('posts/{post}/media/{i}', [P\ContentController::class, 'media'])->whereNumber('i')->name('portal.media');
    Route::post('posts/{post}/comments', [P\ContentController::class, 'comment'])->middleware('throttle:30,1');
    Route::post('posts/{post}/approve', [P\ContentController::class, 'approve']);
    Route::post('posts/{post}/changes', [P\ContentController::class, 'requestChanges']);
    Route::post('posts/{post}/status', [P\ContentController::class, 'status']);

    Route::get('c/{account}/projects', [P\ProjectController::class, 'index']);
    Route::post('c/{account}/projects', [P\ProjectController::class, 'store']);
    Route::put('projects/{project}', [P\ProjectController::class, 'update']);
    Route::delete('projects/{project}', [P\ProjectController::class, 'destroy']);
    Route::post('projects/{project}/tasks', [P\ProjectController::class, 'addTask']);
    Route::put('tasks/{task}', [P\ProjectController::class, 'updateTask']);
    Route::delete('tasks/{task}', [P\ProjectController::class, 'destroyTask']);

    Route::get('c/{account}/files', [P\FileController::class, 'index']);
    Route::post('c/{account}/files', [P\FileController::class, 'store'])->middleware('throttle:30,1');
    Route::get('c/{account}/files/folder/{folder}', [P\FileController::class, 'show']);
    Route::post('c/{account}/file-folders', [P\FileController::class, 'storeFolder']);
    Route::put('file-folders/{folder}', [P\FileController::class, 'updateFolder']);
    Route::delete('file-folders/{folder}', [P\FileController::class, 'destroyFolder']);
    Route::get('files/{file}', [P\FileController::class, 'download']);
    Route::delete('files/{file}', [P\FileController::class, 'destroy']);

    Route::get('invoice-settings', [P\InvoiceSettingsController::class, 'show']);
    Route::post('invoice-settings', [P\InvoiceSettingsController::class, 'save']);
    Route::post('invoice-taxes', [P\InvoiceSettingsController::class, 'storeTax']);
    Route::put('invoice-taxes/{tax}', [P\InvoiceSettingsController::class, 'updateTax']);
    Route::delete('invoice-taxes/{tax}', [P\InvoiceSettingsController::class, 'destroyTax']);
    Route::get('c/{account}/invoices', [P\InvoiceController::class, 'index']);
    Route::get('c/{account}/payments', [P\InvoiceController::class, 'payments']);
    Route::get('c/{account}/invoices/new', [P\InvoiceController::class, 'create']);
    Route::post('c/{account}/invoices', [P\InvoiceController::class, 'store']);
    Route::get('invoices/{invoice}', [P\InvoiceController::class, 'show']);
    Route::get('invoices/{invoice}/edit', [P\InvoiceController::class, 'edit']);
    Route::put('invoices/{invoice}', [P\InvoiceController::class, 'update']);
    Route::post('invoices/{invoice}/send', [P\InvoiceController::class, 'send']);
    Route::get('invoices/{invoice}/pdf', [P\InvoiceController::class, 'pdf']);
    Route::post('invoices/{invoice}/paid', [P\InvoiceController::class, 'markPaid']);
    Route::post('invoices/{invoice}/unpaid', [P\InvoiceController::class, 'markUnpaid']);
    Route::delete('invoices/{invoice}', [P\InvoiceController::class, 'destroy']);

    Route::get('c/{account}/reports', [P\ReportController::class, 'index']);
    Route::post('c/{account}/reports', [P\ReportController::class, 'store']);
    Route::get('reports/{report}/pdf', [P\ReportController::class, 'download']);
    Route::delete('reports/{report}', [P\ReportController::class, 'destroy']);

    // admin panel: clients, their logins and team assignment
    Route::get('clients', [P\ClientAdminController::class, 'index']);
    Route::get('clients/create', [P\ClientAdminController::class, 'create']);
    Route::post('clients', [P\ClientAdminController::class, 'store']);
    Route::get('clients/{account}', [P\ClientAdminController::class, 'edit']);
    Route::put('clients/{account}', [P\ClientAdminController::class, 'update']);
    Route::delete('clients/{account}', [P\ClientAdminController::class, 'destroy']);
    Route::post('clients/{account}/users', [P\ClientAdminController::class, 'addUser']);
    Route::post('clients/{account}/users/{user}/reset', [P\ClientAdminController::class, 'resetUser']);
    Route::delete('clients/{account}/users/{user}', [P\ClientAdminController::class, 'removeUser']);
});

Route::middleware(['auth', 'staff'])->group(function () {
    Route::get('/board', [B::class, 'page']);
    Route::get('/team', [T::class, 'index']);
    Route::get('/team/create', [T::class, 'create']);
    Route::post('/team', [T::class, 'store']);
    Route::get('/team/{user}/edit', [T::class, 'edit']);
    Route::put('/team/{user}', [T::class, 'update']);
    Route::delete('/team/{user}', [T::class, 'destroy']);

    Route::get('/roles', [RC::class, 'index']);
    Route::get('/roles/create', [RC::class, 'create']);
    Route::post('/roles', [RC::class, 'store']);
    Route::get('/roles/{role}/edit', [RC::class, 'edit']);
    Route::put('/roles/{role}', [RC::class, 'update']);
    Route::delete('/roles/{role}', [RC::class, 'destroy']);
});

Route::middleware(['auth', 'super'])->prefix('admin')->group(function () {
    Route::get('/', [S::class, 'dashboard']);
    Route::get('agencies', [S::class, 'agencies']);
    Route::get('agencies/create', [S::class, 'create']);
    Route::post('agencies', [S::class, 'store']);
    Route::get('agencies/{agency}', [S::class, 'show']);
    Route::patch('agencies/{agency}', [S::class, 'update']);
    Route::put('agencies/{agency}/owner', [S::class, 'updateOwner']);
    Route::post('agencies/{agency}/password', [S::class, 'resetPassword']);
    Route::post('agencies/{agency}/impersonate', [S::class, 'impersonate']);
    Route::delete('agencies/{agency}', [S::class, 'destroy']);
    Route::get('reports', [S::class, 'reports']);
    Route::get('report/preview', [SR::class, 'preview'])->name('admin.report.preview');
    Route::get('report/export/{format}', [SR::class, 'export']);
    Route::get('settings', [S::class, 'settings']);
    Route::post('settings', [S::class, 'saveSettings']);
    Route::post('settings/account', [S::class, 'saveAdmin']);
});

Route::prefix('api')->group(function () {
    // agency-level (logged-in staff)
    Route::middleware('auth')->group(function () {
        Route::patch('agency', [A::class, 'update']);
        Route::post('agency/logo', [A::class, 'uploadLogo']);
        Route::delete('agency/logo', [A::class, 'removeLogo']);
        Route::get('templates', [A::class, 'templates']);
        Route::post('templates', [A::class, 'storeTemplate']);
        Route::put('templates/{template}', [A::class, 'updateTemplate']);
        Route::delete('templates/{template}', [A::class, 'destroyTemplate']);
        Route::post('workspaces', [A::class, 'storeWorkspace']);
        Route::patch('workspaces/{account}', [A::class, 'updateWorkspace']);
        Route::delete('workspaces/{account}', [A::class, 'destroyWorkspace']);
        Route::patch('accounts/{account}/share', [A::class, 'updateShare']);
        Route::get('accounts/{account}/people', [A::class, 'people']);
        Route::post('accounts/{account}/people', [A::class, 'addPerson']);
        Route::delete('accounts/{account}/people/{user}', [A::class, 'removePerson']);
    });

    // workspace content (login or client share token — checked in the controller)
    Route::get('accounts/{account}', [B::class, 'show']);
    Route::post('accounts/{account}/tabs', [B::class, 'addTab']);
    Route::get('accounts/{account}/notes', [B::class, 'notes']);
    Route::post('accounts/{account}/notes', [B::class, 'addNote']);
    Route::put('accounts/{account}/docs', [B::class, 'setDocs']);
    Route::delete('notes/{note}', [B::class, 'deleteNote']);
    Route::patch('tabs/{tab}', [B::class, 'updateTab']);
    Route::delete('tabs/{tab}', [B::class, 'deleteTab']);
    Route::post('tabs/{tab}/items', [B::class, 'addItem']);
    Route::patch('items/{item}', [B::class, 'updateItem']);
    Route::delete('items/{item}', [B::class, 'deleteItem']);
    Route::post('items/{item}/image', [B::class, 'upload']);
});
