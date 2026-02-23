<template>
    <Teleport to="body">
        <transition name="fade">
            <div v-if="show" class="modal-overlay" @click.self="$emit('close')">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <!-- Header -->
                        <div class="modal-header">
                            <h5 class="modal-title">
                                <i class="fas fa-users mr-2"></i>
                                Plantel del Partido
                                <span
                                    v-if="video.analyzed_team_name || video.rival_team_name"
                                    class="match-info"
                                >
                                    — {{ video.analyzed_team_name || 'Local' }}
                                    vs {{ video.rival_team_name || 'Rival' }}
                                </span>
                            </h5>
                            <button type="button" class="close" @click="$emit('close')">
                                <span>&times;</span>
                            </button>
                        </div>

                        <!-- Tabs -->
                        <div class="lineup-tabs">
                            <button
                                class="lineup-tab"
                                :class="{ active: activeTab === 'local' }"
                                @click="activeTab = 'local'"
                            >
                                <i class="fas fa-shield-alt mr-1"></i>
                                {{ video.analyzed_team_name || 'Local' }}
                                <span class="player-count">{{ localPlayerCount }}</span>
                            </button>
                            <button
                                class="lineup-tab"
                                :class="{ active: activeTab === 'rival' }"
                                @click="activeTab = 'rival'"
                            >
                                <i class="fas fa-shield mr-1"></i>
                                {{ video.rival_team_name || video.rival_name || 'Rival' }}
                                <span class="player-count">{{ rivalPlayerCount }}</span>
                            </button>
                        </div>

                        <!-- Body -->
                        <div class="modal-body">
                            <!-- Loading state -->
                            <div v-if="lineupStore.isLoading" class="loading-state">
                                <i class="fas fa-spinner fa-spin"></i> Cargando plantel...
                            </div>

                            <!-- LOCAL TAB ───────────────────────────────────── -->
                            <div v-else-if="activeTab === 'local'">
                                <div class="section-header">
                                    <span class="section-title">Titulares (1-15)</span>
                                    <span class="hint">Seleccioná jugadores del plantel</span>
                                </div>

                                <div class="players-grid">
                                    <div
                                        v-for="pos in 15"
                                        :key="pos"
                                        class="player-slot"
                                        :class="{ filled: getLocalPlayer(pos) }"
                                    >
                                        <div class="slot-number">{{ pos }}</div>
                                        <div class="slot-position">{{ POSITIONS[pos] }}</div>

                                        <div v-if="getLocalPlayer(pos)" class="slot-player">
                                            <span class="player-name">
                                                {{ getLocalPlayer(pos)!.user?.name ?? getLocalPlayer(pos)!.player_name }}
                                            </span>
                                            <button
                                                class="btn-remove-player"
                                                title="Quitar"
                                                @click="removeLocalPlayer(pos)"
                                            >
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>

                                        <div v-else class="slot-empty">
                                            <select
                                                class="player-select"
                                                @change="onLocalPlayerSelect(pos, $event)"
                                            >
                                                <option value="">— Asignar jugador —</option>
                                                <option
                                                    v-for="u in availableLocalUsers()"
                                                    :key="u.id"
                                                    :value="u.id"
                                                >
                                                    {{ u.name }}
                                                </option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <!-- Bench -->
                                <div class="section-header mt-3">
                                    <span class="section-title">Banco (16-23)</span>
                                </div>
                                <div class="bench-grid">
                                    <div
                                        v-for="bench in 8"
                                        :key="bench + 15"
                                        class="bench-slot"
                                        :class="{ filled: getLocalBenchPlayer(bench + 15) }"
                                    >
                                        <div class="slot-number">{{ bench + 15 }}</div>

                                        <div v-if="getLocalBenchPlayer(bench + 15)" class="slot-player">
                                            <span class="player-name">
                                                {{ getLocalBenchPlayer(bench + 15)!.user?.name ?? getLocalBenchPlayer(bench + 15)!.player_name }}
                                            </span>
                                            <button
                                                class="btn-remove-player"
                                                @click="removeLocalBenchPlayer(bench + 15)"
                                            >
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>

                                        <div v-else class="slot-empty">
                                            <select
                                                class="player-select"
                                                @change="onLocalBenchSelect(bench + 15, $event)"
                                            >
                                                <option value="">— Banco —</option>
                                                <option
                                                    v-for="u in availableLocalUsers()"
                                                    :key="u.id"
                                                    :value="u.id"
                                                >
                                                    {{ u.name }}
                                                </option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- RIVAL TAB ───────────────────────────────────── -->
                            <div v-else-if="activeTab === 'rival'">
                                <!-- Known players from previous matches -->
                                <template v-if="knownRivalPlayers.length > 0">
                                    <div class="known-players-hint">
                                        <i class="fas fa-history mr-1"></i>
                                        Jugadores conocidos de este rival — hacé click para agregar
                                    </div>
                                    <div class="known-players">
                                        <button
                                            v-for="rp in knownRivalPlayers"
                                            :key="rp.id"
                                            class="known-player-chip"
                                            :class="{ added: isRivalPlayerAdded(rp.id) }"
                                            @click="addKnownRivalPlayer(rp)"
                                        >
                                            <span class="chip-number">#{{ rp.shirt_number ?? '?' }}</span>
                                            {{ rp.name }}
                                            <i v-if="isRivalPlayerAdded(rp.id)" class="fas fa-check ml-1"></i>
                                        </button>
                                    </div>
                                </template>

                                <!-- Rival starters grid -->
                                <div class="section-header" :class="{ 'mt-3': knownRivalPlayers.length > 0 }">
                                    <span class="section-title">Titulares rival (1-15)</span>
                                </div>
                                <div class="players-grid">
                                    <div
                                        v-for="pos in 15"
                                        :key="pos"
                                        class="player-slot rival"
                                        :class="{ filled: getRivalPlayer(pos) }"
                                    >
                                        <div class="slot-number">{{ pos }}</div>
                                        <div class="slot-position">{{ POSITIONS[pos] }}</div>

                                        <div v-if="getRivalPlayer(pos)" class="slot-player">
                                            <span class="player-name">{{ getRivalPlayerName(pos) }}</span>
                                            <button
                                                class="btn-remove-player"
                                                @click="removeRivalPlayer(pos)"
                                            >
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>

                                        <div v-else class="slot-empty">
                                            <input
                                                type="text"
                                                class="player-input"
                                                placeholder="Nombre del jugador"
                                                @keyup.enter="onRivalPlayerInput(pos, $event)"
                                                @blur="onRivalPlayerInput(pos, $event)"
                                            />
                                        </div>
                                    </div>
                                </div>

                                <!-- Rival bench -->
                                <div class="section-header mt-3">
                                    <span class="section-title">Banco rival (16-23)</span>
                                </div>
                                <div class="bench-grid">
                                    <div
                                        v-for="bench in 8"
                                        :key="bench + 15"
                                        class="bench-slot rival"
                                        :class="{ filled: getRivalBenchPlayer(bench + 15) }"
                                    >
                                        <div class="slot-number">{{ bench + 15 }}</div>

                                        <div v-if="getRivalBenchPlayer(bench + 15)" class="slot-player">
                                            <span class="player-name">{{ getRivalPlayerName(bench + 15) }}</span>
                                            <button
                                                class="btn-remove-player"
                                                @click="removeRivalBenchPlayer(bench + 15)"
                                            >
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>

                                        <div v-else class="slot-empty">
                                            <input
                                                type="text"
                                                class="player-input"
                                                placeholder="Nombre"
                                                @keyup.enter="onRivalBenchInput(bench + 15, $event)"
                                                @blur="onRivalBenchInput(bench + 15, $event)"
                                            />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Footer -->
                        <div class="modal-footer">
                            <span class="footer-info text-muted">
                                <i class="fas fa-save mr-1"></i>
                                Los cambios se guardan automáticamente
                            </span>
                            <button type="button" class="btn btn-secondary" @click="$emit('close')">
                                Cerrar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </transition>
    </Teleport>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { useLineupStore } from '@/stores/lineupStore';
