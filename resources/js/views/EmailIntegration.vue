<script setup>
import { computed, onMounted, reactive, ref } from "vue";
import { useRoute } from "vue-router";
import {
    AlertCircle,
    ArrowLeft,
    Check,
    CheckCircle2,
    Clock3,
    ExternalLink,
    Loader2,
    Mail,
    RefreshCw,
    ShieldCheck,
    Unplug,
} from "@lucide/vue";
import api from "@/lib/axios";
import { useNotificationStore } from "@/stores/notification";

const route = useRoute();
const notify = useNotificationStore();
const loading = ref(true);
const busyId = ref(null);
const error = ref("");
const status = ref({
    connections: [],
    pending_count: 0,
    configured: false,
    push_enabled: false,
});
const imports = ref([]);
const accounts = ref([]);
const drafts = reactive({});
const mappingForm = reactive({
    connection_id: "",
    bank_code: "bca",
    account_id: "",
});

const bankOptions = [
    { value: "bca", label: "BCA" },
    { value: "mandiri", label: "Mandiri" },
    { value: "blu", label: "blu" },
    { value: "seabank", label: "SeaBank" },
];
const activeConnections = computed(() => status.value.connections || []);

function messageFromError(err) {
    return (
        err?.response?.data?.message ||
        err?.message ||
        "Terjadi kesalahan. Coba lagi."
    );
}

function currency(value) {
    const amount = Number(value || 0);
    return new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        maximumFractionDigits: 0,
    }).format(amount);
}

function providerLabel(provider) {
    return (
        {
            bca: "BCA",
            blu: "blu",
            mandiri: "Mandiri",
            seabank: "SeaBank",
            other: "Lainnya",
        }[provider] || ""
    );
}

function dateLabel(value) {
    if (!value) return "Tanggal belum terbaca";
    return new Intl.DateTimeFormat("id-ID", { dateStyle: "medium" }).format(
        new Date(`${value}T00:00:00`),
    );
}

function draftFor(item) {
    if (!drafts[item.id]) {
        const existing = activeConnections.value
            .find(
                (connection) =>
                    Number(connection.id) === Number(item.connection_id),
            )
            ?.mappings?.find((mapping) => mapping.bank_code === item.bank_code);
        drafts[item.id] = {
            account_id: existing?.account_id ? String(existing.account_id) : "",
            type: item.type || "",
            amount: item.amount || "",
            date: item.date || "",
            note: item.note || "",
        };
    }
    return drafts[item.id];
}

async function load() {
    loading.value = true;
    error.value = "";
    try {
        const [statusResponse, importResponse, accountResponse] =
            await Promise.all([
                api.get("/api/integrations/email"),
                api.get("/api/integrations/email/imports"),
                api.get("/api/accounts"),
            ]);
        status.value = statusResponse.data?.data || status.value;
        imports.value = importResponse.data?.data || [];
        accounts.value = (accountResponse.data?.data || []).filter(
            (account) => !account.is_archived,
        );
        if (!mappingForm.connection_id && activeConnections.value.length)
            mappingForm.connection_id = String(activeConnections.value[0].id);
    } catch (err) {
        error.value = messageFromError(err);
    } finally {
        loading.value = false;
    }
}

async function saveMapping(connectionId, bankCode, accountId) {
    await api.put(`/api/integrations/email/${connectionId}/mappings`, {
        bank_code: bankCode,
        account_id: Number(accountId),
    });
}

async function addMapping() {
    if (!mappingForm.connection_id || !mappingForm.account_id) return;
    busyId.value = "mapping";
    try {
        await saveMapping(
            mappingForm.connection_id,
            mappingForm.bank_code,
            mappingForm.account_id,
        );
        notify.notify({
            message: "Pemetaan akun email tersimpan.",
            type: "success",
        });
        await load();
    } catch (err) {
        notify.notify({ message: messageFromError(err), type: "error" });
    } finally {
        busyId.value = null;
    }
}

async function confirmImport(item) {
    const draft = draftFor(item);
    if (!draft.account_id || !draft.type || !draft.amount || !draft.date) {
        notify.notify({
            message: "Lengkapi akun, jenis, nominal, dan tanggal transaksi.",
            type: "error",
        });
        return;
    }
    busyId.value = item.id;
    try {
        await api.post(`/api/integrations/email/imports/${item.id}/confirm`, {
            account_id: Number(draft.account_id),
            type: draft.type,
            amount: draft.amount,
            date: draft.date,
            note: draft.note,
        });
        notify.notify({
            message: "Transaksi berhasil dicatat.",
            type: "success",
        });
        await load();
    } catch (err) {
        notify.notify({ message: messageFromError(err), type: "error" });
    } finally {
        busyId.value = null;
    }
}

