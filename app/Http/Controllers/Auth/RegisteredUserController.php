<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\Rules;
use App\Traits\Token;

class RegisteredUserController extends Controller {
  use Token;

  public function create() {
    return view('auth.register');
  }

  public function store(Request $request) {

    //return config('services.api.client_secret');

    $request->validate([
      'name' => ['required', 'string', 'max:255'],
      'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
      'password' => ['required', 'confirmed', Rules\Password::defaults()],
    ]);

    $response = Http::withHeaders([
      'Accept' => 'application/json'
    ])->post('http://lv-cf-api.test/v1/register', $request->all());

    if ($response->status() == 422) {
      return back()->withErrors($response->json()['errors']);
    }

    $service = $response->json();

    $user = User::create([
      'name' => $request->name,
      'email' => $request->email,
      // 'password' => Hash::make($request->password),
    ]);

    $this->getAccessToken($user, $service);

    event(new Registered($user));

    Auth::login($user);

    return redirect(RouteServiceProvider::HOME);
  }
}