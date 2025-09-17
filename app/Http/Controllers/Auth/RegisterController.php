<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\Category;
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
     * Show the application registration form.
     *
     * @return \Illuminate\View\View
     */
    public function showRegistrationForm()
    {
        $categories = Category::all();
        return view('auth.register', compact('categories'));
    }

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
            'role' => ['required', 'in:jugador'],
            'phone' => ['nullable', 'string', 'max:20'],
        ];

        // Player fields validation (always required since only players can register)
        $rules = array_merge($rules, [
            'position' => ['nullable', 'string'],
            'secondary_position' => ['nullable', 'string'],
            'player_number' => ['nullable', 'integer', 'min:1', 'max:99'],
            'weight' => ['nullable', 'integer', 'min:40', 'max:200'],
            'height' => ['nullable', 'integer', 'min:150', 'max:220'],
            'date_of_birth' => ['nullable', 'date'],
            'user_category_id' => ['required', 'exists:categories,id'],
        ]);

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

            // Add player fields (always since only players can register)
            $profileData = array_merge($profileData, [
                'position' => $data['position'] ?? null,
                'secondary_position' => $data['secondary_position'] ?? null,
                'player_number' => $data['player_number'] ?? null,
                'weight' => $data['weight'] ?? null,
                'height' => $data['height'] ?? null,
                'date_of_birth' => $data['date_of_birth'] ?? null,
                'user_category_id' => $data['user_category_id'] ?? null,
            ]);

            UserProfile::create($profileData);
        }

        return $user;
    }
}