import type { Video, User, RivalPlayer } from '@/types/video-player';

// ── Props & emits ─────────────────────────────────────────────────────────────

const props = defineProps<{
    show: boolean;
    video: Video;
    allUsers: Pick<User, 'id' | 'name' | 'role'>[];
}>();

defineEmits<{ close: [] }>();

// ── Store & local state ───────────────────────────────────────────────────────

const lineupStore = useLineupStore();
const activeTab = ref<'local' | 'rival'>('local');
const knownRivalPlayers = ref<RivalPlayer[]>([]);

// ── Position map ──────────────────────────────────────────────────────────────

const POSITIONS: Record<number, string> = {
    1: 'Pilar izq.',
    2: 'Hooker',
    3: 'Pilar der.',
    4: 'Lock izq.',
    5: 'Lock der.',
    6: 'Ala ciego',
    7: 'Ala abierto',
    8: 'Octavo',
    9: 'Medio scrum',
    10: 'Apertura',
    11: 'Ala izq.',
    12: 'Centro izq.',
    13: 'Centro der.',
    14: 'Ala der.',
    15: 'Fullback',
};

// ── Computed player counts ────────────────────────────────────────────────────

const localPlayerCount = computed(() => lineupStore.localLineup?.players.length ?? 0);
const rivalPlayerCount = computed(() => lineupStore.rivalLineup?.players.length ?? 0);

