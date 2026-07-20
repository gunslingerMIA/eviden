<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FolderController;
use App\Http\Controllers\EvaluationController;

Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

// Route gudang eviden (folder dan dokumen)
Route::prefix('folders')->name('folders.')->group(function (){
    Route::get('/', [FolderController::class, 'index'])->name('index');
    Route::get('/{folder}', [FolderController::class, 'show'])->name('show');
    Route::post('/', [FolderController::class, 'store'])->name('store');
    
    // Route untuk mengunggah berkas langsung di halaman utama (tanpa folder)
    Route::post('/documents/upload-root', [FolderController::class, 'storeDocumentRoot'])->name('store-document-root');
    
// Route untuk mengunggah berkas ke dalam subfolder
    Route::post('/{folder}/documents', [FolderController::class, 'storeDocument'])->name('store-document');
    
    // Aksi CRUD & Pemindahan & Download & Link Folder
    Route::put('/{folder}', [FolderController::class, 'updateFolder'])->name('update');
    Route::delete('/{folder}', [FolderController::class, 'destroyFolder'])->name('destroy');
    Route::post('/{folder}/move', [FolderController::class, 'moveFolder'])->name('move');
    Route::get('/{folder}/download-zip', [FolderController::class, 'downloadFolderZip'])->name('download-zip');
    Route::post('/{folder}/link-indicator', [FolderController::class, 'linkFolderIndicator'])->name('link-indicator');
    Route::delete('/{folder}/unlink-indicator/{indicator}', [FolderController::class, 'unlinkFolderIndicator'])->name('unlink-indicator');
});

// Aksi CRUD & Sharing & Pemindahan Dokumen
Route::post('/documents/{document}/toggle-share', [FolderController::class, 'toggleShare'])->name('documents.toggle-share');
Route::put('/documents/{document}', [FolderController::class, 'updateDocument'])->name('documents.update');
Route::delete('/documents/{document}', [FolderController::class, 'destroyDocument'])->name('documents.destroy');
Route::post('/documents/{document}/move', [FolderController::class, 'moveDocument'])->name('documents.move');

// Tempat Sampah (Recycle Bin) untuk Dokumen
Route::post('/documents/{id}/restore', [FolderController::class, 'restoreDocument'])->name('documents.restore');
Route::delete('/documents/{id}/force-delete', [FolderController::class, 'forceDeleteDocument'])->name('documents.force-delete');
Route::delete('/documents/trash/empty', [FolderController::class, 'emptyTrash'])->name('documents.empty-trash');

// Relasi Dokumen dengan Indikator Evaluasi (dari sisi Gudang Eviden)
Route::post('/documents/{document}/link-indicator', [FolderController::class, 'linkIndicator'])->name('documents.link-indicator');
Route::delete('/documents/{document}/unlink-indicator/{indicator}', [FolderController::class, 'unlinkIndicator'])->name('documents.unlink-indicator');

// Halaman Publik (Tanpa Login) untuk Melihat/Mengunduh Berkas yang Dibagikan
Route::get('/shared/{token}', [FolderController::class, 'viewShared'])->name('documents.shared');

// Simulasi Ganti Pengguna
Route::post('/switch-user/{user}', [DashboardController::class, 'switchUser'])->name('switch-user');


// Route evaluasi & indikator & penautan eviden
Route::prefix('evaluations')->name('evaluations.')->group(function (){
    Route::get('/', [EvaluationController::class, 'index'])->name('index');
    Route::post('/', [EvaluationController::class, 'store'])->name('store');
    Route::get('/{evaluation}', [EvaluationController::class, 'show'])->name('show');
    Route::put('/{evaluation}', [EvaluationController::class, 'update'])->name('update');
    Route::delete('/{evaluation}', [EvaluationController::class, 'destroy'])->name('destroy');
    
    // CRUD Indikator
    Route::post('/{evaluation}/indicators', [EvaluationController::class, 'storeIndicator'])->name('indicators.store');
    Route::put('/indicators/{indicator}', [EvaluationController::class, 'updateIndicator'])->name('indicators.update');
    Route::delete('/indicators/{indicator}', [EvaluationController::class, 'destroyIndicator'])->name('indicators.destroy');
    
    // Penautan dokumen
    Route::post('/indicators/{indicator}/link-document', [EvaluationController::class, 'linkDocument'])->name('link-document');
    Route::post('/indicators/{indicator}/unlink-document/{document}', [EvaluationController::class, 'unlinkDocument'])->name('unlink-document');
    Route::post('/indicators/{indicator}/upload-document', [EvaluationController::class, 'uploadDocument'])->name('upload-document');
});
