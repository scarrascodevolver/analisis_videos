<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Organization;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Http\Request;
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
    public function showRegistrationForm(Request $request)
    {
        $organization = null;
        $categories = collect();
        $invitationCode = $request->query('code', '');

        // Si viene código en la URL, validarlo y cargar datos
        if (! empty($invitationCode)) {
            $organization = Organization::findByInvitationCode($invitationCode);

            if ($organization) {
                // Cargar categorías de esta organización (sin global scope)
                $categories = Category::withoutGlobalScope('organization')
                    ->where('organization_id', $organization->id)
                    ->orderBy('name')
                    ->get();
            }
        }

        return view('auth.register', compact('categories', 'organization', 'invitationCode'));
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
     * Handle a registration request for the application.
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function register(Request $request)
    {
        $this->validator($request->all())->validate();

        $data = $request->all();

        // Add the avatar file to data if present
        if ($request->hasFile('avatar')) {
            $data['avatar'] = $request->file('avatar');
        }

        $user = $this->create($data);

        event(new \Illuminate\Auth\Events\Registered($user));

        $this->guard()->login($user);

        return $this->registered($request, $user)
                        ?: redirect($this->redirectPath());
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['required', 'in:jugador'],
            'country_code' => ['nullable', 'string', 'max:5'],
            'phone' => ['nullable', 'string', 'max:20'],
            'avatar' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
            'invitation_code' => ['required', 'string', function ($attribute, $value, $fail) {
                $org = Organization::findByInvitationCode($value);
                if (! $org) {
                    $fail('El código de invitación no es válido o la organización no está activa.');
                }
            }],
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
     * @return \App\Models\User
     */
    protected function create(array $data)
    {
        // Obtener la organización del código de invitación
        $organization = Organization::findByInvitationCode($data['invitation_code']);

        // Combinar código de país con teléfono
        $phone = null;
        if (! empty($data['phone'])) {
            $countryCode = $data['country_code'] ?? '+56';
            $phone = $countryCode.$data['phone'];
        }

        // Create the user
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => $data['role'],
            'phone' => $phone,
        ]);

        // Asignar usuario a la organización
        if ($organization) {
            $organization->users()->attach($user->id, [
                'role' => $data['role'],
                'is_current' => true,
                'is_org_admin' => false,
            ]);
        }

        // Create profile if player or coach
        if (in_array($data['role'], ['jugador', 'entrenador'])) {
            $profileData = [
                'user_id' => $user->id,
                'goals' => $data['goals'] ?? null,
            ];

            // Handle avatar upload if present
            if (isset($data['avatar']) && $data['avatar'] instanceof \Illuminate\Http\UploadedFile) {
                $avatarPath = $data['avatar']->store('avatars', 'public');
                $profileData['avatar'] = $avatarPath;
            }

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
