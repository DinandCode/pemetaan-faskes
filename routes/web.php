<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FaskesCrudController;
use App\Http\Controllers\ImportExportController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    $activeFieldDefinitions = \App\Models\FaskesFieldDefinition::where('is_active', true)->orderBy('sort_order')->get();
    return view('peta', compact('activeFieldDefinitions'));
});

Route::get('/peta', function () {
    $activeFieldDefinitions = \App\Models\FaskesFieldDefinition::where('is_active', true)->orderBy('sort_order')->get();
    return view('peta', compact('activeFieldDefinitions'));
});

// Halaman Dashboard Analitik & Statistik
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

// Import & Export Faskes (ditempatkan sebelum Route::resource agar tidak tertimpa faskes/{faske})
Route::get('faskes/import/template', [ImportExportController::class, 'downloadTemplate'])->name('faskes.import.template');
Route::post('faskes/import/check', [ImportExportController::class, 'checkImport'])->name('faskes.import.check');
Route::post('faskes/import', [ImportExportController::class, 'importFaskes'])->name('faskes.import');
Route::get('faskes/export/excel', [ImportExportController::class, 'exportFaskesExcel'])->name('faskes.export.excel');
Route::get('faskes/export/pdf', [ImportExportController::class, 'exportFaskesPdf'])->name('faskes.export.pdf');

// Export Analisis Event (Excel & PDF)
Route::match(['get', 'post'], 'analisis/export/excel', [ImportExportController::class, 'exportAnalisisExcel'])->name('analisis.export.excel');
Route::match(['get', 'post'], 'analisis/export/pdf', [ImportExportController::class, 'exportAnalisisPdf'])->name('analisis.export.pdf');

use App\Http\Controllers\FaskesFieldController;

// Kelola Definisi Kolom Tambahan (Custom Fields)
Route::prefix('faskes-fields')->name('faskes-fields.')->group(function () {
    Route::get('/', [FaskesFieldController::class, 'index'])->name('index');
    Route::post('/', [FaskesFieldController::class, 'store'])->name('store');
    Route::put('/{id}', [FaskesFieldController::class, 'update'])->name('update');
    Route::delete('/{id}', [FaskesFieldController::class, 'destroy'])->name('destroy');
});

// Bulk Destroy Faskes (ditempatkan sebelum Route::resource)
Route::delete('faskes/bulk-destroy', [FaskesCrudController::class, 'bulkDestroy'])->name('faskes.bulk-destroy');

// Resource CRUD Master Data Faskes
Route::resource('faskes', FaskesCrudController::class);


