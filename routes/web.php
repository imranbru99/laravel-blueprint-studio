<?php

use Imran\BlueprintStudio\Http\Controllers\StudioController;
use Illuminate\Support\Facades\Route;

Route::get('/', [StudioController::class, 'index'])->name('index');

Route::prefix('api')->name('api.')->group(function () {
    Route::get('/bootstrap', [StudioController::class, 'bootstrap'])->name('bootstrap');
    Route::get('/field-types', [StudioController::class, 'fieldTypes'])->name('field-types');
    Route::post('/models', [StudioController::class, 'createModel'])->name('models.create');
    Route::post('/fields/sync', [StudioController::class, 'syncFields'])->name('fields.sync');
    Route::post('/controllers', [StudioController::class, 'createController'])->name('controllers.create');
    Route::post('/crud', [StudioController::class, 'generateCrud'])->name('crud.generate');
    Route::post('/crud/batch', [StudioController::class, 'generateBatch'])->name('crud.batch');
    Route::post('/draft/parse', [StudioController::class, 'parseDraft'])->name('draft.parse');
    Route::post('/draft/import', [StudioController::class, 'importDraft'])->name('draft.import');
    Route::get('/history', [StudioController::class, 'history'])->name('history');
    Route::delete('/history', [StudioController::class, 'clearHistory'])->name('history.clear');
});
