<?php

use App\Http\Controllers\Client\FindParticipantController;
use App\Http\Controllers\Client\TermsAndConditionsController;
use App\Http\Controllers\EcommerceController;
use Illuminate\Support\Facades\Route;


//Homepage
Route::get('/', [EcommerceController::class, 'index'])->name('ecommerce.index');

//Find Participant
Route::get('/participant', [FindParticipantController::class, 'findParticipant'])->name('ecommerce.find-participant');
Route::post('/participant/search', [FindParticipantController::class, 'searchParticipant'])->name('ecommerce.search-participant');
Route::get('/terminos-y-condiciones', [TermsAndConditionsController::class, 'termsAndConditions'])->name('ecommerce.terms-and-conditions');

//Program Routes
require __DIR__ . '/program.php';
require __DIR__ . '/frequent_client.php';

