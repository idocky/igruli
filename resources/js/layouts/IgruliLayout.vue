<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import { dashboard, login } from '@/routes';
import { edit as editProfile } from '@/routes/profile';

type Props = {
    maxWidthClass?: string;
};

const props = withDefaults(defineProps<Props>(), {
    maxWidthClass: 'max-w-4xl',
});

const page = usePage();
const user = computed(() => page.props.auth.user);
</script>

<template>
    <div class="min-h-screen bg-background">
        <header class="border-b">
            <div
                class="mx-auto flex h-16 w-full items-center justify-between px-6 lg:px-8"
                :class="props.maxWidthClass"
            >
                <Link
                    :href="dashboard()"
                    class="text-lg font-bold tracking-tight text-foreground"
                >
                    IGRULI
                </Link>

                <div class="flex items-center gap-2">
                    <Link v-if="!user" :href="login()">
                        <Button variant="outline">Войти</Button>
                    </Link>
                    <Link v-else :href="editProfile()">
                        <Button variant="outline">Профиль</Button>
                    </Link>
                </div>
            </div>
        </header>

        <main
            class="mx-auto w-full px-6 py-6 lg:px-8 lg:py-8"
            :class="props.maxWidthClass"
        >
            <slot />
        </main>
    </div>
</template>