// ── Local team helpers ────────────────────────────────────────────────────────

function getLocalPlayer(positionNumber: number) {
    return lineupStore.localLineup?.players.find(
        p => p.position_number === positionNumber && p.status === 'starter'
    ) ?? null;
}

function getLocalBenchPlayer(shirtNumber: number) {
    return lineupStore.localLineup?.players.find(
        p => p.shirt_number === shirtNumber && p.status === 'substitute'
    ) ?? null;
}

/** Users not already assigned anywhere in the local lineup */
function availableLocalUsers() {
    const usedIds = new Set(
        lineupStore.localLineup?.players
            .map(p => p.user_id)
            .filter((id): id is number => id !== null) ?? []
    );
    return props.allUsers.filter(
        u => !usedIds.has(u.id) && u.role === 'jugador'
    );
}

async function onLocalPlayerSelect(pos: number, event: Event): Promise<void> {
    const userId = parseInt((event.target as HTMLSelectElement).value);
    if (!userId) return;
    // Reset the select immediately so it doesn't look "stuck"
    (event.target as HTMLSelectElement).value = '';

    const lineup = await lineupStore.ensureLineup(props.video.id, 'local');
    await lineupStore.addPlayer(lineup.id, {
        user_id: userId,
        position_number: pos,
        shirt_number: pos,
        status: 'starter',
    });
}

async function onLocalBenchSelect(shirtNumber: number, event: Event): Promise<void> {
    const userId = parseInt((event.target as HTMLSelectElement).value);
    if (!userId) return;
    (event.target as HTMLSelectElement).value = '';

    const lineup = await lineupStore.ensureLineup(props.video.id, 'local');
    await lineupStore.addPlayer(lineup.id, {
        user_id: userId,
        shirt_number: shirtNumber,
        status: 'substitute',
    });
}

async function removeLocalPlayer(pos: number): Promise<void> {
    const player = getLocalPlayer(pos);
    if (player) await lineupStore.removePlayer(player.id);
}

async function removeLocalBenchPlayer(shirtNumber: number): Promise<void> {
    const player = getLocalBenchPlayer(shirtNumber);
    if (player) await lineupStore.removePlayer(player.id);
}

// ── Rival team helpers ────────────────────────────────────────────────────────

function getRivalPlayer(positionNumber: number) {
    return lineupStore.rivalLineup?.players.find(
        p => p.position_number === positionNumber && p.status === 'starter'
    ) ?? null;
}

function getRivalBenchPlayer(shirtNumber: number) {
    return lineupStore.rivalLineup?.players.find(
        p => p.shirt_number === shirtNumber && p.status === 'substitute'
    ) ?? null;
}

function getRivalPlayerName(pos: number): string {
    const player = pos <= 15 ? getRivalPlayer(pos) : getRivalBenchPlayer(pos);
    if (!player) return '';
    return player.rival_player?.name ?? player.player_name ?? '';
}

function isRivalPlayerAdded(rivalPlayerId: number): boolean {
    return !!lineupStore.rivalLineup?.players.find(p => p.rival_player_id === rivalPlayerId);
}

async function onRivalPlayerInput(pos: number, event: Event): Promise<void> {
    const name = (event.target as HTMLInputElement).value.trim();
    if (!name) return;

    const lineup = await lineupStore.ensureLineup(props.video.id, 'rival');

    if (props.video.rival_team_id) {
        try {
            const rp = await lineupStore.createRivalPlayer(props.video.rival_team_id, {
                name,
                shirt_number: pos <= 15 ? pos : undefined,
                usual_position: pos <= 15 ? pos : undefined,
            });
            await lineupStore.addPlayer(lineup.id, {
                rival_player_id: rp.id,
                player_name: name,
                position_number: pos <= 15 ? pos : undefined,
                shirt_number: pos,
                status: 'starter',
            });
            // Add to known list so chip appears immediately
            knownRivalPlayers.value.push(rp);
        } catch {
            // Fall back to plain name if rival player creation fails
            await lineupStore.addPlayer(lineup.id, {
                player_name: name,
                position_number: pos <= 15 ? pos : undefined,
                shirt_number: pos,
                status: 'starter',
            });
        }
    } else {
        await lineupStore.addPlayer(lineup.id, {
            player_name: name,
            position_number: pos <= 15 ? pos : undefined,
            shirt_number: pos,
            status: 'starter',
        });
    }

    (event.target as HTMLInputElement).value = '';
}

