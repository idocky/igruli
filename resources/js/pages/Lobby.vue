<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { computed, onUnmounted, ref, watch } from 'vue';
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

interface LobbyData {
    id: number;
    title: string;
    code: string;
    players: Player[];
    createdBy: string | null;
    canManagePlayers: boolean;
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

interface PlayerRemovedEvent {
    userId: string;
}

const props = defineProps<{
    lobby: LobbyData;
    currentPlayer: Player | null;
}>();

const joinForm = useForm({
    username: '',
    team: 1,
});

const removeForm = useForm({
    guest_id: '',
});

const isJoined = ref(false);
const currentPlayer = ref<Player | null>(null);
const team1Players = ref<Player[]>(props.lobby.players.filter((p) => p.team === 1));
const team2Players = ref<Player[]>(props.lobby.players.filter((p) => p.team === 2));
const canManagePlayers = computed(() => props.lobby.canManagePlayers);

let echo: Echo<'reverb'> | null = null;

function addPlayer(member: PresenceMember): void {
    const player: Player = {
        userId: member.id,
        username: member.username,
        team: member.team,
    };

    if (player.team === 1) {
        if (!team1Players.value.some((p) => p.userId === player.userId)) {
            team1Players.value.push(player);
        }
    } else if (player.team === 2) {
        if (!team2Players.value.some((p) => p.userId === player.userId)) {
            team2Players.value.push(player);
        }
    }
    console.log(1);
}

function removePlayerByUserId(userId: string): void {
    team1Players.value = team1Players.value.filter((p) => p.userId !== userId);
    team2Players.value = team2Players.value.filter((p) => p.userId !== userId);
}

function removePlayer(member: PresenceMember): void {
    removePlayerByUserId(member.id);
}

function subscribePresence(): void {
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

    echo.join(`lobby.${props.lobby.code}`)
        .here((members: PresenceMember[]) => {
            members.forEach((m) => addPlayer(m));
        })
        .joining((member: PresenceMember) => {
            addPlayer(member);
        })
        .leaving((member: PresenceMember) => {
            removePlayer(member);
        })
        .listen('.lobby.player-removed', (event: PlayerRemovedEvent) => {
            removePlayerByUserId(event.userId);
        });
}

function ensurePresenceSubscribed(): void {
    if (echo) {
        return;
    }

    subscribePresence();
}

function joinTeam(team: number): void {
    if (!joinForm.username.trim() || joinForm.processing) {
        return;
    }

    joinForm.team = team;

    joinForm.post(`/lobby/${props.lobby.code}/join`, {
        preserveScroll: true,
    });
}

function kickPlayer(player: Player): void {
    if (!canManagePlayers.value || removeForm.processing) {
        return;
    }

    removePlayerByUserId(player.userId);
    removeForm.guest_id = player.userId;

    removeForm.delete(`/lobby/${props.lobby.code}/players`, {
        preserveScroll: true,
        onFinish: () => {
            removeForm.reset();
        },
    });
}

watch(
    () => props.currentPlayer,
    (player) => {
        currentPlayer.value = player;
        isJoined.value = !!player;

        if (player) {
            ensurePresenceSubscribed();
        }
    },
    { immediate: true },
);

onUnmounted(() => {
    echo?.leave(`lobby.${props.lobby.code}`);
    echo = null;
});
</script>

<template>
    <Head :title="`Лобби — ${lobby.title}`" />

    <div
        class="flex min-h-screen flex-col items-center bg-background p-6 lg:p-8"
    >
        <div class="w-full max-w-4xl">
            <div class="mb-8 text-center">
                <h1 class="text-3xl font-bold tracking-tight text-foreground">
                    {{ lobby.title }}
                </h1>
                <div class="mt-2 flex items-center justify-center gap-2">
                    <span class="text-sm text-muted-foreground">
                        Код лобби:
                    </span>
                    <Badge variant="secondary" class="text-sm">
                        {{ lobby.code }}
                    </Badge>
                </div>
            </div>

            <!-- Nickname entry -->
            <div
                v-if="!isJoined"
                class="mx-auto mb-10 flex max-w-sm flex-col items-center gap-4"
            >
                <label
                    for="nickname"
                    class="text-sm font-medium text-muted-foreground"
                >
                    Введите ваш никнейм
                </label>
                <Input
                    id="nickname"
                    v-model="joinForm.username"
                    placeholder="Никнейм"
                    class="text-center"
                    :aria-invalid="!!joinForm.errors.username"
                    @keyup.enter="
                        joinForm.username.trim() ? joinTeam(1) : undefined
                    "
                />
                <p
                    v-if="joinForm.errors.username"
                    class="text-sm text-destructive"
                >
                    {{ joinForm.errors.username }}
                </p>
            </div>

            <!-- Joined badge -->
            <div v-else class="mx-auto mb-10 text-center">
                <p class="text-lg text-muted-foreground">
                    Вы в игре как
                    <span class="font-semibold text-foreground">{{
                        currentPlayer?.username
                    }}</span>
                    — Команда {{ currentPlayer?.team }}
                </p>
            </div>