async function ignoreImport(item) {
    busyId.value = item.id;
    try {
        await api.post(`/api/integrations/email/imports/${item.id}/ignore`);
        notify.notify({ message: "Email diabaikan.", type: "success" });
        await load();
    } catch (err) {
        notify.notify({ message: messageFromError(err), type: "error" });
    } finally {
        busyId.value = null;
    }
}

async function disconnect(connection) {
    if (
        !window.confirm(
            `Putuskan koneksi ${connection.email}? Transaksi yang sudah dicatat tetap tersimpan.`,
        )
    )
        return;
    busyId.value = `disconnect-${connection.id}`;
    try {
        await api.delete(`/api/integrations/email/${connection.id}`);
        notify.notify({ message: "Koneksi email diputus.", type: "success" });
        await load();
    } catch (err) {
        notify.notify({ message: messageFromError(err), type: "error" });
    } finally {
        busyId.value = null;
    }
}

function refresh() {
    load();
}

onMounted(async () => {
    await load();
    if (route.query.email === "connected")
        notify.notify({
            message: "Gmail berhasil dihubungkan.",
            type: "success",
        });
    if (
        ["failed", "invalid-state", "refresh-token-required"].includes(
            route.query.email,
        )
    ) {
        notify.notify({
            message:
                "Gmail belum dapat dihubungkan. Periksa konfigurasi OAuth lalu coba lagi.",
            type: "error",
        });
    }
});
</script>

