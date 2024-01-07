<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegisterRequest;
use App\Mail\VerificationCodeMail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Nette\Utils\Random;
use PragmaRX\Google2FA\Google2FA as Google2FAGoogle2FA;
use PragmaRX\Google2FALaravel\Support\Authenticator;
use PragmaRX\Google2FAQRCode\Google2FA;

class AuthController extends Controller
{
    /**
     * Register api
     *
     * @return \Illuminate\Http\Response
     */
    public function register(RegisterRequest $request)
    {
        $input = $request->validated();
        $input['password'] = Hash::make($input['password']);
        $user = User::create($input);
        $success['token'] = $user->createToken('MyApp')->plainTextToken;
        $success['id'] = $user->id;
        $success['name'] = $user->name;
        return $this->sendResponse($success);
    }

    public function enableTwoFactorAuthentication(Request $request)
    {
        $user = $request->user();
        $code = Random::generate(6);

        // Update user's secret key and enable 2FA
        $user->verification_code = $code;
        $user->is_two_factor_enabled = true;
        $user->code_expired_at = now()->addMinutes(5);
        $user->save();

        // Send the email
        $email = $user->email; // Replace with the user's email address
        Mail::to($email)->send(new VerificationCodeMail($code));
        return $this->sendResponse([], 'Two-factor authentication enabled.');
    }

    public function verifyTwoFactorAuthentication(Request $request)
    {
        $user = $request->user();
        $code = $request->input('code');

        if (now() < $user->code_expired_at && $user->verification_code == $code) {

            $user->update(['two_factor_verified' => true]);
            return $this->sendResponse([], 'Two-factor authentication verified successfully.');

        }
        return $this->sendError([], 'Invalid two-factor authentication code. Please try again.', 200);

    }

    /**
     * Login api
     *
     * @return \Illuminate\Http\Response
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => ['required', 'exists:users,email', 'email'],
            'password' => ['required'],
        ]);
        if (Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
            $user = Auth::user();
            $success['token'] = $user->createToken('MyApp')->plainTextToken;
            $success['name'] = $user->name;
            $success['id'] = $user->id;
            $success['is_admin'] = $user->is_admin;
            return $this->sendResponse($success);

        } else {
            return $this->sendError([], 'Unauthorised.', 403);

        }
    }

    public function users() {
        $users = User::query()->where('is_admin', false)->get();
        return $this->sendResponse($users);
    }
}
