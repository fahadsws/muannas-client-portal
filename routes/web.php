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
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect('/board'));
Route::get('/login', [Au::class, 'form'])->name('login');
Route::post('/login', [Au::class, 'login']);
Route::post('/logout', [Au::class, 'logout']);
Route::post('/impersonate/stop', [Au::class, 'stopImpersonating'])->middleware('auth');

Route::get('/share/{token}', [B::class, 'share']);

Route::middleware('auth')->group(function () {
    Route::get('/board', [B::class, 'page']);
    Route::get('/help', [H::class, 'page']);
    Route::get('/help/pdf', [H::class, 'pdf']);
    Route::get('/docs/preview', [D::class, 'preview'])->name('docs.preview');
    Route::get('/docs/export/{format}', [D::class, 'export']);
});

Route::middleware(['auth', 'staff'])->group(function () {
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
