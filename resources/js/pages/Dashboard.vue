<script setup lang="ts">
import { Head, useForm, Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
    CardDescription,
} from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';

interface LobbyItem {
    id: number;
    title: string;
    code: string;
    created_at: string;
}

const props = defineProps<{
    lobbies: LobbyItem[];
}>();

const form = useForm({
    title: '',
});

function createLobby(): void {
    form.post('/lobby', {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
        },
    });
}

function lobbyUrl(code: string): string {
    return `/lobby/${code}`;
}

function copyLink(code: string): void {
    navigator.clipboard.writeText(window.location.origin + lobbyUrl(code));
}
</script>

<template>
    <Head title="Игрули" />

    <div
        class="flex min-h-screen flex-col items-center bg-background p-6 lg:p-8"
    >
        <div class="w-full max-w-2xl">
            <h1
                class="mb-8 text-center text-3xl font-bold tracking-tight text-foreground"
            >
                Игрули
            </h1>

            <!-- Create Lobby -->
            <Card class="mb-6">
                <CardHeader>
                    <CardTitle class="text-xl">Создать лобби</CardTitle>
                    <CardDescription>
                        Создайте игровое лобби и поделитесь ссылкой с друзьями
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <form
                        class="flex flex-col gap-4 sm:flex-row"
                        @submit.prevent="createLobby"
                    >
                        <Input
                            v-model="form.title"
                            placeholder="Название лобби"
                            class="flex-1"
                            :aria-invalid="!!form.errors.title"
                        />
                        <Button
                            type="submit"
                            :disabled="form.processing || !form.title.trim()"
                        >
                            Создать
                        </Button>
                    </form>
                    <p
                        v-if="form.errors.title"
                        class="mt-2 text-sm text-destructive"
                    >
                        {{ form.errors.title }}
                    </p>
                </CardContent>
            </Card>

            <!-- Lobby List -->
            <Card v-if="props.lobbies.length > 0">
                <CardHeader>
                    <CardTitle class="text-xl">Последние лобби</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="space-y-3">
                        <div
                            v-for="lobby in props.lobbies"
                            :key="lobby.id"
                            class="flex items-center justify-between gap-4 rounded-lg border p-4"
                        >
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2">
                                    <h3
                                        class="truncate font-medium text-foreground"
                                    >
                                        {{ lobby.title }}
                                    </h3>
                                    <Badge variant="secondary">
                                        {{ lobby.code }}
                                    </Badge>
                                </div>
                            </div>
                            <div class="flex shrink-0 items-center gap-2">
                                <Button
                                    variant="outline"
                                    size="sm"
                                    @click="copyLink(lobby.code)"
                                >
                                    Копировать ссылку
                                </Button>
                                <Link :href="lobbyUrl(lobby.code)">
                                    <Button size="sm"> Открыть </Button>
                                </Link>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
