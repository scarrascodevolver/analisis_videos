<script setup lang="ts">
import { ref, watch } from 'vue';
import type { VideoClip } from '@/types/video-player';

const props = defineProps<{
    clip: VideoClip;
    videoId: number;
    tournamentId?: number | null;
    show: boolean;
}>();

const emit = defineEmits<{
    close: [];
}>();

interface PlayerResult {
    id: number;
    name: string;
    org_name: string;
    org_id: number;
    is_own_org: boolean;
}

const query        = ref('');
const results      = ref<PlayerResult[]>([]);
const selected     = ref<PlayerResult | null>(null);
const message      = ref('');
const loading      = ref(false);
const sending      = ref(false);
const searchDone   = ref(false);
let searchTimeout: ReturnType<typeof setTimeout> | null = null;

watch(() => props.show, (val) => {
    if (!val) {
        query.value    = '';
        results.value  = [];
        selected.value = null;
        message.value  = '';
        searchDone.value = false;
    }
});

watch(query, (val) => {
    selected.value = null;
    if (searchTimeout) clearTimeout(searchTimeout);
    if (val.length < 2) {
        results.value = [];
        searchDone.value = false;
        return;
    }
    searchTimeout = setTimeout(() => searchPlayers(val), 300);
});

async function searchPlayers(q: string) {
    loading.value = true;
    try {
        const params = new URLSearchParams({ q, video_id: String(props.videoId) });
        const res = await fetch(`/api/clips/search-players?${params}`);
        if (res.ok) results.value = await res.json();
        searchDone.value = true;
    } finally {
        loading.value = false;
    }
}

function selectPlayer(player: PlayerResult) {
    selected.value = player;
    query.value    = player.name;
    results.value  = [];
}

async function send() {
    if (!selected.value || sending.value) return;

    sending.value = true;
    try {
        const csrf = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content ?? '';
        const res = await fetch(`/api/clips/${props.clip.id}/share-with-player`, {
            method:  'POST',
            headers: { 'X-CSRF-TOKEN': csrf, 'Content-Type': 'application/json' },
            body:    JSON.stringify({ user_id: selected.value.id, message: message.value }),
        });
        if (res.ok) {
            emit('close');
        } else {
            const data = await res.json().catch(() => ({}));
            alert(data.error ?? 'Error al compartir el clip.');
        }
    } finally {
        sending.value = false;
    }
}

function formatTime(seconds: number) {
    const s = Math.floor(seconds);
    return `${String(Math.floor(s / 60)).padStart(2, '0')}:${String(s % 60).padStart(2, '0')}`;
}
</script>

<template>
    <Teleport to="body">
        <div v-if="show" class="scm-backdrop" @click.self="$emit('close')">
            <div class="scm-modal">
                <!-- Header -->
                <div class="scm-header">
                    <span class="scm-title">
                        <i class="fas fa-paper-plane"></i>
                        Compartir clip con jugador
                    </span>
                    <button class="scm-close" @click="$emit('close')">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <!-- Clip info -->
                <div class="scm-clip-info">
                    <i class="fas fa-film"></i>
                    <span>{{ clip.category?.name ?? 'Sin categoría' }}</span>
                    <span class="scm-time">{{ formatTime(clip.start_time) }} – {{ formatTime(clip.end_time) }}</span>
                </div>

                <!-- Search -->
                <div class="scm-section">
                    <label class="scm-label">Buscar jugador</label>
                    <div class="scm-search-wrap">
                        <i class="fas fa-search scm-search-icon"></i>
                        <input
                            v-model="query"
                            class="scm-input"
                            placeholder="Nombre del jugador (mín. 2 letras)..."
                            autocomplete="off"
                        />
                        <i v-if="loading" class="fas fa-spinner fa-spin scm-loading-icon"></i>
                    </div>

                    <!-- Results -->
                    <div v-if="results.length > 0" class="scm-results">
                        <button
                            v-for="player in results"
                            :key="player.id"
                            class="scm-result-item"
                            @click="selectPlayer(player)"
                        >
                            <span class="scm-player-name">{{ player.name }}</span>
                            <span
                                class="scm-org-badge"
                                :class="player.is_own_org ? 'scm-org-own' : 'scm-org-other'"
                            >
                                {{ player.org_name }}
                            </span>
                        </button>
                    </div>
                    <div v-else-if="searchDone && !loading && query.length >= 2" class="scm-no-results">
                        No se encontraron jugadores con ese nombre.
                    </div>
                </div>

                <!-- Selected player chip -->
                <div v-if="selected" class="scm-selected">
                    <i class="fas fa-user-check"></i>
                    <span>{{ selected.name }}</span>
                    <span class="scm-org-badge" :class="selected.is_own_org ? 'scm-org-own' : 'scm-org-other'">
                        {{ selected.org_name }}
                    </span>
                    <button class="scm-remove-selected" @click="selected = null; query = ''">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <!-- Message -->
                <div class="scm-section">
                    <label class="scm-label">Mensaje (opcional)</label>
                    <textarea
                        v-model="message"
                        class="scm-textarea"
                        placeholder="Ej: Mirá tu posición en el scrum en el minuto 34..."
                        rows="3"
                        maxlength="500"
                    ></textarea>
                    <span class="scm-char-count">{{ message.length }}/500</span>
                </div>

                <!-- Actions -->
                <div class="scm-actions">
                    <button class="scm-btn-cancel" @click="$emit('close')">Cancelar</button>
                    <button
                        class="scm-btn-send"
                        :disabled="!selected || sending"
                        @click="send"
                    >
                        <i :class="sending ? 'fas fa-spinner fa-spin' : 'fas fa-paper-plane'"></i>
                        {{ sending ? 'Enviando...' : 'Enviar clip' }}
                    </button>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<style>
