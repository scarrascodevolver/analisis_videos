<?php

namespace App\Console\Commands;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Console\Command;

class SetUserOrganization extends Command
{
    protected $signature = 'org:set-current
                            {email : User email}
                            {organization_id : Target organization ID}';

    protected $description = 'Set current organization context for a user (and persists default if missing)';

    public function handle(): int
    {
        $email = (string) $this->argument('email');
        $organizationId = (int) $this->argument('organization_id');

        $user = User::where('email', $email)->first();
        if (! $user) {
            $this->error("User not found: {$email}");

            return self::FAILURE;
        }

        $organization = Organization::find($organizationId);
        if (! $organization) {
            $this->error("Organization not found: {$organizationId}");

            return self::FAILURE;
        }

        $ok = $user->switchOrganization(
            $organization,
            $user->isSuperAdmin() || $user->isOrgManager(),
            ['switch_reason' => 'support_cli']
        );

        if (! $ok) {
            $this->error("User {$email} has no access to organization {$organizationId}.");

            return self::FAILURE;
        }

        $this->info("Current organization set for {$email}: {$organization->id} - {$organization->name}");

        return self::SUCCESS;
    }
}

