<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Helpers\LDAP;
use App\Rules\Username;

class AuthController extends Controller {
    public function login(Request $request) {
        # Validation
        $request->validate([
            'uid' => ['required', new Username],
            'password' => ['required']
        ],
        [
            'uid.required' => 'Login ist erforderlich.',
            'password.required' => 'Passwort ist erforderlich.'
        ]);

        # LDAP Authentification
        ## Production
        $ldap = new LDAP();
        $authObject = $ldap->auth($request->input('name'), $request->input('password'));
        ## Local Testing
        // $authObject = true;
        if ($authObject) {
            # Find User in Database
            $user = User::where('uid', '=', $request->input('uid'))->first();
            if ($user == null) {
                // TODO: create User in Database if not existant
                $user = User::find(1);
            }
            # Login user
            Auth::login($user);
            # return success
            return response([
                'success' => true,
                'level' => 1,
                'uid' => Auth::user()->name
            ], 200);
        }

        # return error
        return response([
            'errors' => [
                'Anmeldedaten' => ['Login und/oder Passwort sind nicht korrekt.']
            ]
        ], 401);
    }

    public function logout(Request $request) {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response('sucess', 200);
    }

    public function check() {
        if (Auth::check()) {
            return response([
                'success' => true,
                'level' => 1,
                'uid' => Auth::user()->name
            ], 200);
        }
        return response([
            'success' => false,
            'level' => 0,
            'uid' => ''
        ], 200);
    }

    public function test() {
        return response('Test', 200);
    }
}
