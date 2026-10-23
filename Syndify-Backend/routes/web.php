<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Route Dashboard existante
Route::get('/dashboard', function () {
    return view('dashboard');
})->name('dashboard');

// 🟢 NOUVELLES ROUTES POUR LE MENU
// Route::middleware('auth')->group(function () {
    Route::get('/coproprietes', function () { return view('coproprietes.index'); })->name('coproprietes.index');
    Route::get('/assemblees', function () { return view('assemblees.index'); })->name('assemblees.index');
    Route::get('/documents', function () { return view('documents.index'); })->name('documents.index');
    Route::get('/depenses', function () { return view('depenses.index'); })->name('depenses.index');
    Route::get('/annonces', function () { return view('annonces.index'); })->name('annonces.index');
    
    // Paiements = cotisations
    Route::get('/paiements', function () { return view('cotisations.index'); })->name('cotisations.index'); 
    
    // Charges = appels_fonds
    Route::get('/charges', function () { return view('appels_fonds.index'); })->name('appels_fonds.index'); 
    Route::get('/lots', function () { return view('lots.index'); })->name('lots.index');
    
    // Profil (Breeze)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
// });

require __DIR__.'/auth.php';