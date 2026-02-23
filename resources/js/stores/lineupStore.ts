import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import axios from 'axios';
import type { Lineup, LineupPlayer, RivalPlayer } from '@/types/video-player';

export const useLineupStore = defineStore('lineup', () => {
    // ── State ─────────────────────────────────────────────────────────────────
    const lineups = ref<Lineup[]>([]);
    const isLoading = ref(false);

    // ── Computed ──────────────────────────────────────────────────────────────
    const localLineup = computed(() =>
        lineups.value.find(l => l.team_type === 'local') ?? null
    );

    const rivalLineup = computed(() =>
        lineups.value.find(l => l.team_type === 'rival') ?? null
    );

    const totalPlayers = computed(() =>
        lineups.value.reduce((sum, l) => sum + l.players.length, 0)
    );

    // ── Actions ───────────────────────────────────────────────────────────────

    async function loadLineups(videoId: number): Promise<void> {
        isLoading.value = true;
        try {
            const res = await axios.get(`/api/videos/${videoId}/lineups`);
            lineups.value = res.data.lineups;
        } catch (e) {
            console.error('Error loading lineups:', e);
        } finally {
            isLoading.value = false;
        }
    }

    /**
     * Returns the existing lineup for the given team_type, or creates a new one
     * via the API and caches it locally.
     */
    async function ensureLineup(videoId: number, teamType: 'local' | 'rival'): Promise<Lineup> {
        const existing = lineups.value.find(l => l.team_type === teamType);
        if (existing) return existing;

        const res = await axios.post(`/api/videos/${videoId}/lineups`, {
            team_type: teamType,
        });
        const lineup = res.data.lineup as Lineup;
        lineups.value.push(lineup);
        return lineup;
    }

    /**
     * Add a player to a lineup. If a player with the same shirt_number already
     * exists in local state it is replaced (mirrors the backend behaviour).
     */
    async function addPlayer(lineupId: number, data: Partial<LineupPlayer>): Promise<LineupPlayer> {
        const res = await axios.post(`/api/lineups/${lineupId}/players`, data);
        const player = res.data.player as LineupPlayer;

        const lineup = lineups.value.find(l => l.id === lineupId);
        if (lineup) {
            // Replace slot if same shirt_number, otherwise append
            const idx = lineup.players.findIndex(
                p => p.shirt_number !== null && p.shirt_number === player.shirt_number
            );
            if (idx >= 0) {
                lineup.players.splice(idx, 1, player);
            } else {
                lineup.players.push(player);
            }
        }

        return player;
    }

    async function updatePlayer(playerId: number, data: Partial<LineupPlayer>): Promise<void> {
        const res = await axios.put(`/api/lineup-players/${playerId}`, data);
        const updated = res.data.player as LineupPlayer;

        for (const lineup of lineups.value) {
            const idx = lineup.players.findIndex(p => p.id === playerId);
            if (idx >= 0) {
                lineup.players.splice(idx, 1, updated);
                break;
            }
        }
    }

    async function removePlayer(playerId: number): Promise<void> {
        await axios.delete(`/api/lineup-players/${playerId}`);

        for (const lineup of lineups.value) {
            const idx = lineup.players.findIndex(p => p.id === playerId);
            if (idx >= 0) {
                lineup.players.splice(idx, 1);
                break;
            }
        }
    }

    /** Update formation/notes metadata without touching players. */
    function updateLineupMeta(lineupId: number, data: { formation?: string; notes?: string }): void {
        const lineup = lineups.value.find(l => l.id === lineupId);
        if (lineup) Object.assign(lineup, data);
    }

    async function fetchRivalPlayers(rivalTeamId: number): Promise<RivalPlayer[]> {
        const res = await axios.get(`/api/rival-teams/${rivalTeamId}/players`);
        return res.data.players;
    }

    async function createRivalPlayer(
        rivalTeamId: number,
        data: Partial<RivalPlayer>
    ): Promise<RivalPlayer> {
        const res = await axios.post(`/api/rival-teams/${rivalTeamId}/players`, data);
        return res.data.player;
    }

    function reset(): void {
        lineups.value = [];
        isLoading.value = false;
    }

    return {
        // State
        lineups,
        isLoading,
        // Computed
        localLineup,
        rivalLineup,
        totalPlayers,
        // Actions
        loadLineups,
        ensureLineup,
        addPlayer,
        updatePlayer,
        removePlayer,
        updateLineupMeta,
        fetchRivalPlayers,
        createRivalPlayer,
        reset,
    };
});
