<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\PerfilController;
use App\Http\Controllers\SesionController;
use App\Http\Controllers\RolController;
use App\Http\Controllers\VistaController;
use App\Http\Controllers\PermisoController;
use App\Http\Controllers\ProductoController;

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Rutas para el controlador de Usuario
Route::get('/usuarios', [UsuarioController::class, 'listar']);
Route::post('/usuarios/paginado', [UsuarioController::class, 'paginado']);
Route::get('/usuarios/obtener/{usuario}', [UsuarioController::class, 'obtener']);
Route::post('/usuarios', [UsuarioController::class, 'guardar']);
Route::put('/usuarios', [UsuarioController::class, 'actualizar']);
Route::patch('/usuarios', [UsuarioController::class, 'actualizar']);
Route::delete('/usuarios', [UsuarioController::class, 'eliminar']);
Route::post('/usuarios/restablecer', [UsuarioController::class, 'restablecer']);

// Rutas para el controlador de Perfil
Route::get('/perfil/{id_relativo}/{medida}', [PerfilController::class, 'perfil']);
Route::put('/perfil/cuenta', [PerfilController::class, 'cuenta']);
Route::patch('/perfil/cuenta', [PerfilController::class, 'cuenta']);
Route::put('/perfil/clave', [PerfilController::class, 'clave']);
Route::patch('/perfil/clave', [PerfilController::class, 'clave']);
Route::put('/perfil/personal', [PerfilController::class, 'personal']);
Route::patch('/perfil/personal', [PerfilController::class, 'personal']);

// Rutas para el controlador de Sesion
Route::post('/sesion/ingresar', [SesionController::class, 'ingresar']);
Route::post('/sesion/cerrar', [SesionController::class, 'cerrar']);
Route::post('/sesion/verificar', [SesionController::class, 'verificar']);

// Rutas para el controlador de Rol
Route::get('/roles', [RolController::class, 'listar']);
Route::post('/roles/paginado', [RolController::class, 'paginado']);
Route::post('/roles', [RolController::class, 'guardar']);
Route::put('/roles', [RolController::class, 'actualizar']);
Route::patch('/roles', [RolController::class, 'actualizar']);
Route::put('/roles/permisos', [RolController::class, 'permisos']);
Route::delete('/roles', [RolController::class, 'eliminar']);
Route::post('/roles/restablecer', [RolController::class, 'restablecer']);

// Rutas para el controlador de Vista
Route::get('/vistas', [VistaController::class, 'listar']);
Route::post('/vistas/paginado', [VistaController::class, 'paginado']);
Route::post('/vistas', [VistaController::class, 'guardar']);
Route::put('/vistas', [VistaController::class, 'actualizar']);
Route::patch('/vistas', [VistaController::class, 'actualizar']);
Route::delete('/vistas', [VistaController::class, 'eliminar']);
Route::post('/vistas/restablecer', [VistaController::class, 'restablecer']);

// Rutas para el controlador de Permiso
Route::get('/permisos', [PermisoController::class, 'listar']);
Route::post('/permisos', [PermisoController::class, 'guardar']);
Route::put('/permisos', [PermisoController::class, 'actualizar']);
Route::patch('/permisos', [PermisoController::class, 'actualizar']);
Route::delete('/permisos', [PermisoController::class, 'eliminar']);
Route::post('/permisos/restablecer', [PermisoController::class, 'restablecer']);
Route::post('/permisos/paginado', [PermisoController::class, 'paginado']);

// Rutas para el controlador de Medida
Route::get('/medidas', [MedidaController::class, 'listar']);
Route::post('/medidas', [MedidaController::class, 'guardar']);
Route::put('/medidas', [MedidaController::class, 'actualizar']);
Route::patch('/medidas', [MedidaController::class, 'actualizar']);
Route::delete('/medidas', [MedidaController::class, 'eliminar']);
Route::post('/medidas/restablecer', [MedidaController::class, 'restablecer']);
Route::post('/medidas/paginado', [MedidaController::class, 'paginado']);

// Rutas para el controlador de Medida
Route::get('/categorias', [CategoriaController::class, 'listar']);
Route::post('/categorias', [CategoriaController::class, 'guardar']);
Route::put('/categorias', [CategoriaController::class, 'actualizar']);
Route::patch('/categorias', [CategoriaController::class, 'actualizar']);
Route::delete('/categorias', [CategoriaController::class, 'eliminar']);
Route::post('/categorias/restablecer', [CategoriaController::class, 'restablecer']);
Route::post('/categorias/paginado', [CategoriaController::class, 'paginado']);

// Rutas para el controlador de Producto
Route::get('/productos', [ProductoController::class, 'listar']);
Route::post('/productos', [ProductoController::class, 'guardar']);
Route::put('/productos', [ProductoController::class, 'actualizar']);
Route::patch('/productos', [ProductoController::class, 'actualizar']);
Route::delete('/productos', [ProductoController::class, 'eliminar']);
Route::post('/productos/restablecer', [ProductoController::class, 'restablecer']);
Route::post('/productos/paginado', [ProductoController::class, 'paginado']);