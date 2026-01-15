<?php

namespace App\Console\Commands;

use App\Models\Organization;
use App\Models\Team;
use App\Models\Video;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ManageTeamsCommand extends Command
{
    protected $signature = 'teams:manage
                            {--org= : Filter by organization ID or slug}
                            {--delete : Delete teams without videos}
                            {--force : Skip confirmation when deleting}';

    protected $description = 'List and manage teams. Shows which teams have videos and allows cleanup of unused teams.';

    public function handle()
    {
        $this->info('');
        $this->info('========================================');
        $this->info('   TEAM MANAGEMENT TOOL');
        $this->info('========================================');
        $this->info('');

        // Get all teams without organization scope
        $teamsQuery = Team::withoutGlobalScope('organization');

        // Filter by organization if specified
        $orgFilter = $this->option('org');
        $organization = null;

        if ($orgFilter) {
            $organization = is_numeric($orgFilter)
                ? Organization::find($orgFilter)
                : Organization::where('slug', $orgFilter)->first();

            if (!$organization) {
                $this->error("Organization not found: {$orgFilter}");
                return 1;
            }

            $teamsQuery->where('organization_id', $organization->id);
            $this->info("Filtering by organization: {$organization->name}");
            $this->info('');
        }

        $teams = $teamsQuery->orderBy('organization_id')->orderBy('name')->get();

        if ($teams->isEmpty()) {
            $this->warn('No teams found.');
            return 0;
        }

        // Get video counts for each team (without org scope)
        $analyzedCounts = Video::withoutGlobalScope('organization')
            ->select('analyzed_team_id', DB::raw('count(*) as count'))
            ->whereNotNull('analyzed_team_id')
            ->groupBy('analyzed_team_id')
            ->pluck('count', 'analyzed_team_id');

        $rivalCounts = Video::withoutGlobalScope('organization')
            ->select('rival_team_id', DB::raw('count(*) as count'))
            ->whereNotNull('rival_team_id')
            ->groupBy('rival_team_id')
            ->pluck('count', 'rival_team_id');

        // Build table data
        $tableData = [];
        $teamsWithoutVideos = collect();

        foreach ($teams as $team) {
            $org = $team->organization_id
                ? Organization::find($team->organization_id)?->name ?? 'Unknown'
                : 'NULL';

            $analyzedCount = $analyzedCounts[$team->id] ?? 0;
            $rivalCount = $rivalCounts[$team->id] ?? 0;
            $totalVideos = $analyzedCount + $rivalCount;

            $tableData[] = [
                'ID' => $team->id,
                'Organization' => substr($org, 0, 20),
                'Own' => $team->is_own_team ? 'Yes' : 'No',
                'Name' => substr($team->name, 0, 25),
                'As Analyzed' => $analyzedCount,
                'As Rival' => $rivalCount,
                'Total' => $totalVideos,
                'Status' => $totalVideos > 0 ? 'In Use' : 'UNUSED',
            ];

            if ($totalVideos === 0) {
                $teamsWithoutVideos->push($team);
            }
        }

        // Display table
        $this->table(
            ['ID', 'Organization', 'Own', 'Name', 'As Analyzed', 'As Rival', 'Total', 'Status'],
            $tableData
        );

        // Summary
        $this->info('');
        $this->info('Summary:');
        $this->info("  Total teams: {$teams->count()}");
        $this->info("  Teams with videos: " . ($teams->count() - $teamsWithoutVideos->count()));
        $this->warn("  Teams WITHOUT videos (can be deleted): {$teamsWithoutVideos->count()}");
        $this->info('');

        // Delete option
        if ($this->option('delete') && $teamsWithoutVideos->isNotEmpty()) {
            $this->warn('Teams that will be DELETED:');
            foreach ($teamsWithoutVideos as $team) {
                $this->line("  - [{$team->id}] {$team->name}");
            }
            $this->info('');

            if (!$this->option('force')) {
                if (!$this->confirm('Are you sure you want to delete these teams?')) {
                    $this->info('Operation cancelled.');
                    return 0;
                }
            }

            // Delete teams
            $deleted = 0;
            foreach ($teamsWithoutVideos as $team) {
                $team->delete();
                $deleted++;
                $this->info("  Deleted: {$team->name}");
            }

            $this->info('');
            $this->info("Successfully deleted {$deleted} teams.");
        } elseif ($teamsWithoutVideos->isNotEmpty() && !$this->option('delete')) {
            $this->info('To delete unused teams, run with --delete option:');
            $this->line('  php artisan teams:manage --delete');
            if ($organization) {
                $this->line("  php artisan teams:manage --org={$organization->slug} --delete");
            }
        }

        return 0;
    }
}
