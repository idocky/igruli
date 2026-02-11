<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { computed, onUnmounted, ref, watch } from 'vue';
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';
import IgruliLayout from '@/layouts/IgruliLayout.vue';

interface LobbyData {
    id: number;
    title: string;
    code: string;
    players: Player[];
    teams: Team[];
    createdBy: string | null;
    canManagePlayers: boolean;
}

interface Player {
    userId: string;
    username: string;
    team: number;
}

interface Team {
    number: number;
    name: string;
    maxPlayers: number | null;
    players: Player[];
}

interface GameInfo {
    title: string;
    team_max_size: number | null;
    max_teams: number;
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
    gameInfo: GameInfo;
    currentPlayer: Player | null;
}>();

const joinForm = useForm({
    username: '',
    team: props.lobby.teams[0]?.number ?? 1,
});

const removeForm = useForm({
    guest_id: '',
});

const createTeamForm = useForm({});

const isJoined = ref(false);
const currentPlayer = ref<Player | null>(null);
const teams = ref<Team[]>([]);
const canManagePlayers = computed(() => props.lobby.canManagePlayers);
const canAddTeam = computed(() => teams.value.length < props.gameInfo.max_teams);

let echo: Echo<'reverb'> | null = null;

watch(
    () => props.lobby.teams,
    (newTeams) => {
        teams.value = newTeams.map((team) => {
            const existing = teams.value.find((t) => t.number === team.number);

            if (!existing) {
                return team;
            }

            return {
                ...team,
                players: existing.players,
            };
        });
    },
    { immediate: true },
);

function addPlayer(member: PresenceMember): void {
    const player: Player = {
        userId: member.id,
        username: member.username,
        team: member.team,
    };

    const team = teams.value.find((t) => t.number === player.team);
    if (!team) {
        return;
    }

    if (!team.players.some((p) => p.userId === player.userId)) {
        team.players.push(player);
    }
}

function removePlayerByUserId(userId: string): void {
    teams.value = teams.value.map((team) => ({
        ...team,
        players: team.players.filter((p) => p.userId !== userId),
    }));
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

function createTeam(): void {
    if (!canAddTeam.value || createTeamForm.processing) {
        return;
    }

    createTeamForm.post(`/lobby/${props.lobby.code}/teams`, {
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

    <IgruliLayout>
        <div class="mb-8 text-center">
            <h1 class="text-3xl font-bold tracking-tight text-foreground">
                {{ lobby.title }}
            </h1>
            <div class="mt-2 flex items-center justify-center gap-2">
                <span class="text-sm text-muted-foreground">Код лобби:</span>
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
            <label for="nickname" class="text-sm font-medium text-muted-foreground">
                Введите ваш никнейм
            </label>
            <Input
                id="nickname"
                v-model="joinForm.username"
                placeholder="Никнейм"
                class="text-center"
                :aria-invalid="!!joinForm.errors.username"
                @keyup.enter="
                    joinForm.username.trim() && teams[0]
                        ? joinTeam(teams[0].number)
                        : undefined
                "
            />
            <p v-if="joinForm.errors.username" class="text-sm text-destructive">
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
        <div class="flex flex-wrap justify-center gap-6">
            <Card v-for="team in teams" :key="team.number" class="w-full max-w-sm">
                <CardHeader class="border-b">
                    <CardTitle class="flex items-center gap-2 text-xl">
                        <span
                            class="flex h-8 w-8 items-center justify-center rounded-full bg-muted text-sm font-bold text-foreground"
                        >
                            {{ team.number }}
                        </span>
                        {{ team.name }}
                        <span
                            class="ml-auto rounded-full bg-muted px-2.5 py-0.5 text-xs font-medium text-foreground"
                        >
                            {{ team.players.length
                            }}<template v-if="team.maxPlayers !== null"
                                >/ {{ team.maxPlayers }}</template
                            >
                        </span>
                    </CardTitle>
                </CardHeader>
                <CardContent class="min-h-[200px]">
                    <ul class="mb-4 space-y-2">
                        <li
                            v-for="player in team.players"
                            :key="player.userId"
                            class="flex items-center gap-2 rounded-md bg-muted/40 px-3 py-2 text-sm"
                        >
                            <span
                                class="flex h-6 w-6 items-center justify-center rounded-full bg-muted text-xs font-medium text-foreground"
                            >
                                {{ player.username.charAt(0).toUpperCase() }}
                            </span>
                            <span
                                class="font-medium"
                                :class="{
                                    'text-foreground':
                                        player.userId === currentPlayer?.userId,
                                }"
                            >
                                {{ player.username }}
                                <span
                                    v-if="player.userId === currentPlayer?.userId"
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
                        v-if="team.players.length === 0"
                        class="py-8 text-center text-sm text-muted-foreground"
                    >
                        Пока никого нет
                    </p>

                    <p v-if="joinForm.errors.team" class="mb-3 text-sm text-destructive">
                        {{ joinForm.errors.team }}
                    </p>

                    <Button
                        v-if="!isJoined && joinForm.username.trim()"
                        class="w-full"
                        :disabled="
                            joinForm.processing ||
                            (team.maxPlayers !== null &&
                                team.players.length >= team.maxPlayers)
                        "
                        @click="joinTeam(team.number)"
                    >
                        <template
                            v-if="
                                team.maxPlayers !== null &&
                                team.players.length >= team.maxPlayers
                            "
                        >
                            Команда заполнена
                        </template>
                        <template v-else> Присоединиться </template>
                    </Button>
                </CardContent>
            </Card>

            <Card
                v-if="canAddTeam"
                class="w-full max-w-sm cursor-pointer border-dashed transition-colors hover:bg-muted/40"
                @click="createTeam"
            >
                <CardContent
                    class="flex min-h-[280px] flex-col items-center justify-center gap-3"
                >
                    <div
                        class="flex h-12 w-12 items-center justify-center rounded-full bg-muted text-2xl font-semibold"
                    >
                        +
                    </div>
                    <div class="text-center">
                        <p class="font-medium">Добавить команду</p>
                        <p class="text-sm text-muted-foreground">
                            Лимит: {{ gameInfo.max_teams }}
                        </p>
                    </div>
                    <p
                        v-if="createTeamForm.errors.teams"
                        class="text-sm text-destructive"
                    >
                        {{ createTeamForm.errors.teams }}
                    </p>
                </CardContent>
            </Card>
        </div>
    </IgruliLayout>
</template>
