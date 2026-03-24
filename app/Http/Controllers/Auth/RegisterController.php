<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\TenantService;
use App\Services\UserService;
use App\Validator\RegisterValidator;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class RegisterController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Register Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users as well as their
    | validation and creation. By default this controller uses a trait to
    | provide this functionality without requiring any additional code.
    |
    */

    use RegistersUsers;

    /**
     * Where to redirect users after registration.
     *
     * @var string
     */
    //    protected $redirectTo = '/email/verify';

    public function __construct(
        protected RegisterValidator $registerValidator,
        protected UserService $userService,
        protected TenantService $tenantService,
    ) {
        $this->middleware('guest');
    }

    public function redirectPath()
    {
        $user = auth()->user();

        if ($user && $this->tenantService->getUserInvitationCount($user) > 0) {
            return route('invitations');
        }

        return Redirect::getIntendedUrl() ?? route('home');
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @return Validator
     */
    protected function validator(array $data)
    {
        return $this->registerValidator->validate($data);
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @return User
     */
    protected function create(array $data)
    {
        return $this->userService->createUser($data);
    }

    /**
     * Show the application registration form.
     *
     * @return View
     */
    public function showRegistrationForm()
    {
        if (url()->previous() != route('login') && Redirect::getIntendedUrl() === null) {
            Redirect::setIntendedUrl(url()->previous()); // make sure we redirect back to the page we came from
        }

        return view('auth.register', [
            'isOtpLoginEnabled' => config('app.otp_login_enabled'),
        ]);
    }
}
