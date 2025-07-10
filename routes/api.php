<?php
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\UpdateController;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/pending-update', [UpdateController::class, 'pending']);
Route::post('/approve-update', [UpdateController::class, 'approve']);
Route::get('/last-update', [UpdateController::class, 'last']);
Route::post('/export-complete-data', [UpdateController::class, 'exportCompleteData']);
Route::get('/version', [UpdateController::class, 'version']);
// Auth::routes();
// Route::middleware('auth:api')->get('/export-complete-data', 
//     [UpdateController::class, 'exportCompleteData']);