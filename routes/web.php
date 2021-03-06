<?php

use App\Mail\UserVerified;
use App\Models\UserVerificationToken;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/user/verify/{token}', function(string $token) {
    $verificationToken = UserVerificationToken::where('token', $token)->first();
    if (is_null($verificationToken)) { // If the token is not found.
        abort(404);
    }

    // Verify user exists
    $user = $verificationToken->user;
    if ($user->status != 'pending') { // If no valid user is not found.
        return redirect(getenv('FRONTEND_URL') . '/login.html');
    }

    // Mark user as active
    $user->status = 'active';
    $user->save();

    // Send activation email.
    Mail::to($user)->send(new UserVerified($user));

    // Redirect users to their dashboard on the frontend.
    return redirect(getenv('FRONTEND_URL') . '/login.html');
})->name('user.verify');

Route::get('/admin/refresh-app', function() {
    if (env('APP_ENV') != 'local') {
        return 'App is not in local mode.';
    }

    Artisan::call('app:refresh');
    return 'App refreshed!';
});