async function onRivalBenchInput(shirtNumber: number, event: Event): Promise<void> {
    const name = (event.target as HTMLInputElement).value.trim();
    if (!name) return;

    const lineup = await lineupStore.ensureLineup(props.video.id, 'rival');
    await lineupStore.addPlayer(lineup.id, {
        player_name: name,
        shirt_number: shirtNumber,
        status: 'substitute',
    });

    (event.target as HTMLInputElement).value = '';
}

async function removeRivalPlayer(pos: number): Promise<void> {
    const player = getRivalPlayer(pos);
    if (player) await lineupStore.removePlayer(player.id);
}

async function removeRivalBenchPlayer(shirtNumber: number): Promise<void> {
    const player = getRivalBenchPlayer(shirtNumber);
    if (player) await lineupStore.removePlayer(player.id);
}

async function addKnownRivalPlayer(rp: RivalPlayer): Promise<void> {
    if (isRivalPlayerAdded(rp.id)) return;

    const lineup = await lineupStore.ensureLineup(props.video.id, 'rival');
    const pos = rp.usual_position ?? undefined;

    await lineupStore.addPlayer(lineup.id, {
        rival_player_id: rp.id,
        player_name: rp.name,
        position_number: pos,
        shirt_number: rp.shirt_number ?? pos,
        status: 'starter',
    });
}

// ── Load known rival players whenever the modal is opened ─────────────────────

watch(
    () => props.show,
    async (val) => {
        if (val && props.video.rival_team_id) {
            try {
                knownRivalPlayers.value = await lineupStore.fetchRivalPlayers(
                    props.video.rival_team_id
                );
            } catch {
                // Rival team has no stored players yet — that is fine
            }
        }
    },
    { immediate: true }
);
</script>

<style scoped>
/* ── Overlay & dialog ─────────────────────────────────────────────────────── */
.modal-overlay {
    position: fixed;
    top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(0, 0, 0, 0.75);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 10000;
    padding: 1rem;
}

.modal-dialog {
    width: 100%;
    max-width: 720px;
    max-height: 90vh;
    display: flex;
    flex-direction: column;
}

.modal-content {
    background: #1a1a1a;
    border: 1px solid #333;
    border-radius: 6px;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.5);
    display: flex;
    flex-direction: column;
    max-height: 90vh;
    overflow: hidden;
}

/* ── Header ───────────────────────────────────────────────────────────────── */
.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem 1rem;
    border-bottom: 1px solid #333;
    flex-shrink: 0;
}

.modal-title {
    color: #fff;
    font-size: 1rem;
    font-weight: 600;
    margin: 0;
}

.match-info {
    font-weight: 400;
    font-size: 0.85rem;
    color: #aaa;
}

.close {
    background: none;
    border: none;
    color: #ccc;
    font-size: 1.4rem;
    cursor: pointer;
    padding: 0;
    line-height: 1;
}
.close:hover { color: #fff; }

/* ── Tabs ─────────────────────────────────────────────────────────────────── */
.lineup-tabs {
    display: flex;
    border-bottom: 1px solid #333;
    flex-shrink: 0;
}

.lineup-tab {
    flex: 1;
    padding: 0.6rem 1rem;
    background: transparent;
    border: none;
    border-bottom: 2px solid transparent;
    color: #888;
    cursor: pointer;
    font-size: 0.85rem;
    font-weight: 500;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.4rem;
}

.lineup-tab.active {
    color: #00B7B5;
    border-bottom-color: #00B7B5;
}

.lineup-tab:hover:not(.active) {
    color: #ccc;
    background: rgba(255, 255, 255, 0.03);
}

.player-count {
    background: rgba(0, 183, 181, 0.2);
    color: #00B7B5;
    border-radius: 10px;
    padding: 0 0.4rem;
    font-size: 0.7rem;
    font-weight: 700;
    min-width: 18px;
    text-align: center;
}

/* ── Body ─────────────────────────────────────────────────────────────────── */
.modal-body {
    padding: 0.9rem 1rem;
    overflow-y: auto;
    flex: 1;
}

.loading-state {
    text-align: center;
    padding: 2rem;
    color: #888;
}

/* ── Section headers ──────────────────────────────────────────────────────── */
.section-header {
    display: flex;
    align-items: baseline;
    gap: 0.75rem;
    margin-bottom: 0.5rem;
}

.section-title {
    font-size: 0.75rem;
    font-weight: 700;
    color: #00B7B5;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.hint {
    font-size: 0.7rem;
    color: #666;
}

.mt-3 { margin-top: 1rem !important; }

/* ── Grids ────────────────────────────────────────────────────────────────── */
.players-grid {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 0.4rem;
}

.bench-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 0.4rem;
}

