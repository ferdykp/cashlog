<script setup>
import { useNotificationStore } from "../stores/notification";
import { AlertTriangle, Check, Info, X, X as Close } from "@lucide/vue";

const notificationStore = useNotificationStore();
</script>

<template>
    <div
        class="toast-container fixed bottom-5 right-5 z-50 flex flex-col gap-2 max-w-sm w-full px-4 pointer-events-none"
        aria-live="polite"
        aria-relevant="additions text"
        aria-atomic="false"
    >
        <TransitionGroup
            name="toast"
            tag="div"
            class="flex flex-col gap-2 w-full"
        >
            <div
                v-for="toast in notificationStore.notifications"
                :key="toast.id"
                class="toast-card pointer-events-auto p-4 rounded-2xl border flex items-start justify-between gap-3 text-xs md:text-sm"
                :class="{
                    'is-success': toast.type === 'success',
                    'is-error': toast.type === 'error',
                    'is-warning': toast.type === 'warning',
                    'is-info': toast.type === 'info',
                }"
            >
                <div class="flex items-center gap-2">
                    <Check v-if="toast.type === 'success'" class="toast-icon" aria-hidden="true" />
                    <X v-else-if="toast.type === 'error'" class="toast-icon" aria-hidden="true" />
                    <AlertTriangle v-else-if="toast.type === 'warning'" class="toast-icon" aria-hidden="true" />
                    <Info v-else class="toast-icon" aria-hidden="true" />
                    <span>{{ toast.message }}</span>
                </div>

                <button
                    @click="notificationStore.remove(toast.id)"
                    class="toast-close ml-2"
                    aria-label="Tutup notifikasi"
                >
                    <Close aria-hidden="true" />
                </button>
            </div>
        </TransitionGroup>
    </div>
</template>

<style scoped>
.toast-enter-from {
    opacity: 0;
    transform: translateY(10px);
}
.toast-leave-to {
    opacity: 0;
    transform: translateY(-5px);
}
.toast-enter-active,
.toast-leave-active {
    transition: opacity 220ms var(--ease-premium), transform 220ms var(--ease-premium);
}
.toast-card { color:var(--ink-soft);background:var(--surface-raised);border-color:var(--line-strong);box-shadow:var(--shadow-card);backdrop-filter:blur(16px) saturate(1.25);-webkit-backdrop-filter:blur(16px) saturate(1.25); }
.toast-card.is-success { color:var(--positive);border-color:color-mix(in srgb,var(--positive) 28%,var(--line)); }
.toast-card.is-error { color:var(--negative);border-color:color-mix(in srgb,var(--negative) 28%,var(--line)); }
.toast-card.is-warning { color:var(--warning);border-color:color-mix(in srgb,var(--warning) 28%,var(--line)); }
.toast-card.is-info { color:var(--cyan);border-color:color-mix(in srgb,var(--cyan) 28%,var(--line)); }
.toast-icon { width: 18px; height: 18px; flex: none; }
.toast-close { display: grid; place-items: center; width: 36px; height: 36px; flex: none; border: 0; border-radius: 7px; background: transparent; color: inherit; }
.toast-close svg { width: 16px; height: 16px; }
@media (prefers-reduced-motion: reduce) {
    .toast-enter-active, .toast-leave-active { transition: none; }
}
</style>
