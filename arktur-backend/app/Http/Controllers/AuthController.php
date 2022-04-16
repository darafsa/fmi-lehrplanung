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
        $request->validate(
            [
                'uid' => ['required', new Username],
                'password' => ['required']
            ],
            [
                'uid.required' => 'Login ist erforderlich.',
                'password.required' => 'Passwort ist erforderlich.'
            ]
        );

        $ldap = new LDAP();
        $ldap->connect();
        $authObject = $ldap->auth($request->uid, $request->password);

        if ($request->input('uid') == "guest01" || $request->input('uid') == "guest02") { # Guest user //TODO: create conatiner for guest
            $user = User::where('uid', $request->input('uid'))->first();

            Auth::login($user);
            # return success
            return response([
                'success' => true,
                'level' => Auth::user()->level,
                'uid' => Auth::user()->uid,
            ], 200);
        } else if ($authObject) {
            # Find User in Database
            $user = User::where('uid', '=', $request->input('uid'))->first();
            $data = $ldap->directory_entry($request->uid);
            $ldap->save_data($request->uid);
            $ldap->close();

            $roles = $data["edupersonaffiliation"];
            if (!is_array($roles)) {
                $roles = [$roles];
            }

            if (in_array("Mitarbeiter", $roles)) {  # User is allowed to login
                if ($user == null) {
                    User::create([
                        'uid' => $data["uid"],
                        'email' => $data["mail"],
                        'forename' => $data['fsufirstname'],
                        'surname' => $data['fsucompletesurname'],
                        'salutation' => $data['thuedusalutation'],
                        'displayname' => $data['displayname']
                    ]);
                    $user = User::where('uid', '=', $request->input('uid'))->first();
                }

                # Login user
                Auth::login($user);
                # return success
                return response([
                    'success' => true,
                    'level' => Auth::user()->level,
                    'uid' => Auth::user()->uid,
                ], 200);
            } else if (in_array("Student", $roles)) {    # User is Student
                if ($user == null) {
                    User::create([
                        'uid' => $data["uid"],
                        'email' => $data["mail"],
                        'forename' => $data['fsufirstname'],
                        'surname' => $data['fsucompletesurname'],
                        'salutation' => $data['thuedusalutation'],
                        'displayname' => $data['displayname'],
                        'level' => 0
                    ]);
                    $user = User::where('uid', '=', $request->input('uid'))->first();

                    # Login user
                    Auth::login($user);
                    # return success
                    return response([
                        'success' => true,
                        'level' => Auth::user()->level,
                        'uid' => Auth::user()->uid,
                    ], 200);
                }
            } else {    # User is not allowed to login
                return response([
                    'success' => false,
                    'errors' => [
                        'Berechtigung' => ['Sie besitzen nicht über ausreichende Rechte um auf diese Resource zuzugreifen. Bitte Kontaktieren Sie den Seiten-Admin.']
                    ]
                ], 401);
            }
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
                'level' => Auth::user()->level,
                'uid' => Auth::user()->uid
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
