<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;

class LoginController extends Controller
{
    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/'; // O la ruta que quieras después del login

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    /**
     * Get the throttle key for the login attempt.
     */
    protected function throttleKey(Request $request)
    {
        return strtolower($request->input('email')) . '|' . $request->ip();
    }

    /**
     * Get the maximum number of attempts to allow.
     */
    public function maxAttempts()
    {
        return 5;
    }

    /**
     * Get the number of minutes to throttle for.
     */
    public function decayMinutes()
    {
        return 1;
    }

    /**
     * Show the application's login form.
     *
     * @return \Illuminate\View\View
     */
    public function showLoginForm()
    {
        return view('auth.login');
    }

    /**
     * Get the needed authorization credentials from the request.
     */
    protected function credentials(Request $request)
    {
        $credentials = $request->only('email', 'password');
        $credentials['estado'] = 1;
        return $credentials;
    }

    /**
     * Get the failed login response instance.
     */
    protected function sendFailedLoginResponse(Request $request)
    {
        $errors = [$this->username() => trans('auth.failed')];

        if ($request->expectsJson()) {
            return response()->json($errors, 422);
        }

        return redirect()->back()
            ->withInput($request->only($this->username(), 'remember'))
            ->withErrors([
                'email' => 'Credenciales incorrectas. Verifica tu correo y contraseña.',
                'login_error' => true
            ]);
    }

    /**
     * The user has been authenticated.
     */
    protected function authenticated(Request $request, $user)
    {
        // Registrar último acceso
        $user->ultimo_acceso = now();
        $user->ultimo_ip = $request->ip();
        $user->save();
    }
}