<?php

use App\Http\Controllers\DocumentController;
use App\Livewire\Library;
use Illuminate\Support\Facades\Route;

Route::get('/', Library::class)->name('library')->block(10, 10);
Route::get('/documents/new', [DocumentController::class, 'create'])->name('documents.create');
Route::post('/documents', [DocumentController::class, 'store'])->name('documents.store')->block(10, 10);
Route::get('/documents/{document}', [DocumentController::class, 'show'])->name('documents.show');
Route::get('/documents/{document}/edit', [DocumentController::class, 'edit'])->name('documents.edit');
Route::patch('/documents/{document}', [DocumentController::class, 'update'])->name('documents.update')->block(10, 10);
Route::delete('/documents/{document}', [DocumentController::class, 'destroy'])->name('documents.destroy')->block(10, 10);
