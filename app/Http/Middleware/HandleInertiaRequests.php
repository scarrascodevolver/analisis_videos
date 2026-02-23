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
        $user = $request->user();
        $organization = $user?->currentOrganization();

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'is_super_admin' => $user->isSuperAdmin(),
                    'avatar' => $user->profile?->avatar
                        ? asset('storage/' . $user->profile->avatar)
                        : null,
                ] : null,
            ],
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