.scm-backdrop {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.75);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 99999;
    padding: 1rem;
}

.scm-modal {
    background: #1e1e1e;
    border: 1px solid #333;
    border-radius: 8px;
    width: 100%;
    max-width: 440px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.8);
    overflow: hidden;
}

.scm-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.85rem 1rem;
    border-bottom: 1px solid #2c2c2c;
    background: #181818;
}

.scm-title {
    font-size: 13px;
    font-weight: 600;
    color: #00B7B5;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.scm-close {
    background: transparent;
    border: none;
    color: #666;
    cursor: pointer;
    font-size: 12px;
    padding: 0.25rem;
    line-height: 1;
    transition: color 0.15s;
}
.scm-close:hover { color: #fff; }

.scm-clip-info {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.6rem 1rem;
    background: #252525;
    border-bottom: 1px solid #2c2c2c;
    font-size: 11px;
    color: #aaa;
}
.scm-clip-info i { color: #00B7B5; font-size: 11px; }
.scm-time {
    margin-left: auto;
    color: #00B7B5;
    font-weight: 500;
    font-family: monospace;
}

.scm-section {
    padding: 0.85rem 1rem 0;
    position: relative;
}

.scm-label {
    display: block;
    font-size: 10.5px;
    font-weight: 600;
    color: #888;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    margin-bottom: 0.4rem;
}

.scm-search-wrap {
    position: relative;
}
.scm-search-icon {
    position: absolute;
    left: 0.6rem;
    top: 50%;
    transform: translateY(-50%);
    color: #555;
    font-size: 11px;
    pointer-events: none;
}
.scm-loading-icon {
    position: absolute;
    right: 0.6rem;
    top: 50%;
    transform: translateY(-50%);
    color: #00B7B5;
    font-size: 11px;
    pointer-events: none;
}

.scm-input {
    width: 100%;
    background: #2a2a2a;
    border: 1px solid #3a3a3a;
    border-radius: 5px;
    color: #e0e0e0;
    font-size: 12px;
    padding: 0.45rem 0.5rem 0.45rem 2rem;
    outline: none;
    transition: border-color 0.15s;
}
.scm-input:focus { border-color: #00B7B5; }

.scm-results {
    background: #252525;
    border: 1px solid #3a3a3a;
    border-radius: 5px;
    margin-top: 0.3rem;
    overflow: hidden;
    max-height: 180px;
    overflow-y: auto;
}

.scm-result-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    width: 100%;
    padding: 0.45rem 0.7rem;
    background: transparent;
    border: none;
    border-bottom: 1px solid #2c2c2c;
    color: #ddd;
    font-size: 12px;
    cursor: pointer;
    text-align: left;
    transition: background 0.1s;
}
.scm-result-item:last-child { border-bottom: none; }
.scm-result-item:hover { background: rgba(0, 183, 181, 0.08); }

.scm-player-name { flex: 1; }

.scm-org-badge {
    font-size: 9.5px;
    padding: 0.15rem 0.4rem;
    border-radius: 3px;
    font-weight: 600;
    white-space: nowrap;
}
.scm-org-own {
    background: rgba(0, 84, 97, 0.5);
    color: #00B7B5;
}
.scm-org-other {
    background: rgba(100, 70, 0, 0.3);
    color: #f0a500;
}

.scm-no-results {
    font-size: 11px;
    color: #666;
    padding: 0.5rem 0;
    text-align: center;
}

.scm-selected {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin: 0.65rem 1rem 0;
    padding: 0.45rem 0.7rem;
    background: rgba(0, 183, 181, 0.1);
    border: 1px solid rgba(0, 183, 181, 0.3);
    border-radius: 5px;
    font-size: 12px;
    color: #00B7B5;
}
.scm-selected i { font-size: 11px; }
.scm-selected span:nth-child(2) { flex: 1; }

.scm-remove-selected {
    background: transparent;
    border: none;
    color: #666;
    cursor: pointer;
    font-size: 10px;
    padding: 0;
    line-height: 1;
}
.scm-remove-selected:hover { color: #ff6b6b; }

.scm-textarea {
    width: 100%;
    background: #2a2a2a;
    border: 1px solid #3a3a3a;
    border-radius: 5px;
    color: #e0e0e0;
    font-size: 12px;
    padding: 0.45rem 0.6rem;
    outline: none;
    resize: none;
    line-height: 1.5;
    transition: border-color 0.15s;
    font-family: inherit;
}
.scm-textarea:focus { border-color: #00B7B5; }

.scm-char-count {
    display: block;
    text-align: right;
    font-size: 10px;
    color: #555;
    margin-top: 0.2rem;
}

.scm-actions {
    display: flex;
    justify-content: flex-end;
    gap: 0.5rem;
    padding: 0.85rem 1rem;
    margin-top: 0.5rem;
    border-top: 1px solid #2c2c2c;
}

.scm-btn-cancel {
    background: transparent;
    border: 1px solid #3a3a3a;
    border-radius: 5px;
    color: #888;
    font-size: 12px;
    padding: 0.4rem 0.9rem;
    cursor: pointer;
    transition: all 0.15s;
}
.scm-btn-cancel:hover { border-color: #555; color: #ccc; }

.scm-btn-send {
    background: #005461;
    border: 1px solid #005461;
    border-radius: 5px;
    color: #fff;
    font-size: 12px;
    font-weight: 600;
    padding: 0.4rem 1rem;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 0.4rem;
    transition: all 0.15s;
}
.scm-btn-send:hover:not(:disabled) { background: #00B7B5; border-color: #00B7B5; }
.scm-btn-send:disabled { opacity: 0.45; cursor: not-allowed; }
</style>
