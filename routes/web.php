<?php

use App\Http\Controllers\DiaryController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/diaries');
Route::resource('diaries', DiaryController::class)->except('show');
