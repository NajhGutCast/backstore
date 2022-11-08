<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ViewsController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\ModuleController;
use App\Http\Controllers\EnvironmentController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SessionController;
use App\Http\Controllers\ActivityController;
use App\Models\Role;


Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// USERS
Route::get('/users', [UserController::class, 'index']);
Route::post('/users', [UserController::class, 'store']);
Route::put('/users', [UserController::class, 'update']);
Route::delete('/users', [UserController::class, 'destroy']);
Route::post('/users/restore', [UserController::class, 'restore']);
Route::get('/users/get/{username}', [UserController::class, 'getUser']);
Route::post('/users/paginate', [UserController::class, 'paginate']);


// PROFILE
Route::get('/profile/{relative_id}/{zize}', [ProfileController::class, 'profile']);
Route::put('/profile/account', [ProfileController::class, 'account']);
Route::patch('/profile/account', [ProfileController::class, 'account']);
Route::put('/profile/password', [ProfileController::class, 'password']);
Route::patch('/profile/password', [ProfileController::class, 'password']);
Route::put('/profile/personal', [ProfileController::class, 'personal']);
Route::patch('/profile/personal', [ProfileController::class, 'personal']);


// SESSION
Route::post('/session/login', [SessionController::class, 'login']);
Route::post('/session/logout', [SessionController::class, 'logout']);
Route::post('/session/verify', [SessionController::class, 'verify']);


// ROLE
Route::get('/roles', [RoleController::class, 'index']);
Route::post('/roles', [RoleController::class, 'store']);
Route::put('/roles', [RoleController::class, 'update']);
Route::patch('/roles', [RoleController::class, 'update']);
Route::delete('/roles', [RoleController::class, 'destroy']);
Route::post('/roles/restore', [RoleController::class, 'restore']);
Route::post('/roles/paginate', [RoleController::class, 'paginate']);
Route::put('/roles/permissions', [RoleController::class, 'permissions']);


// VIEWS 
Route::get('/views', [ViewsController::class, 'index']);
Route::post('/views/paginate', [ViewsController::class, 'paginate']);
Route::post('/views', [ViewsController::class, 'store']);
Route::put('/views', [ViewsController::class, 'update']);
Route::delete('/views', [ViewsController::class, 'delete']);
Route::post('/views/restore', [ViewsController::class, 'restore']);


// PERMISSIONS
Route::get('/permissions', [PermissionController::class, 'index']);
Route::post('/permissions', [PermissionController::class, 'store']);
Route::put('/permissions', [PermissionController::class, 'update']);
Route::delete('/permissions', [PermissionController::class, 'delete']);
Route::post('/permissions/restore', [PermissionController::class, 'restore']);
Route::post('/permissions/paginate', [PermissionController::class, 'paginate']);

Route::get('/productos', [ProductosController::class, 'listar']);
Route::post('/productos', [ProductosController::class, 'agregar']);

Route::any('/*', null);