<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { onUnmounted, ref } from 'vue';
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

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

const props = defineProps<{
    lobby: LobbyData;
}>();

const nickname = ref('');
const isJoined = ref(false);
const currentPlayer = ref<Player | null>(null);
const team1Players = ref<Player[]>([]);
const team2Players = ref<Player[]>([]);
const isLoading = ref(false);

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
}

function removePlayer(member: PresenceMember): void {
    team1Players.value = team1Players.value.filter(
        (p) => p.userId !== member.id,
    );
    team2Players.value = team2Players.value.filter(
        (p) => p.userId !== member.id,
    );
}

function subscribePresence(): void {
    window.Pusher = Pusher;

    echo = new Echo({
        broadcaster: 'reverb',
        key: import.meta.env.VITE_REVERB_APP_KEY,
        wsHost: import.meta.env.VITE_REVERB_HOST,
        wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,
        wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
        forceTLS:
            (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
        enabledTransports: ['ws', 'wss'],
    });

    echo.join(`lobby.${props.lobby.code}`)
        .here((members: PresenceMember[]) => {
            team1Players.value = [];
            team2Players.value = [];
            members.forEach((m) => addPlayer(m));
        })
        .joining((member: PresenceMember) => {
            addPlayer(member);
        })
        .leaving((member: PresenceMember) => {
            removePlayer(member);
        });
}

async function joinTeam(team: number): Promise<void> {
    if (!nickname.value.trim() || isLoading.value) {
        return;
    }

    isLoading.value = true;

    try {
        const csrfToken =
            document
                .querySelector<HTMLMetaElement>('meta[name="csrf-token"]')
                ?.getAttribute('content') ?? '';

        const xsrfToken = decodeURIComponent(
            document.cookie
                .split('; ')
                .find((row) => row.startsWith('XSRF-TOKEN='))
                ?.split('=')[1] ?? '',
        );

        const response = await fetch(`/lobby/${props.lobby.code}/join`, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-XSRF-TOKEN': xsrfToken,
            },
            body: JSON.stringify({
                username: nickname.value.trim(),
                team,
            }),
        });

        if (response.ok) {
            const data = await response.json();
            currentPlayer.value = {
                userId: data.user_id,
                username: data.username,
                team: data.team,
            };
            isJoined.value = true;

            // Subscribe to presence channel after server saved session
            subscribePresence();
        }
    } finally {
        isLoading.value = false;
    }
}

onUnmounted(() => {
    echo?.leave(`lobby.${props.lobby.code}`);
});
</script>

<template>
    <Head :title="`Лобби — ${lobby.title}`" />

    <div
        class="flex min-h-screen flex-col items-center bg-background p-6 lg:p-8"
    >
        <div class="w-full max-w-4xl">
            <div class="mb-8 text-center">
                <h1
                    class="text-3xl font-bold tracking-tight text-foreground"
                >
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
                    v-model="nickname"
                    placeholder="Никнейм"
                    class="text-center"
                    @keyup.enter="nickname.trim() ? joinTeam(1) : undefined"
                />
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
                                        player.username
                                            .charAt(0)
                                            .toUpperCase()
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
                            </li>
                        </ul>
                        <p
                            v-if="team1Players.length === 0"
                            class="py-8 text-center text-sm text-muted-foreground"
                        >
                            Пока никого нет
                        </p>
                        <Button
                            v-if="!isJoined && nickname.trim()"
                            class="w-full bg-blue-600 text-white hover:bg-blue-700"
                            :disabled="isLoading"
                            @click="joinTeam(1)"
                        >
                            Присоединиться к Команде 1
                        </Button>
                    </CardContent>
                </Card>

                <!-- Team 2 -->
                <Card
                    class="border-red-500/30 bg-red-500/5 dark:bg-red-500/10"
                >
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
                                        player.username
                                            .charAt(0)
                                            .toUpperCase()
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
                            </li>
                        </ul>
                        <p
                            v-if="team2Players.length === 0"
                            class="py-8 text-center text-sm text-muted-foreground"
                        >
                            Пока никого нет
                        </p>
                        <Button
                            v-if="!isJoined && nickname.trim()"
                            class="w-full bg-red-600 text-white hover:bg-red-700"
                            :disabled="isLoading"
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
