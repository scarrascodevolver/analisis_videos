<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     */
    protected $rootView = 'inertia';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     */
    public function share(Request $request): array
    {
        $user         = $request->user();
        $organization = $user?->currentOrganization();

        // Orgs disponibles para el switcher (misma lógica que app.blade.php)
        $userOrganizations = null;
        if ($user) {
            if ($user->isSuperAdmin()) {
                $userOrganizations = \App\Models\Organization::where('is_active', true)
                    ->orderBy('name')
                    ->get(['id', 'name', 'type', 'logo_path']);
            } elseif ($user->isOrgManager()) {
                $userOrganizations = \App\Models\Organization::where('is_active', true)
                    ->where('created_by', $user->id)
                    ->orderBy('name')
                    ->get(['id', 'name', 'type', 'logo_path']);
            } else {
                $userOrganizations = $user->organizations()
                    ->where('is_active', true)
                    ->orderBy('name')
                    ->get(['organizations.id', 'organizations.name', 'organizations.type', 'organizations.logo_path']);
            }
        }

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user ? [
                    'id'             => $user->id,
                    'name'           => $user->name,
                    'email'          => $user->email,
                    'role'           => $user->role,
                    'is_super_admin' => $user->isSuperAdmin(),
                    'is_org_manager' => $user->isOrgManager(),
                    'avatar'         => $user->profile?->avatar
                        ? asset('storage/' . $user->profile->avatar)
                        : null,
                ] : null,
            ],
            'user_organizations' => $userOrganizations?->map(fn ($o) => [
                'id'         => $o->id,
                'name'       => $o->name,
                'type'       => $o->type,
                'is_current' => $organization?->id === $o->id,
            ]),
            'organization' => $organization ? [
                'id' => $organization->id,
                'name' => $organization->name,
                'slug' => $organization->slug,
                'type' => $organization->type,
                'logo_path' => $organization->logo_path
                    ? asset('storage/' . $organization->logo_path)
                    : null,
            ] : null,
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'warning' => fn () => $request->session()->get('warning'),
                'info' => fn () => $request->session()->get('info'),
            ],
            'notifications' => $user ? [
                'unread' => $user->unreadNotifications->take(5)->map(fn ($n) => [
                    'id' => $n->id,
                    'type' => $n->type,
                    'data' => $n->data,
                    'created_at' => $n->created_at->diffForHumans(),
                ]),
                'unread_count' => $user->unreadNotifications->count(),
            ] : null,
        ];
    }
}
