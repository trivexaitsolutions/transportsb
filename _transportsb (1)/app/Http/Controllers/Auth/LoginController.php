<?php
namespace App\Http\Controllers\Auth;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;
class LoginController extends Controller
{
    public function show(): View { return view('auth.login'); }
    public function login(Request $request): RedirectResponse
    {
        $credentials=$request->validate(['email'=>['required','email'],'password'=>['required','string']]);
        $email=strtolower(trim($credentials['email'])); $key='transport-v2-login:'.$email.'|'.$request->ip();
        if (RateLimiter::tooManyAttempts($key,5)) return back()->withErrors(['email'=>'Too many attempts. Try again in '.RateLimiter::availableIn($key).' seconds.'])->onlyInput('email');
        if (!Auth::attempt(['email'=>$email,'password'=>$credentials['password'],'is_active'=>true],$request->boolean('remember'))) { RateLimiter::hit($key,60); return back()->withErrors(['email'=>'Email or password is incorrect.'])->onlyInput('email'); }
        RateLimiter::clear($key); $request->session()->regenerate(); return redirect()->route('sale.orders.index');
    }
    public function logout(Request $request): RedirectResponse { Auth::logout(); $request->session()->invalidate(); $request->session()->regenerateToken(); return redirect()->route('login'); }
}
