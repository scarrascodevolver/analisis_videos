<script setup lang="ts">
import { ref, computed, watch, onMounted, onBeforeUnmount } from 'vue';
import type { User } from '@/types/video-player';

const props = defineProps<{
    modelValue: string;
    users: Pick<User, 'id' | 'name' | 'role'>[];
    placeholder?: string;
    rows?: number;
}>();

const emit = defineEmits<{
    'update:modelValue': [value: string];
}>();

const textarea = ref<HTMLTextAreaElement | null>(null);
const showDropdown = ref(false);
const dropdownPosition = ref({ top: 0, left: 0 });
const mentionQuery = ref('');
const selectedIndex = ref(0);
const mentionStartPos = ref(-1);

const filteredUsers = computed(() => {
    if (!mentionQuery.value) return props.users.slice(0, 10);
    const q = mentionQuery.value.toLowerCase();
    return props.users
        .filter(u => u.name.toLowerCase().includes(q))
        .slice(0, 8);
});

function onInput(e: Event) {
    const target = e.target as HTMLTextAreaElement;
    emit('update:modelValue', target.value);
    checkForMention(target);
}

function checkForMention(el: HTMLTextAreaElement) {
    const cursorPos = el.selectionStart;
    const textBeforeCursor = el.value.substring(0, cursorPos);

    // Find @ symbol before cursor
    const lastAtIndex = textBeforeCursor.lastIndexOf('@');

    if (lastAtIndex === -1) {
        showDropdown.value = false;
        return;
    }

    // Check if @ is at start or preceded by whitespace
    const charBefore = lastAtIndex > 0 ? textBeforeCursor[lastAtIndex - 1] : ' ';
    if (charBefore !== ' ' && charBefore !== '\n' && lastAtIndex !== 0) {
        showDropdown.value = false;
        return;
    }

    const query = textBeforeCursor.substring(lastAtIndex + 1);

    // If there's a space after the query, close dropdown
    if (query.includes(' ') && query.length > 20) {
        showDropdown.value = false;
        return;
    }

    mentionQuery.value = query;
    mentionStartPos.value = lastAtIndex;
    selectedIndex.value = 0;

    if (filteredUsers.value.length > 0) {
        showDropdown.value = true;
        positionDropdown(el);
    } else {
        showDropdown.value = false;
    }
}

function positionDropdown(el: HTMLTextAreaElement) {
    const rect = el.getBoundingClientRect();
    const lineHeight = parseInt(getComputedStyle(el).lineHeight) || 20;
    const lines = el.value.substring(0, el.selectionStart).split('\n').length;

    dropdownPosition.value = {
        top: Math.min(lines * lineHeight, el.offsetHeight) + 4,
        left: 0,
    };
}

function selectUser(user: Pick<User, 'id' | 'name' | 'role'>) {
    if (!textarea.value || mentionStartPos.value === -1) return;

    const before = props.modelValue.substring(0, mentionStartPos.value);
    const after = props.modelValue.substring(textarea.value.selectionStart);
    const newValue = `${before}@${user.name} ${after}`;

    emit('update:modelValue', newValue);
    showDropdown.value = false;

    // Set cursor position after the mention
    const cursorPos = mentionStartPos.value + user.name.length + 2; // @ + name + space
    requestAnimationFrame(() => {
        if (textarea.value) {
            textarea.value.focus();
            textarea.value.selectionStart = cursorPos;
            textarea.value.selectionEnd = cursorPos;
        }
    });
}

function onKeydown(e: KeyboardEvent) {
    if (!showDropdown.value) return;

    if (e.key === 'ArrowDown') {
        e.preventDefault();
        selectedIndex.value = Math.min(selectedIndex.value + 1, filteredUsers.value.length - 1);
    } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        selectedIndex.value = Math.max(selectedIndex.value - 1, 0);
    } else if (e.key === 'Enter' && showDropdown.value) {
        e.preventDefault();
        if (filteredUsers.value[selectedIndex.value]) {
            selectUser(filteredUsers.value[selectedIndex.value]);
        }
    } else if (e.key === 'Escape') {
        showDropdown.value = false;
    }
}

function roleBadgeClass(role: string): string {
    switch (role) {
        case 'analista': return 'badge-primary';
        case 'entrenador': return 'badge-success';
        default: return 'badge-info';
    }
}

function onClickOutside() {
    showDropdown.value = false;
}

onMounted(() => {
    document.addEventListener('click', onClickOutside);
});

onBeforeUnmount(() => {
    document.removeEventListener('click', onClickOutside);
});
</script>

<template>
    <div class="mention-input-wrapper" @click.stop>
        <textarea
            ref="textarea"
            :value="modelValue"
            :placeholder="placeholder || 'Escribe tu comentario... Usa @nombre para mencionar'"
            :rows="rows || 3"
            class="form-control"
            @input="onInput"
            @keydown="onKeydown"
        ></textarea>

        <Transition name="dropdown">
            <div
                v-if="showDropdown && filteredUsers.length"
                class="mention-dropdown"
                :style="{ top: dropdownPosition.top + 'px' }"
            >
                <div
                    v-for="(user, i) in filteredUsers"
                    :key="user.id"
                    class="mention-item"
                    :class="{ selected: i === selectedIndex }"
                    @mouseenter="selectedIndex = i"
                    @click="selectUser(user)"
                >
                    <i class="fas fa-user mr-2"></i>
                    <span class="mention-name">{{ user.name }}</span>
                    <span class="badge badge-sm ml-2" :class="roleBadgeClass(user.role)">
                        {{ user.role.charAt(0).toUpperCase() + user.role.slice(1) }}
                    </span>
                </div>
            </div>
        </Transition>
    </div>
</template>

<style scoped>
.mention-input-wrapper {
    position: relative;
}

.mention-input-wrapper textarea {
    background-color: #003d4a;
    border-color: #018790;
    color: #fff;
    font-size: 13px;
}

.mention-input-wrapper textarea:focus {
    background-color: #005461;
    border-color: #00B7B5;
    color: #fff;
}

.mention-input-wrapper textarea::placeholder {
    color: #aaa;
}

.mention-dropdown {
    position: absolute;
    left: 0;
    right: 0;
    background: #1a1a1a;
    border: 1px solid #333;
    border-radius: 6px;
    max-height: 200px;
    overflow-y: auto;
    z-index: 1000;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.5);
}

.mention-item {
    padding: 8px 12px;
    cursor: pointer;
    display: flex;
    align-items: center;
    font-size: 13px;
    color: #ccc;
}

.mention-item:hover,
.mention-item.selected {
    background: #005461;
    color: #fff;
}

.mention-name {
    font-weight: 500;
}

.dropdown-enter-active, .dropdown-leave-active {
    transition: opacity 0.15s, transform 0.15s;
}

.dropdown-enter-from, .dropdown-leave-to {
    opacity: 0;
    transform: translateY(-4px);
}
</style>
