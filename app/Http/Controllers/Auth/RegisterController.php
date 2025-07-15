<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

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
    protected $redirectTo = '/home';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest');
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array  $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['required', 'in:jugador,entrenador,analista,staff'],
            'phone' => ['nullable', 'string', 'max:20'],
        ];

        // Additional validation for player fields
        if (isset($data['role']) && $data['role'] === 'jugador') {
            $rules = array_merge($rules, [
                'position' => ['nullable', 'string'],
                'player_number' => ['nullable', 'integer', 'min:1', 'max:99'],
                'experience_level' => ['nullable', 'in:principiante,intermedio,avanzado,experto'],
                'weight' => ['nullable', 'integer', 'min:40', 'max:200'],
                'height' => ['nullable', 'integer', 'min:150', 'max:220'],
                'date_of_birth' => ['nullable', 'date'],
            ]);
        }

        // Additional validation for coach fields
        if (isset($data['role']) && $data['role'] === 'entrenador') {
            $rules = array_merge($rules, [
                'coaching_experience' => ['nullable', 'integer', 'min:0', 'max:50'],
                'certifications' => ['nullable', 'string'],
                'specializations' => ['nullable', 'array'],
            ]);
        }

        return Validator::make($data, $rules);
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param  array  $data
     * @return \App\Models\User
     */
    protected function create(array $data)
    {
        // Create the user
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => $data['role'],
            'phone' => $data['phone'] ?? null,
        ]);

        // Create profile if player or coach
        if (in_array($data['role'], ['jugador', 'entrenador'])) {
            $profileData = [
                'user_id' => $user->id,
                'goals' => $data['goals'] ?? null,
            ];

            if ($data['role'] === 'jugador') {
                $profileData = array_merge($profileData, [
                    'position' => $data['position'] ?? null,
                    'player_number' => $data['player_number'] ?? null,
                    'experience_level' => $data['experience_level'] ?? null,
                    'weight' => $data['weight'] ?? null,
                    'height' => $data['height'] ?? null,
                    'date_of_birth' => $data['date_of_birth'] ?? null,
                ]);
            } elseif ($data['role'] === 'entrenador') {
                $profileData = array_merge($profileData, [
                    'coaching_experience' => $data['coaching_experience'] ?? null,
                    'certifications' => $data['certifications'] ?? null,
                    'specializations' => isset($data['specializations']) ? json_encode($data['specializations']) : null,
                ]);
            }

            UserProfile::create($profileData);
        }

        return $user;
    }
}
