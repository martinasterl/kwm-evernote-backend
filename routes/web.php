<?php

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
*/

Route::get('/', function () {

    $lists = \App\Models\ListModel::all();
    $todos = \App\Models\Todo::whereNull('note_id')->get();

    // Daten an die Ansicht senden
    return view('index', compact('lists', 'todos'));
});

//Alternative-Methode:

// Alle Einträge der Tabelle 'lists' werden herausgespeichert
Route::get('/lists', [\App\Http\Controllers\ListModelController::class, 'index']);
Route::get('/lists/{id}', [\App\Http\Controllers\ListModelController::class, 'findById']);
Route::get('/lists/checkid/{id}', [\App\Http\Controllers\ListModelController::class, 'checkId']);

// Abfragen aller Todos, bei denen 'note_id' NULL ist
Route::get('/todos', [\App\Http\Controllers\TodoController::class, 'index']);
Route::get('/todos/{id}', [\App\Http\Controllers\TodoController::class, 'show']);
