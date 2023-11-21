<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegisterRequest;
use App\Mail\VerificationCodeMail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
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
        $authenticator = app(Authenticator::class)->boot($user);

        // Generate secret key
        $secretKey = $authenticator->generateSecretKey();

        // Update user's secret key and enable 2FA
        $user->google2fa_secret = $secretKey;
        $user->is_two_factor_enabled = true;
        $user->save();

        // Send the email
        $email = 'alissarkousa@gmail.com'; // Replace with the user's email address
        Mail::to($email)->send(new VerificationCodeMail($secretKey));

        return response()->json([
            'message' => 'Two-factor authentication enabled.',
            // 'secret_key' => $secretKey,
        ]);
    }

    public function verifyTwoFactorAuthentication(Request $request)
{
     $user = $request->user();
    $code = $request->input('2fa_code');

    // Verify the 2FA code
    $google2fa = new Google2FAGoogle2FA();
    $valid = $google2fa->verifyKey($user->google2fa_secret, $code);

    if ($valid) {
        // 2FA code is valid
        // Perform the necessary actions for successful verification
        // For example, you can update the user's authentication status or redirect them to the desired page
        $user->update(['two_factor_verified' => true]);

        return 'Two-factor authentication verified successfully.';
    } else {
        // 2FA code is invalid
        // Handle the case when the code is not valid
        // For example, you can redirect the user back to the verification page with an error message
        return 'Invalid two-factor authentication code. Please try again.';
    }
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