/* ── Slot ─────────────────────────────────────────────────────────────────── */
.player-slot,
.bench-slot {
    background: #252525;
    border: 1px solid #333;
    border-radius: 5px;
    padding: 0.4rem;
    min-height: 68px;
    display: flex;
    flex-direction: column;
    gap: 0.2rem;
}

.player-slot.filled,
.bench-slot.filled {
    border-color: #005461;
    background: #1a2a2a;
}

.player-slot.rival.filled { border-color: #444; background: #222; }

.slot-number {
    font-size: 0.7rem;
    font-weight: 700;
    color: #00B7B5;
    line-height: 1;
}

.slot-position {
    font-size: 0.6rem;
    color: #666;
    line-height: 1;
}

.slot-player {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.3rem;
    flex: 1;
}

.player-name {
    font-size: 0.72rem;
    color: #ddd;
    font-weight: 500;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    flex: 1;
}

.btn-remove-player {
    background: none;
    border: none;
    color: #666;
    cursor: pointer;
    font-size: 0.65rem;
    padding: 0;
    flex-shrink: 0;
}
.btn-remove-player:hover { color: #dc3545; }

.slot-empty {
    flex: 1;
    display: flex;
    align-items: flex-end;
}

/* ── Form controls inside slots ───────────────────────────────────────────── */
.player-select {
    width: 100%;
    background: #1a1a1a;
    border: 1px solid #444;
    color: #aaa;
    border-radius: 3px;
    font-size: 0.65rem;
    padding: 0.2rem 0.3rem;
    cursor: pointer;
}
.player-select:focus { outline: none; border-color: #00B7B5; }

.player-input {
    width: 100%;
    background: #1a1a1a;
    border: 1px solid #444;
    color: #ddd;
    border-radius: 3px;
    font-size: 0.68rem;
    padding: 0.25rem 0.35rem;
}
.player-input:focus { outline: none; border-color: #00B7B5; }

/* ── Known rival players ──────────────────────────────────────────────────── */
.known-players-hint {
    font-size: 0.72rem;
    color: #666;
    margin-bottom: 0.4rem;
}

.known-players {
    display: flex;
    flex-wrap: wrap;
    gap: 0.3rem;
    margin-bottom: 0.75rem;
}

.known-player-chip {
    background: #252525;
    border: 1px solid #444;
    border-radius: 12px;
    color: #aaa;
    font-size: 0.72rem;
    padding: 0.2rem 0.55rem;
    cursor: pointer;
    transition: all 0.15s;
}
.known-player-chip:hover:not(.added) {
    border-color: #00B7B5;
    color: #00B7B5;
}
.known-player-chip.added {
    border-color: #005461;
    color: #00B7B5;
    background: rgba(0, 183, 181, 0.1);
}

.chip-number {
    font-weight: 700;
    margin-right: 0.25rem;
}

/* ── Footer ───────────────────────────────────────────────────────────────── */
.modal-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem 1rem;
    border-top: 1px solid #333;
    flex-shrink: 0;
}

.footer-info { font-size: 0.78rem; }

.btn {
    padding: 0.4rem 0.9rem;
    border-radius: 4px;
    font-size: 0.85rem;
    font-weight: 500;
    cursor: pointer;
    border: none;
    transition: all 0.2s;
}

.btn-secondary { background: #444; color: #ccc; }
.btn-secondary:hover { background: #555; color: #fff; }

.text-muted { color: #777 !important; }

/* ── Transition ───────────────────────────────────────────────────────────── */
.fade-enter-active,
.fade-leave-active { transition: opacity 0.2s; }
.fade-enter-from,
.fade-leave-to { opacity: 0; }

/* ── Responsive ───────────────────────────────────────────────────────────── */
@media (max-width: 600px) {
    .players-grid { grid-template-columns: repeat(3, 1fr); }
    .bench-grid { grid-template-columns: repeat(3, 1fr); }
}
</style>