<template>
    <div class="app-page page-shell space-y-6 font-body">
        <div class="flex items-start justify-between gap-4">
            <div>
                <router-link
                    to="/settings"
                    class="inline-flex items-center gap-1 text-xs font-semibold text-ink-500 hover:text-violet-700 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-violet-600"
                >
                    <ArrowLeft class="w-4 h-4" /> Pengaturan
                </router-link>
                <h1
                    class="mt-3 font-display text-2xl md:text-3xl font-bold text-ink-900"
                >
                    Integrasi email
                </h1>
                <p class="mt-1 text-sm text-ink-600">
                    Hubungkan Gmail pribadi untuk mengimpor notifikasi transaksi
                    ke antrean tinjauan.
                </p>
            </div>
            <button
                type="button"
                @click="refresh"
                :disabled="loading"
                class="min-h-11 min-w-11 rounded-xl border border-line-200 bg-paper-0 text-ink-600 flex items-center justify-center hover:bg-violet-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-violet-600 disabled:opacity-50"
                aria-label="Muat ulang status email"
            >
                <RefreshCw
                    class="w-4 h-4"
                    :class="loading ? 'animate-spin' : ''"
                />
            </button>
        </div>

        <section
            class="rounded-2xl border border-sky-200 bg-sky-50 p-4 sm:p-5 flex gap-3"
            aria-label="Informasi izin Gmail"
        >
            <ShieldCheck class="w-5 h-5 text-sky-700 shrink-0 mt-0.5" />
            <div>
                <h2 class="font-semibold text-sm text-ink-900">
                    Izin hanya untuk membaca
                </h2>
                <p class="text-xs sm:text-sm text-ink-600 mt-1 leading-relaxed">
                    Ledger membaca email dari pengirim bank yang diizinkan untuk
                    menemukan transaksi. Isi email mentah tidak disimpan. Semua
                    hasil masuk ke tinjauan sebelum saldo berubah. Gmail yang
                    dihubungkan boleh berbeda dari email login Ledger.
                </p>
            </div>
        </section>

        <div
            v-if="error"
            role="alert"
            class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700 flex gap-2"
        >
            <AlertCircle class="w-5 h-5 shrink-0" /> {{ error }}
        </div>

        <section
            class="bg-paper-0 border border-line-200 rounded-3xl p-5 sm:p-6 shadow-soft space-y-4"
        >
            <div
                class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3"
            >
                <div>
                    <h2 class="font-display font-bold text-base text-ink-900">
                        Kotak masuk terhubung
                    </h2>
                    <p class="text-xs text-ink-500 mt-1">
                        {{ activeConnections.length }} Gmail terhubung ·
                        {{ status.pending_count || 0 }} transaksi menunggu
                        tinjauan
                    </p>
                </div>
                <a
                    v-if="status.configured"
                    href="/auth/gmail/redirect"
                    class="min-h-11 px-4 rounded-xl bg-violet-600 hover:bg-violet-700 text-white text-sm font-bold inline-flex items-center justify-center gap-2 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-violet-600"
                >
                    <Mail class="w-4 h-4" /> Hubungkan Gmail
                    <ExternalLink class="w-3.5 h-3.5" />
                </a>
                <span
                    v-else
                    class="text-xs text-amber-700 bg-amber-50 border border-amber-200 rounded-xl px-3 py-2"
                    >Integrasi Gmail belum tersedia. Admin perlu menyelesaikan
                    verifikasi dan konfigurasi keamanan lebih dulu.</span
                >
            </div>

            <div
                v-if="loading"
                class="py-8 flex justify-center text-violet-600"
            >
                <Loader2 class="w-6 h-6 animate-spin" />
            </div>
            <div
                v-else-if="!activeConnections.length"
                class="rounded-2xl border border-dashed border-line-200 bg-paper-50 p-6 text-center"
            >
                <Mail class="w-8 h-8 text-ink-300 mx-auto" />
                <p class="text-sm font-semibold text-ink-800 mt-3">
                    Belum ada Gmail yang terhubung
                </p>
                <p class="text-xs text-ink-500 mt-1">
                    Hubungkan alamat yang menerima notifikasi transaksi dari
                    bank.
                </p>
            </div>
            <article
                v-for="connection in activeConnections"
                :key="connection.id"
                class="rounded-2xl border border-line-200 p-4 sm:p-5 space-y-4"
            >
                <div
                    class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3"
                >
                    <div class="flex items-center gap-3 min-w-0">
                        <div
                            class="w-10 h-10 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center shrink-0"
                        >
                            <Mail class="w-5 h-5" />
                        </div>
                        <div class="min-w-0">
                            <p class="text-sm font-bold text-ink-900 truncate">
                                {{ connection.email }}
                            </p>
                            <p
                                class="text-xs mt-0.5"
                                :class="
                                    connection.status === 'connected'
                                        ? 'text-emerald-700'
                                        : 'text-amber-700'
                                "
                            >
                                {{
                                    connection.status === "connected"
                                        ? "Terhubung"
                                        : connection.status === "reauthorize"
                                          ? "Perlu hubungkan ulang"
                                          : "Sinkronisasi bermasalah"
                                }}
                                <span v-if="connection.last_sync_at">
                                    · terakhir
                                    {{
                                        new Date(
                                            connection.last_sync_at,
                                        ).toLocaleString("id-ID")
                                    }}</span
                                >
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 self-end sm:self-auto">
                        <a
                            v-if="connection.status === 'reauthorize'"
                            href="/auth/gmail/redirect"
                            class="min-h-10 px-3 rounded-lg bg-amber-100 text-amber-800 text-xs font-bold flex items-center"
                            >Hubungkan ulang</a
                        >
                        <button
                            type="button"
                            @click="disconnect(connection)"
                            :disabled="busyId === `disconnect-${connection.id}`"
                            class="min-h-10 px-3 rounded-lg border border-line-200 text-ink-500 hover:text-rose-700 hover:border-rose-200 text-xs font-semibold flex items-center gap-1.5 disabled:opacity-50"
                        >
                            <Unplug class="w-4 h-4" /> Putuskan
                        </button>
                    </div>
                </div>
                <p
                    v-if="connection.last_error"
                    class="text-xs text-amber-800 bg-amber-50 border border-amber-100 rounded-lg p-3"
                >
                    {{ connection.last_error }}
                </p>
                <div
                    v-if="connection.mappings?.length"
                    class="space-y-2 border-t border-line-100 pt-4"
                >
                    <p
                        class="text-[11px] font-bold uppercase tracking-wide text-ink-400"
                    >
                        Pemetaan akun
                    </p>
                    <div
                        v-for="mapping in connection.mappings"
                        :key="mapping.id"
                        class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 rounded-xl bg-paper-50 px-3 py-3"
                    >
                        <p class="text-xs font-semibold text-ink-800">
                            {{ mapping.bank_code.toUpperCase() }}
                            → {{ mapping.account_name || "Akun dihapus" }}
                        </p>
                    </div>
                </div>
            </article>
        </section>

        <section
            v-if="activeConnections.length"
            class="bg-paper-0 border border-line-200 rounded-3xl p-5 sm:p-6 shadow-soft space-y-4"
        >
            <div>
                <h2 class="font-display font-bold text-base text-ink-900">
                    Hubungkan bank ke wallet Ledger
                </h2>
                <p class="text-xs text-ink-500 mt-1">
                    Pilih penyedia yang dikenali dan wallet Ledger tujuan. Nomor
                    rekening tidak diperlukan; setiap impor tetap masuk ke
                    tinjauan.
                </p>
            </div>
            <form
                class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 items-end"
                @submit.prevent="addMapping"
            >
                <label class="space-y-1.5 text-xs font-semibold text-ink-600">
                    Gmail
                    <select
                        v-model="mappingForm.connection_id"
                        required
                        class="w-full h-11 rounded-xl border border-line-200 bg-paper-0 px-3 text-sm text-ink-900 focus:border-violet-600 focus:outline-none"
                    >
                        <option
                            v-for="connection in activeConnections"
                            :key="connection.id"
                            :value="String(connection.id)"
                        >
                            {{ connection.email }}
                        </option>
                    </select>
                </label>
                <label class="space-y-1.5 text-xs font-semibold text-ink-600">
                    Bank
                    <select
                        v-model="mappingForm.bank_code"
                        required
                        class="w-full h-11 rounded-xl border border-line-200 bg-paper-0 px-3 text-sm text-ink-900 focus:border-violet-600 focus:outline-none"
                    >
                        <option
                            v-for="bank in bankOptions"
                            :key="bank.value"
                            :value="bank.value"
                        >
                            {{ bank.label }}
                        </option>
                    </select>
                </label>
                <label class="space-y-1.5 text-xs font-semibold text-ink-600">
                    Akun Cashlog
                    <select
                        v-model="mappingForm.account_id"
                        required
                        class="w-full h-11 rounded-xl border border-line-200 bg-paper-0 px-3 text-sm text-ink-900 focus:border-violet-600 focus:outline-none"
                    >
                        <option value="" disabled>Pilih akun</option>
                        <option
                            v-for="account in accounts"
                            :key="account.id"
                            :value="String(account.id)"
                        >
                            {{ account.name
                            }}{{
                                providerLabel(account.provider)
                                    ? ` · ${providerLabel(account.provider)}`
                                    : ""
                            }}
                        </option>
                    </select>
                </label>
                <button
                    type="submit"
                    :disabled="busyId === 'mapping' || !accounts.length"
                    class="sm:col-span-2 lg:col-span-3 min-h-11 px-4 rounded-xl border border-violet-200 bg-violet-50 text-violet-800 text-sm font-bold flex items-center justify-center gap-2 hover:bg-violet-100 disabled:opacity-50"
                >
                    <Loader2
                        v-if="busyId === 'mapping'"
                        class="w-4 h-4 animate-spin"
                    /><Check v-else class="w-4 h-4" /> Simpan pemetaan
                </button>
            </form>
        </section>

        <section
            v-if="activeConnections.length"
            class="bg-paper-0 border border-line-200 rounded-3xl p-5 sm:p-6 shadow-soft space-y-4"
        >
            <div class="flex items-center justify-between gap-3">
                <div>
                    <h2 class="font-display font-bold text-base text-ink-900">
                        Perlu ditinjau
                    </h2>
                    <p class="text-xs text-ink-500 mt-1">
                        Pastikan nominal, arah transaksi, tanggal, dan akun
                        benar sebelum saldo berubah.
                    </p>
                </div>
                <span
                    class="min-w-8 h-8 px-2 rounded-full bg-amber-100 text-amber-800 text-xs font-bold flex items-center justify-center"
                    role="status"
                    aria-atomic="true"
                    >{{ imports.length }}</span
                >
            </div>
            <div
                v-if="!imports.length"
                class="rounded-2xl border border-dashed border-line-200 p-6 text-center"
            >
                <CheckCircle2 class="w-7 h-7 text-emerald-600 mx-auto" />
                <p class="text-sm font-semibold text-ink-800 mt-2">
                    Tidak ada transaksi yang menunggu
                </p>
            </div>
            <article
                v-for="item in imports"
                :key="item.id"
                class="rounded-2xl border border-line-200 p-4 sm:p-5 space-y-4"
            >
                <div
                    class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3"
                >
                    <div>
                        <p class="text-sm font-bold text-ink-900">
                            {{ item.bank_name || "Bank belum dikenali" }}
                        </p>
                        <p class="text-xs text-ink-500 mt-1">
                            {{ item.email }} · {{ dateLabel(item.date) }}
                        </p>
                        <p
                            v-if="item.reason"
                            class="text-xs text-amber-800 mt-2 flex items-center gap-1.5"
                        >
                            <AlertCircle class="w-3.5 h-3.5" />{{ item.reason }}
                        </p>
                    </div>
                    <div class="sm:text-right">
                        <p class="text-lg font-bold text-ink-900">
                            {{
                                item.amount
                                    ? currency(item.amount)
                                    : "Nominal belum terbaca"
                            }}
                        </p>
                    </div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <label class="space-y-1 text-xs font-semibold text-ink-600"
                        >Akun
                        <select
                            v-model="draftFor(item).account_id"
                            class="w-full h-11 rounded-xl border border-line-200 bg-paper-0 px-3 text-sm text-ink-900 focus:border-violet-600 focus:outline-none"
                        >
                            <option value="" disabled>Pilih akun</option>
                            <option
                                v-for="account in accounts"
                                :key="account.id"
                                :value="String(account.id)"
                            >
                                {{ account.name }}
                            </option>
                        </select>
                    </label>
                    <label class="space-y-1 text-xs font-semibold text-ink-600"
                        >Jenis
                        <select
                            v-model="draftFor(item).type"
                            class="w-full h-11 rounded-xl border border-line-200 bg-paper-0 px-3 text-sm text-ink-900 focus:border-violet-600 focus:outline-none"
                        >
                            <option value="">Pilih jenis</option>
                            <option value="expense">Pengeluaran</option>
                            <option value="income">Pemasukan</option>
                        </select>
                    </label>
                    <label class="space-y-1 text-xs font-semibold text-ink-600"
                        >Nominal
                        <input
                            v-model="draftFor(item).amount"
                            inputmode="decimal"
                            type="number"
                            min="1"
                            step="0.01"
                            class="w-full h-11 rounded-xl border border-line-200 bg-paper-0 px-3 text-sm text-ink-900 focus:border-violet-600 focus:outline-none"
                        />
                    </label>
                    <label class="space-y-1 text-xs font-semibold text-ink-600"
                        >Tanggal
                        <input
                            v-model="draftFor(item).date"
                            type="date"
                            class="w-full h-11 rounded-xl border border-line-200 bg-paper-0 px-3 text-sm text-ink-900 focus:border-violet-600 focus:outline-none"
                        />
                    </label>
                    <label
                        class="sm:col-span-2 space-y-1 text-xs font-semibold text-ink-600"
                        >Catatan
                        <input
                            v-model="draftFor(item).note"
                            maxlength="500"
                            class="w-full h-11 rounded-xl border border-line-200 bg-paper-0 px-3 text-sm text-ink-900 focus:border-violet-600 focus:outline-none"
                        />
                    </label>
                </div>
                <div
                    class="flex flex-col-reverse sm:flex-row sm:justify-end gap-2 border-t border-line-100 pt-3"
                >
                    <button
                        type="button"
                        @click="ignoreImport(item)"
                        :disabled="busyId === item.id"
                        class="min-h-11 px-4 rounded-xl border border-line-200 text-ink-600 text-sm font-semibold hover:bg-paper-50 disabled:opacity-50"
                    >
                        Abaikan
                    </button>
                    <button
                        type="button"
                        @click="confirmImport(item)"
                        :disabled="busyId === item.id"
                        class="min-h-11 px-4 rounded-xl bg-violet-600 hover:bg-violet-700 text-white text-sm font-bold flex items-center justify-center gap-2 disabled:opacity-50"
                    >
                        <Loader2
                            v-if="busyId === item.id"
                            class="w-4 h-4 animate-spin"
                        /><Check v-else class="w-4 h-4" /> Catat transaksi
                    </button>
                </div>
            </article>
        </section>

        <section
            class="rounded-2xl border border-line-200 bg-paper-50 p-4 text-xs text-ink-500 leading-relaxed flex gap-2"
        >
            <Clock3 class="w-4 h-4 shrink-0 mt-0.5" />
            <p v-if="status.push_enabled">
                Gmail mengirim pemicu sinkronisasi saat ada email baru.
                Pemeriksaan berkala tetap menjadi cadangan; pemberitahuan dapat
                terlambat.
            </p>
            <p v-else>
                Sinkronisasi berjalan setiap 5 menit, jadi pembaruan tidak
                seketika. Push Gmail opsional dan perlu Cloud Pub/Sub.
            </p>
        </section>
    </div>
</template>
