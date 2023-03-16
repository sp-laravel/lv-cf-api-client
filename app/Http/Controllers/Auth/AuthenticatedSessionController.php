<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use App\Traits\Token;

class AuthenticatedSessionController extends Controller {
  use Token;

  public function create() {
    return view('auth.login');
  }

  public function store(Request $request) {
    // $request->authenticate();
    // $request->session()->regenerate();
    // return redirect()->intended(RouteServiceProvider::HOME);

    $request->validate([
      'email' => 'required|string|email',
      'password' => 'required|string',
    ]);

    $response = Http::withHeaders([
      'Accept' => 'application/json'
    ])->post('http://lv-cf-api.test/v1/login', [
      'email' => $request->email,
      'password' => $request->password
    ]);

    if ($response->status() == 404) {
      return back()->withErrors('these credentials do not match our records.');
    }

    $service = $response->json();

    $user = User::updateOrcreate([
      'email' => $request->email
    ], $service['data']);
    // return $user;

    //if (!$user->accessToken->count()) {
    if (!$user->accessToken) {
      $this->getAccessToken($user, $service);
    }

    Auth::login($user, $request->remember);
    return redirect()->intended(RouteServiceProvider::HOME);
    // dd($access_token);
  }

  public function destroy(Request $request) {
    Auth::guard('web')->logout();

    $request->session()->invalidate();

    $request->session()->regenerateToken();

    return redirect('/');
  }
}