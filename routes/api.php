<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

//Liste
Route::get('lists', [\App\Http\Controllers\ListModelController::class, 'index']);
Route::get('lists/{id}', [\App\Http\Controllers\ListModelController::class, 'findById']);
Route::get('lists/checkid/{id}', [\App\Http\Controllers\ListModelController::class, 'checkId']);
Route::get('lists/search/{searchTerm}', [\App\Http\Controllers\ListModelController::class, 'findBySearchTerm']);

//Tag
Route::get('tags', [\App\Http\Controllers\TagController::class, 'index']);
Route::get('tags/{id}', [\App\Http\Controllers\TagController::class, 'findById']);
Route::get('tags/search/{searchTerm}', [\App\Http\Controllers\TagController::class, 'findBySearchTerm']);

//Note
Route::get('notes', [\App\Http\Controllers\NoteController::class, 'index']);
Route::get('notes/search/{searchTerm}', [\App\Http\Controllers\NoteController::class, 'findBySearchTerm']);

//Todo
Route::get('todos', [\App\Http\Controllers\TodoController::class, 'index']);
Route::get('todos/{id}', [\App\Http\Controllers\TodoController::class, 'findById']);
Route::get('todos/search/{searchTerm}', [\App\Http\Controllers\TodoController::class, 'findBySearchTerm']);

//Users
Route::get('users', [\App\Http\Controllers\UserController::class, 'index']);
Route::get('users/{id}', [\App\Http\Controllers\UserController::class, 'findById']);

//Auth
Route::post('auth/login', [\App\Http\Controllers\AuthController::class, 'login']);
Route::group(['middleware' => ['api', 'auth.jwt']], function (){
    //Liste
    Route::post('lists', [\App\Http\Controllers\ListModelController::class, 'save']);
    Route::put('lists/{id}', [\App\Http\Controllers\ListModelController::class, 'update']);
    Route::delete('lists/{id}', [\App\Http\Controllers\ListModelController::class, 'delete']);

    //Tag
    Route::post('tags', [\App\Http\Controllers\TagController::class, 'save']);
    Route::put('tags/{id}', [\App\Http\Controllers\TagController::class, 'update']);
    Route::delete('tags/{id}', [\App\Http\Controllers\TagController::class, 'delete']);

    //Note
    Route::post('notes', [\App\Http\Controllers\NoteController::class, 'save']);
    Route::put('notes/{id}', [\App\Http\Controllers\NoteController::class, 'update']);
    Route::delete('notes/{id}', [\App\Http\Controllers\NoteController::class, 'delete']);

    //Todo
    Route::post('todos', [\App\Http\Controllers\TodoController::class, 'save']);
    Route::put('todos/{id}', [\App\Http\Controllers\TodoController::class, 'update']);
    Route::delete('todos/{id}', [\App\Http\Controllers\TodoController::class, 'delete']);

    //Users
    Route::post('users', [\App\Http\Controllers\UserController::class, 'save']);
    Route::put('users/{id}', [\App\Http\Controllers\UserController::class, 'update']);
    Route::delete('users/{id}', [\App\Http\Controllers\UserController::class, 'delete']);

    //Auth
    Route::post('auth/logout', [\App\Http\Controllers\AuthController::class, 'logout']);
});
