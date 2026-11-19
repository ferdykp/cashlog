<script setup>
import AuthLayout from "@/components/AuthLayout.vue";
import { ref, computed } from "vue";
import { useRouter, useRoute } from "vue-router";
import { useAuthStore } from "../stores/auth";
import { useNotificationStore } from "../stores/notification";
import { Mail, Lock, Eye, EyeOff } from "@lucide/vue";

const router = useRouter();
const route = useRoute();
const authStore = useAuthStore();
const notificationStore = useNotificationStore();

const showPassword = ref(false);

const form = ref({
    email: "",
    password: "",
});

const isLoading = ref(false);
const errorMessage = ref("");
const googleMessage = computed(() => {
    const messages = {
        "manual-account": "Email ini sudah memiliki akun manual. Masuk dengan email dan kata sandi, lalu hubungkan Google dari Pengaturan.",
        "email-mismatch": "Email Google tidak sama dengan email akun aplikasi.",
        "email-failed": "Kode verifikasi gagal dikirim. Coba lagi sebentar lagi.",
        "not-configured": "Login Google belum tersedia. Konfigurasi Google OAuth belum lengkap.",
        "login-required": "Masuk ke akun manual terlebih dahulu sebelum menghubungkan Google.",
        failed: "Login Google gagal. Silakan coba lagi.",
        cancelled: "Proses Google dibatalkan.",
        "invalid-state": "Sesi Google kedaluwarsa. Silakan mulai lagi.",
    };
    return messages[route.query.google] || "";
});

async function handleSubmit() {
    if (isLoading.value) return;
    isLoading.value = true;
    errorMessage.value = "";

    try {
        await authStore.login(form.value);
        const redirect = route.query.redirect;
        await router.push(
            typeof redirect === "string" &&
                redirect.startsWith("/") &&
                !redirect.startsWith("//")
                ? redirect
                : "/dashboard",
        );
    } catch (err) {
        const msg =
            err.response?.data?.message ||
            (!err.response
                ? "Tidak dapat menghubungi server. Periksa koneksi lalu coba lagi."
                : "Belum berhasil masuk. Silakan coba lagi.");
        errorMessage.value = msg;

        notificationStore.notify({
            message: msg,
            type: "error",
        });
    } finally {
        isLoading.value = false;
    }
}
</script>

