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
        $success['token'] =  $user->createToken('MyApp')->plainTextToken;
        $success['name'] =  $user->name;

        return $success;
    }

    public function enableTwoFactorAuthentication(Request $request)
    {
        $user = $request->user();
        // $authenticator = app(Authenticator::class)->boot($user);

        // Generate secret key
        // $secretKey = $authenticator->generateSecretKey();
        $code = Random::generate(6);

        // Update user's secret key and enable 2FA
        $user->verification_code = $code;
        $user->is_two_factor_enabled = true;
        $user->code_expired_at = now()->addMinutes(5);
        $user->save();

        // Send the email
        $email = $user->email; // Replace with the user's email address
        Mail::to($email)->send(new VerificationCodeMail($code));

        return response()->json([
            'message' => 'Two-factor authentication enabled.'
        ]);
    }

    public function verifyTwoFactorAuthentication(Request $request)
    {
        $user = $request->user();
        $code = $request->input('code');

        if (now() >= $user->code_expired_at && $user->verification_code == $code) {

            $user->update(['two_factor_verified' => true]);

            return 'Two-factor authentication verified successfully.';
        }

        return 'Invalid two-factor authentication code. Please try again.';
    }

    /**
     * Login api
     *
     * @return \Illuminate\Http\Response
     */
    public function login(Request $request)
    {
        if (Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
            $user = Auth::user();
            $success['token'] =  $user->createToken('MyApp')->plainTextToken;
            $success['name'] =  $user->name;

            return  $success;
        } else {
            return 'Unauthorised.';
        }
    }
}
