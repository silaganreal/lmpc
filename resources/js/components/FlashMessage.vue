<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

interface Flash {
    success?: string | null;
    error?: string | null;
}

interface PageProps {
    [key: string]: unknown;
    flash?: Flash;
}

const page = usePage<PageProps>();

const visible = ref(false);

const successMessage = computed(() => page.props.flash?.success ?? null);
const errorMessage = computed(() => page.props.flash?.error ?? null);

let timeout: ReturnType<typeof setTimeout> | null = null;

watch(
    [successMessage, errorMessage],
    ([success, error]) => {
        if (!success && !error) {
            visible.value = false;
            return;
        }

        visible.value = true;

        if (timeout) {
            clearTimeout(timeout);
        }

        timeout = setTimeout(() => {
            visible.value = false;
        }, 5000);
    },
    {
        immediate: true,
    },
);

const close = () => {
    visible.value = false;

    if (timeout) {
        clearTimeout(timeout);
    }
};
</script>

<template>
    <Transition
        enter-active-class="transition duration-300 ease-out"
        enter-from-class="translate-x-4 opacity-0"
        enter-to-class="translate-x-0 opacity-100"
        leave-active-class="transition duration-200 ease-in"
        leave-from-class="translate-x-0 opacity-100"
        leave-to-class="translate-x-4 opacity-0"
    >
        <div
            v-if="visible && (successMessage || errorMessage)"
            class="fixed top-6 right-6 z-50 w-[calc(100%-3rem)] max-w-md"
        >
            <!-- Success -->
            <div
                v-if="successMessage"
                class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 shadow-lg dark:border-green-900/50 dark:bg-green-950/50"
            >
                <div class="flex items-start gap-3">
                    <div
                        class="mt-0.5 flex size-6 shrink-0 items-center justify-center rounded-full bg-green-600 text-sm text-white"
                    >
                        ✓
                    </div>

                    <div class="min-w-0 flex-1">
                        <p
                            class="font-medium text-green-800 dark:text-green-300"
                        >
                            Success
                        </p>

                        <p
                            class="mt-0.5 text-sm text-green-700 dark:text-green-400"
                        >
                            {{ successMessage }}
                        </p>
                    </div>

                    <button
                        type="button"
                        class="shrink-0 text-green-700/60 transition hover:text-green-900 dark:text-green-400/60 dark:hover:text-green-300"
                        aria-label="Close notification"
                        @click="close"
                    >
                        ×
                    </button>
                </div>
            </div>

            <!-- Error -->
            <div
                v-else-if="errorMessage"
                class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 shadow-lg dark:border-red-900/50 dark:bg-red-950/50"
            >
                <div class="flex items-start gap-3">
                    <div
                        class="mt-0.5 flex size-6 shrink-0 items-center justify-center rounded-full bg-red-600 text-sm text-white"
                    >
                        !
                    </div>

                    <div class="min-w-0 flex-1">
                        <p class="font-medium text-red-800 dark:text-red-300">
                            Error
                        </p>

                        <p
                            class="mt-0.5 text-sm text-red-700 dark:text-red-400"
                        >
                            {{ errorMessage }}
                        </p>
                    </div>

                    <button
                        type="button"
                        class="shrink-0 text-red-700/60 transition hover:text-red-900 dark:text-red-400/60 dark:hover:text-red-300"
                        aria-label="Close notification"
                        @click="close"
                    >
                        ×
                    </button>
                </div>
            </div>
        </div>
    </Transition>
</template>
