<script setup>
import { ref, watch, computed } from "vue";
import { useAuthStore } from "@/stores/auth";
import { useRoute, useRouter } from "vue-router";
import { useTheme } from "@/composables/useTheme";
import BrandMark from "@/components/BrandMark.vue";
import {
    LayoutDashboard, Wallet, PiggyBank, Target, BarChart3, History,
    Plus, ScanLine, X, ReceiptText, ChevronRight, Tags, UsersRound,
    MessageCircle,
    Activity, House, ArrowDownLeft, ArrowUpRight,
    ArrowLeftRight, Camera, Moon, Sun,
} from "@lucide/vue";

const auth = useAuthStore();
const route = useRoute();
const router = useRouter();
const { isDark, toggleTheme } = useTheme();
const composer = ref(false);
const sections = [
    { label: "Keuangan", items: [
        { name: "Beranda", path: "/dashboard", icon: LayoutDashboard },
        { name: "Aktivitas", path: "/transactions", icon: History },
        { name: "Dompet & akun", path: "/accounts", icon: Wallet },
        { name: "Kategori", path: "/categories", icon: Tags },
        { name: "Laporan", path: "/report", icon: BarChart3 },
    ] },
    { label: "Rencana", items: [
        { name: "Anggaran", path: "/budget", icon: PiggyBank },
        { name: "Target tabungan", path: "/goals", icon: Target },
        { name: "Investasi", path: "/investasi", icon: Wallet },
        { name: "Tagihan", path: "/bills", icon: ReceiptText },
    ] },
    { label: "Lainnya", items: [
        { name: "Pindai bukti", path: "/scan", icon: ScanLine },
        { name: "Keuangan bersama", path: "/kolaborasi", icon: UsersRound },
        { name: "WhatsApp", path: "/settings/whatsapp", icon: MessageCircle },
    ] },
];
const active = (path) => path === "/settings"
    ? route.path === path
    : route.path === path || route.path.startsWith(path + "/");
const inPlan = computed(() => ["/budget", "/goals", "/investasi", "/bills"].some(active));
const title = computed(() => {
    if (route.name === "dashboard") return "Beranda";
    if (route.path.includes("transactions/create") || route.path.includes("/edit")) return "Catat transaksi";
    for (const section of sections) {
        const item = section.items.find((entry) => active(entry.path));
        if (item) return item.name;
    }
    if (route.path.includes("transactions")) return "Catat transaksi";
    return "Ledger";
});
function openTransaction(type) {
    composer.value = false;
    router.push({ path: "/transactions/create", query: { type } });
}
watch(() => route.path, () => { composer.value = false; });
</script>

<template>
    <header class="ledger-mobile-header lg:hidden">
        <router-link to="/dashboard" class="mobile-brand" aria-label="Ledger, beranda">
            <BrandMark /><span class="mobile-brand-title">{{ title }}</span>
        </router-link>
        <div class="mobile-header-actions">
            <button type="button" class="theme-toggle" @click="toggleTheme" :aria-label="isDark ? 'Beralih ke tema terang' : 'Beralih ke tema gelap'" :title="isDark ? 'Tema terang' : 'Tema gelap'">
                <Sun v-if="isDark" aria-hidden="true" />
                <Moon v-else aria-hidden="true" />
            </button>
            <router-link to="/settings" class="mobile-profile" :aria-label="`Pengaturan ${auth.user?.name || 'akun'}`">
                {{ (auth.user?.name || "A").charAt(0).toUpperCase() }}
            </router-link>
        </div>
    </header>

    <aside class="ledger-sidebar hidden lg:flex">
        <router-link to="/dashboard" class="brand-lockup"><BrandMark /><span>Ledger<span class="brand-caption">Keuangan pribadi</span></span></router-link>
        <router-link to="/transactions/create" class="primary-button sidebar-create"><Plus class="w-4 h-4" />Catat transaksi</router-link>
        <nav class="sidebar-navigation" aria-label="Navigasi utama">
            <section v-for="section in sections" :key="section.label">
                <p class="nav-section-label">{{ section.label }}</p>
                <router-link v-for="item in section.items" :key="item.path" :to="item.path" class="nav-item" :class="{ 'is-active': active(item.path) }" :aria-current="active(item.path) ? 'page' : undefined">
                    <component :is="item.icon" /><span>{{ item.name }}</span><span v-if="active(item.path)" class="nav-active-dot" />
                </router-link>
            </section>
        </nav>
        <router-link to="/settings" class="sidebar-profile"><span class="profile-initial">{{ (auth.user?.name || "A").charAt(0).toUpperCase() }}</span><span class="min-w-0 flex-1"><strong>{{ auth.user?.name || "Akun Anda" }}</strong><small>Kelola akun</small></span><ChevronRight class="w-4 h-4" /></router-link>
    </aside>

    <nav class="mobile-navigation lg:hidden" aria-label="Navigasi utama">
        <router-link to="/dashboard" :class="{ 'is-active': active('/dashboard') }" :aria-current="active('/dashboard') ? 'page' : undefined"><House /><span>Beranda</span></router-link>
        <router-link to="/transactions" :class="{ 'is-active': active('/transactions') }" :aria-current="active('/transactions') ? 'page' : undefined"><Activity /><span>Aktivitas</span></router-link>
        <button class="mobile-create" @click="composer = true" :aria-expanded="composer" aria-controls="quick-action-sheet"><span class="mobile-create-icon"><Plus /></span><span>Catat</span></button>
        <router-link to="/goals" :class="{ 'is-active': inPlan }" :aria-current="inPlan ? 'page' : undefined"><Target /><span>Rencana</span></router-link>
        <router-link to="/accounts" :class="{ 'is-active': active('/accounts') }" :aria-current="active('/accounts') ? 'page' : undefined"><Wallet /><span>Akun</span></router-link>
    </nav>

    <div v-if="composer" v-dialog="() => (composer = false)" class="dialog-backdrop menu-backdrop quick-action-backdrop lg:hidden" @click.self="composer = false">
        <section id="quick-action-sheet" class="menu-panel quick-action-sheet" aria-labelledby="quick-action-title">
            <div class="sheet-handle" aria-hidden="true"></div>
            <div class="flex items-start justify-between mb-5"><div><p class="eyebrow">TRANSAKSI BARU</p><h2 id="quick-action-title">Apa yang ingin dicatat?</h2><p class="text-sm text-ink-500 mt-1">Pilih aktivitas untuk mulai.</p></div><button @click="composer = false" class="icon-button" aria-label="Tutup"><X class="w-5 h-5" /></button></div>
            <div class="quick-action-grid">
                <button class="quick-action-option expense" @click="openTransaction('expense')"><span><ArrowUpRight /></span><b>Pengeluaran</b><small>Catat uang keluar</small></button>
                <button class="quick-action-option income" @click="openTransaction('income')"><span><ArrowDownLeft /></span><b>Pemasukan</b><small>Catat uang masuk</small></button>
                <button class="quick-action-option transfer" @click="openTransaction('transfer')"><span><ArrowLeftRight /></span><b>Transfer</b><small>Pindah antar akun</small></button>
                <router-link class="quick-action-option scan" to="/scan"><span><Camera /></span><b>Scan struk</b><small>Impor dari bukti</small></router-link>
            </div>
        </section>
    </div>

</template>
