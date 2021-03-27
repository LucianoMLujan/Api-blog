<?php

use Illuminate\Support\Facades\Route;
use App\Http\Middleware\ApiAuthMiddleware;
use App\Http\Controllers\UserController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\PostController;


/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});


//Rutas del controlador de usuarios
Route::post('/api/register', [UserController::class, 'register']);
Route::post('/api/login', [UserController::class, 'login']);
Route::put('/api/user/update', [UserController::class, 'update']);
Route::post('/api/user/upload', [UserController::class, 'upload'])->middleware(ApiAuthMiddleware::class);
Route::get('/api/user/avatar/{filename}', [UserController::class, 'getImage']);
Route::get('/api/user/detail/{id}', [UserController::class, 'detail']);

//Rutas del controlador de categorias
Route::resource('/api/category', CategoryController::class);

//Rutas del controlador de posts
Route::resource('/api/post', PostController::class);
Route::post('/api/post/upload', [PostController::class ,'upload']);
Route::get('/api/post/image/{filename}', [PostController::class ,'getImage']);
Route::get('/api/post/category/{id}', [PostController::class ,'getPostsByCategory']);
Route::get('/api/post/user/{id}', [PostController::class ,'getPostsByUser']);