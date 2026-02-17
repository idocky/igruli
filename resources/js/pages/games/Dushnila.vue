<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed, onUnmounted, ref, watch } from 'vue';
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';
import IgruliLayout from '@/layouts/IgruliLayout.vue';

interface LobbyData {
    id: number;
    title: string;
    code: string;
}

interface Player {
    userId: string;
    username: string;
    team: number;
}

interface PresenceMember {
    id: string;
    username: string;
    team: number;
}

interface RosterUpdatedEvent {
    players: Player[];
}

const props = defineProps<{
    lobby: LobbyData;
    players: Player[];
    currentPlayer: Player | null;
    game: string;
}>();

const players = ref<Player[]>([...props.players]);
const currentPlayer = ref<Player | null>(props.currentPlayer);

const title = computed(() => `${props.game} — ${props.lobby.title}`);

let echo: Echo<'reverb'> | null = null;

function ensureEcho(): Echo<'reverb'> {
    if (echo) {
        return echo;
    }

    window.Pusher = Pusher;

    echo = new Echo({
        broadcaster: 'reverb',
        key: import.meta.env.VITE_REVERB_APP_KEY,
        wsHost: import.meta.env.VITE_REVERB_HOST,
        wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,
        wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
        forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
        enabledTransports: ['ws', 'wss'],
    });

    return echo;
}

function upsertPlayer(player: Player): void {
    const existingIdx = players.value.findIndex((p) => p.userId === player.userId);

    if (existingIdx === -1) {
        players.value.push(player);
        return;
    }

    players.value[existingIdx] = player;
}

function addMember(member: PresenceMember): void {
    upsertPlayer({
        userId: member.id,
        username: member.username,
        team: member.team,
    });
}

function removeMember(member: PresenceMember): void {
    players.value = players.value.filter((p) => p.userId !== member.id);
}

function subscribePublic(): void {
    ensureEcho()
        .channel(`lobby.${props.lobby.code}.public`)
        .listen('.lobby.roster-updated', (event: RosterUpdatedEvent) => {
            players.value = event.players;
        });
}

function subscribePresence(): void {
    if (!currentPlayer.value) {
        return;
    }

    ensureEcho()
        .join(`lobby.${props.lobby.code}`)
        .here((members: PresenceMember[]) => {
            players.value = [];
            members.forEach((m) => addMember(m));
        })
        .joining((member: PresenceMember) => {
            addMember(member);
        })
        .leaving((member: PresenceMember) => {
            removeMember(member);
        });
}

watch(
    () => props.currentPlayer,
    (player) => {
        currentPlayer.value = player;

        if (player) {
            subscribePresence();
        }
    },
    { immediate: true },
);

subscribePublic();

onUnmounted(() => {
    echo?.leave(`lobby.${props.lobby.code}`);
    echo?.leaveChannel(`lobby.${props.lobby.code}.public`);
    echo = null;
});
</script>

<template>
    <Head :title="title" />

    <IgruliLayout max-width-class="max-w-6xl">
        <div class="mb-6">
            <h1 class="text-2xl font-bold tracking-tight text-foreground">
                {{ lobby.title }}
            </h1>
            <p class="mt-1 text-sm text-muted-foreground">Игра: {{ game }}</p>
        </div>

        <div class="rounded-lg border bg-muted/30 p-3">
            <div class="flex flex-nowrap gap-2 overflow-x-auto pb-1">
                <div
                    v-for="player in players"
                    :key="player.userId"
                    class="flex flex-none items-center gap-2 rounded-md border bg-background px-3 py-2"
                    :class="{
                        'border-foreground/30': player.userId === currentPlayer?.userId,
                    }"
                >
                    <div
                        class="flex h-8 w-8 items-center justify-center rounded-full bg-muted text-xs font-semibold text-foreground"
                    >
                        {{ player.username.charAt(0).toUpperCase() }}
                    </div>
                    <div class="min-w-0">
                        <p class="max-w-[9rem] truncate text-sm font-medium text-foreground">
                            {{ player.username }}
                        </p>
                        <p class="text-xs text-muted-foreground">
                            Команда {{ player.team }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </IgruliLayout>
</template>