<template>
    <AuthLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="space-y-4 text-center">
                <h1
                    class="font-display text-2xl md:text-3xl font-bold text-ink-900"
                >
                    Selamat datang kembali
                </h1>
                <p class="text-ink-600 font-medium text-md">
                    Masuk untuk melihat keuangan dan melanjutkan rencana Anda.
                </p>
            </div>

            <div
                v-if="route.query.reset === 'success'"
                class="p-3 bg-emerald-50 border border-emerald-200 text-emerald-700 text-xs rounded-sm"
            >
                Kata sandi berhasil direset. Silakan masuk dengan kata sandi
                baru.
            </div>

            <div
                v-if="googleMessage"
                role="status"
                class="p-3 bg-amber-50 border border-amber-200 text-amber-800 text-xs rounded-sm"
            >
                {{ googleMessage }}
            </div>

            <!-- Alert Error -->
            <div
                v-if="errorMessage"
                class="p-3 bg-expense-100 border border-expense-600/20 text-expense-600 text-xs rounded-sm flex items-start gap-2"
            >
                <span>{{ errorMessage }}</span>
            </div>

            <!-- Form Login -->
            <form @submit.prevent="handleSubmit" class="space-y-6">
                <!-- Input Email -->
                <div class="space-y-1.5">
                    <label
                        for="email"
                        class="block text-xs font-medium text-ink-600"
                        >Alamat Email</label
                    >
                    <div class="relative flex items-center">
                        <Mail
                            class="absolute left-3 w-4 h-4 text-ink-600 pointer-events-none"
                        />
                        <input
                            id="email"
                            autocomplete="email"
                            v-model="form.email"
                            type="email"
                            required
                            placeholder="nama@email.com"
                            class="w-full h-10 pl-9 pr-3 border border-line-200 rounded-sm bg-paper-50 focus:bg-paper-0 focus:border-indigo-600 focus:outline-none text-sm text-ink-900 transition-colors"
                        />
                    </div>
                </div>

                <!-- Input kata sandi dengan Link Lupa kata sandi & Toggle Visibility -->
                <div class="space-y-1.5">
                    <div class="flex items-center justify-between">
                        <label
                            for="password"
                            class="block text-xs font-medium text-ink-600"
                            >Kata sandi</label
                        >
                        <router-link
                            to="/forgot-password"
                            class="text-xs font-medium text-indigo-600 hover:underline"
                        >
                            Lupa kata sandi?
                        </router-link>
                    </div>
                    <div class="relative flex items-center">
                        <Lock
                            class="absolute left-3 w-4 h-4 text-ink-600 pointer-events-none"
                        />
                        <input
                            id="password"
                            autocomplete="current-password"
                            v-model="form.password"
                            :type="showPassword ? 'text' : 'password'"
                            required
                            placeholder="••••••••"
                            class="w-full h-10 pl-9 pr-10 border border-line-200 rounded-sm bg-paper-50 focus:bg-paper-0 focus:border-indigo-600 focus:outline-none text-sm text-ink-900 transition-colors"
                        />
                        <button
                            type="button"
                            @click="showPassword = !showPassword"
                            :aria-label="
                                showPassword
                                    ? 'Sembunyikan kata sandi'
                                    : 'Tampilkan kata sandi'
                            "
                            class="absolute right-3 text-ink-600 hover:text-ink-900 focus:outline-none cursor-pointer"
                        >
                            <EyeOff v-if="showPassword" class="w-4 h-4" />
                            <Eye v-else class="w-4 h-4" />
                        </button>
                    </div>
                </div>

                <!-- Submit Button -->
                <button
                    type="submit"
                    :disabled="isLoading"
                    class="w-full h-10 bg-indigo-600 text-white font-medium text-sm rounded-md hover:bg-indigo-600/90 transition-colors disabled:opacity-50 cursor-pointer flex items-center justify-center"
                >
                    <span v-if="!isLoading">Masuk</span>
                    <span v-else class="text-xs">Memproses...</span>
                </button>
            </form>

            <div class="space-y-4">
                <div class="flex items-center gap-3 text-xs text-ink-300">
                    <span class="h-px flex-1 bg-line-200" />
                    <span>atau</span>
                    <span class="h-px flex-1 bg-line-200" />
                </div>
                <a
                    href="/auth/google/redirect?intent=login"
                    class="w-full min-h-11 border border-line-200 rounded-md bg-paper-0 text-ink-900 font-medium text-sm hover:bg-paper-50 transition-colors flex items-center justify-center gap-3 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"
                >
                    <svg aria-hidden="true" viewBox="0 0 48 48" class="w-5 h-5">
                        <path fill="#4285F4" d="M43.6 24.5c0-1.4-.1-2.8-.4-4.1H24v7.8h11a9.4 9.4 0 0 1-4.1 6.2v5.1h6.7c3.9-3.6 6-8.8 6-15z" />
                        <path fill="#34A853" d="M24 44c5.5 0 10.1-1.8 13.5-4.8l-6.7-5.1c-1.8 1.2-4 1.9-6.8 1.9-5.2 0-9.7-3.5-11.3-8.2H5.8v5.2A20 20 0 0 0 24 44z" />
                        <path fill="#FBBC05" d="M12.7 27.8a12 12 0 0 1 0-7.6V15H5.8a20 20 0 0 0 0 18z" />
                        <path fill="#EA4335" d="M24 12.1c3 0 5.7 1 7.8 3.1l5.9-5.9C34.1 6.1 29.5 4 24 4A20 20 0 0 0 5.8 15l6.9 5.2c1.6-4.7 6.1-8.1 11.3-8.1z" />
                    </svg>
                    Lanjutkan dengan Google
                </a>
            </div>

            <!-- Footer -->
            <div
                class="pt-4 border-t border-line-200 text-center text-xs text-ink-600"
            >
                Belum memiliki akun?
                <router-link
                    to="/register"
                    class="text-indigo-600 font-medium hover:underline"
                >
                    Daftar sekarang
                </router-link>
            </div>
        </div>
    </AuthLayout>
</template>