            <!-- Teams -->
            <div class="grid gap-6 md:grid-cols-2">
                <!-- Team 1 -->
                <Card
                    class="border-blue-500/30 bg-blue-500/5 dark:bg-blue-500/10"
                >
                    <CardHeader class="border-b border-blue-500/20">
                        <CardTitle
                            class="flex items-center gap-2 text-xl text-blue-600 dark:text-blue-400"
                        >
                            <span
                                class="flex h-8 w-8 items-center justify-center rounded-full bg-blue-500/20 text-sm font-bold"
                            >
                                1
                            </span>
                            Команда 1
                            <span
                                class="ml-auto rounded-full bg-blue-500/20 px-2.5 py-0.5 text-xs font-medium"
                            >
                                {{ team1Players.length }}
                            </span>
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="min-h-[200px]">
                        <ul class="mb-4 space-y-2">
                            <li
                                v-for="player in team1Players"
                                :key="player.userId"
                                class="flex items-center gap-2 rounded-md bg-blue-500/10 px-3 py-2 text-sm"
                            >
                                <span
                                    class="flex h-6 w-6 items-center justify-center rounded-full bg-blue-500/30 text-xs font-medium text-blue-700 dark:text-blue-300"
                                >
                                    {{
                                        player.username.charAt(0).toUpperCase()
                                    }}
                                </span>
                                <span
                                    class="font-medium"
                                    :class="{
                                        'text-blue-700 dark:text-blue-300':
                                            player.userId ===
                                            currentPlayer?.userId,
                                    }"
                                >
                                    {{ player.username }}
                                    <span
                                        v-if="
                                            player.userId ===
                                            currentPlayer?.userId
                                        "
                                        class="text-xs text-muted-foreground"
                                    >
                                        (вы)
                                    </span>
                                </span>
                                <Button
                                    v-if="canManagePlayers"
                                    variant="destructive"
                                    size="sm"
                                    class="ml-auto h-8 px-2"
                                    :disabled="removeForm.processing"
                                    @click="kickPlayer(player)"
                                >
                                    Удалить
                                </Button>
                            </li>
                        </ul>
                        <p
                            v-if="team1Players.length === 0"
                            class="py-8 text-center text-sm text-muted-foreground"
                        >
                            Пока никого нет
                        </p>
                        <Button
                            v-if="!isJoined && joinForm.username.trim()"
                            class="w-full bg-blue-600 text-white hover:bg-blue-700"
                            :disabled="joinForm.processing"
                            @click="joinTeam(1)"
                        >
                            Присоединиться к Команде 1
                        </Button>
                    </CardContent>
                </Card>

                <!-- Team 2 -->
                <Card class="border-red-500/30 bg-red-500/5 dark:bg-red-500/10">
                    <CardHeader class="border-b border-red-500/20">
                        <CardTitle
                            class="flex items-center gap-2 text-xl text-red-600 dark:text-red-400"
                        >
                            <span
                                class="flex h-8 w-8 items-center justify-center rounded-full bg-red-500/20 text-sm font-bold"
                            >
                                2
                            </span>
                            Команда 2
                            <span
                                class="ml-auto rounded-full bg-red-500/20 px-2.5 py-0.5 text-xs font-medium"
                            >
                                {{ team2Players.length }}
                            </span>
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="min-h-[200px]">
                        <ul class="mb-4 space-y-2">
                            <li
                                v-for="player in team2Players"
                                :key="player.userId"
                                class="flex items-center gap-2 rounded-md bg-red-500/10 px-3 py-2 text-sm"
                            >
                                <span
                                    class="flex h-6 w-6 items-center justify-center rounded-full bg-red-500/30 text-xs font-medium text-red-700 dark:text-red-300"
                                >
                                    {{
                                        player.username.charAt(0).toUpperCase()
                                    }}
                                </span>
                                <span
                                    class="font-medium"
                                    :class="{
                                        'text-red-700 dark:text-red-300':
                                            player.userId ===
                                            currentPlayer?.userId,
                                    }"
                                >
                                    {{ player.username }}
                                    <span
                                        v-if="
                                            player.userId ===
                                            currentPlayer?.userId
                                        "
                                        class="text-xs text-muted-foreground"
                                    >
                                        (вы)
                                    </span>
                                </span>
                                <Button
                                    v-if="canManagePlayers"
                                    variant="destructive"
                                    size="sm"
                                    class="ml-auto h-8 px-2"
                                    :disabled="removeForm.processing"
                                    @click="kickPlayer(player)"
                                >
                                    Удалить
                                </Button>
                            </li>
                        </ul>
                        <p
                            v-if="team2Players.length === 0"
                            class="py-8 text-center text-sm text-muted-foreground"
                        >
                            Пока никого нет
                        </p>
                        <Button
                            v-if="!isJoined && joinForm.username.trim()"
                            class="w-full bg-red-600 text-white hover:bg-red-700"
                            :disabled="joinForm.processing"
                            @click="joinTeam(2)"
                        >
                            Присоединиться к Команде 2
                        </Button>
                    </CardContent>
                </Card>
            </div>
        </div>
    </div>
</template>
