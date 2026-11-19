<script setup>
import { onMounted, onUnmounted, ref } from "vue";
import { useRouter } from "vue-router";
import AuthLayout from "@/components/AuthLayout.vue";
import api from "@/lib/axios";
import { useAuthStore } from "@/stores/auth";
import { Mail, ShieldCheck } from "@lucide/vue";

const router = useRouter();
const authStore = useAuthStore();
const code = ref("");
const email = ref("");
const purpose = ref("register");
const loading = ref(false);
const resending = ref(false);
const errorMessage = ref("");
const cooldown = ref(60);
let cooldownInterval;

function startCooldown() {
    cooldown.value = 60;
    clearInterval(cooldownInterval);
    cooldownInterval = setInterval(() => {
        if (cooldown.value > 0) cooldown.value -= 1;
        else clearInterval(cooldownInterval);
    }, 1000);
}

onMounted(async () => {
    try {
        const { data } = await api.get("/api/auth/google/pending");
        email.value = data.email;
        purpose.value = data.purpose;
        startCooldown();
    } catch (error) {
        errorMessage.value = error.response?.data?.message || "Sesi verifikasi tidak tersedia. Mulai kembali dari halaman login atau Pengaturan.";
    }
});

onUnmounted(() => clearInterval(cooldownInterval));

async function handleVerify() {
    if (loading.value || code.value.length !== 6) return;
    loading.value = true;
    errorMessage.value = "";
    try {
        const { data } = await api.post("/api/auth/google/otp/verify", { code: code.value });
        authStore.user = data.data.user;
        await router.replace(purpose.value === "link" ? "/settings" : "/dashboard");
    } catch (error) {
        errorMessage.value = error.response?.data?.errors?.code?.[0] || error.response?.data?.message || "Verifikasi gagal. Coba lagi.";
    } finally {
        loading.value = false;
    }
}

async function resendCode() {
    if (resending.value || cooldown.value > 0) return;
    resending.value = true;
    errorMessage.value = "";
    try {
        await api.post("/api/auth/google/otp/resend");
        startCooldown();
    } catch (error) {
        errorMessage.value = error.response?.data?.message || "Kode baru belum bisa dikirim.";
        if (error.response?.status === 410) {
            await router.replace("/login");
        }
    } finally {
        resending.value = false;
    }
}
</script>

<template>
    <AuthLayout>
        <div class="space-y-6">
            <div class="space-y-3 text-center">
                <div class="mx-auto w-12 h-12 rounded-full bg-indigo-50 text-indigo-600 flex items-center justify-center">
                    <ShieldCheck class="w-6 h-6" />
                </div>
                <h1 class="font-display text-2xl md:text-3xl font-bold text-ink-900">Verifikasi email</h1>
                <p class="text-ink-600 text-sm">
                    Masukkan kode 6 digit yang kami kirim ke
                    <strong v-if="email" class="text-ink-900">{{ email }}</strong>
                    <span v-else>email Google Anda</span>.
                    Kode berlaku selama 10 menit.
                </p>
            </div>

            <div v-if="purpose === 'link'" class="p-3 bg-indigo-50 border border-indigo-100 text-indigo-800 text-xs rounded-md">
                Email Google sama dengan email akun Anda. Verifikasi ini akan menghubungkan keduanya untuk login berikutnya.
            </div>

            <div v-if="errorMessage" role="alert" class="p-3 bg-expense-100 border border-expense-600/20 text-expense-600 text-xs rounded-sm">
                {{ errorMessage }}
            </div>

            <form @submit.prevent="handleVerify" class="space-y-4">
                <div class="space-y-1.5">
                    <label for="google-otp" class="block text-xs font-medium text-ink-600">Kode verifikasi</label>
                    <div class="relative flex items-center">
                        <Mail class="absolute left-3 w-4 h-4 text-ink-600 pointer-events-none" />
                        <input
                            id="google-otp"
                            v-model.trim="code"
                            inputmode="numeric"
                            autocomplete="one-time-code"
                            pattern="[0-9]{6}"
                            maxlength="6"
                            required
                            aria-describedby="otp-help"
                            placeholder="123456"
                            class="w-full h-12 pl-10 pr-3 border border-line-200 rounded-md bg-paper-50 focus:bg-paper-0 focus:border-indigo-600 focus:outline-none text-center tracking-[0.35em] text-lg font-semibold text-ink-900"
                        />
                    </div>
                    <p id="otp-help" class="text-xs text-ink-600">Kode hanya dapat digunakan satu kali.</p>
                </div>

                <button type="submit" :disabled="loading || code.length !== 6" class="w-full min-h-11 bg-indigo-600 text-white font-medium text-sm rounded-md hover:bg-indigo-700 transition-colors disabled:opacity-50 flex items-center justify-center">
                    {{ loading ? "Memverifikasi..." : "Verifikasi dan lanjutkan" }}
                </button>
            </form>

            <p class="text-center text-xs text-ink-600">
                Belum menerima kode?
                <button type="button" :disabled="cooldown > 0 || resending" @click="resendCode" class="text-indigo-600 font-semibold disabled:text-ink-300 disabled:cursor-not-allowed underline-offset-2 hover:underline">
                    {{ resending ? "Mengirim..." : cooldown > 0 ? `Kirim ulang dalam ${cooldown} dtk` : "Kirim ulang" }}
                </button>
            </p>
        </div>
    </AuthLayout>
</template>
