<script setup>
import { computed } from "vue";
import { useRoute } from "vue-router";
import { useAuthStore } from "@/stores/auth";
import { useTheme } from "@/composables/useTheme";
import Sidebar from "@/components/Sidebar.vue";
import BrandMark from "@/components/BrandMark.vue";
import { ChevronRight, Moon, Sun } from "@lucide/vue";

const route = useRoute();
const authStore = useAuthStore();
const { isDark, toggleTheme } = useTheme();
const pageTitle = computed(() => {
    const titles = {
        dashboard: "Beranda",
        transactions: "Aktivitas",
        "transactions.create": "Catat transaksi",
        "transactions.edit": "Ubah transaksi",
        accounts: "Dompet & akun",
        "categories.index": "Kategori",
        "budget.index": "Anggaran",
        "goals.index": "Target simpanan",
        "assets.index": "Aset & investasi",
        "collaboration.index": "Keuangan bersama",
        "report.index": "Laporan keuangan",
        "settings.index": "Pengaturan",
        scan: "Pindai bukti",
        bills: "Tagihan rutin",
        "settings.whatsapp": "WhatsApp",
    };
    return titles[route.name] || "Keuangan pribadi";
});
</script>
<template>
    <div class="app-frame">
        <a href="#main-content" class="skip-link">Langsung ke konten</a>
        <Sidebar />
        <div class="app-main-shell">
            <header class="ledger-desktop-header">
                <div class="desktop-context">
                    <BrandMark />
                    <span>Cashlog</span>
                    <ChevronRight aria-hidden="true" />
                    <strong>{{ pageTitle }}</strong>
                </div>
                <div class="desktop-header-actions">
                    <button type="button" class="theme-toggle" @click="toggleTheme" :aria-label="isDark ? 'Beralih ke tema terang' : 'Beralih ke tema gelap'" :title="isDark ? 'Tema terang' : 'Tema gelap'">
                        <Sun v-if="isDark" aria-hidden="true" />
                        <Moon v-else aria-hidden="true" />
                    </button>
                    <span class="desktop-profile-name">{{ authStore.user?.name || "Akun Anda" }}</span>
                    <router-link to="/settings" class="desktop-profile" :aria-label="`Pengaturan ${authStore.user?.name || 'akun'}`">
                        {{ (authStore.user?.name || "A").charAt(0).toUpperCase() }}
                    </router-link>
                </div>
            </header>
            <main id="main-content" class="app-content" tabindex="-1">
                <div class="app-content-inner">
                    <router-view v-slot="{ Component, route }">
                        <transition name="content-page" mode="out-in" appear>
                            <component
                                :is="Component"
                                :key="
                                    ['goals.index', 'assets.index'].includes(
                                        route.name,
                                    )
                                        ? 'financial-workspace'
                                        : route.path
                                "
                            />
                        </transition>
                    </router-view>
                </div>
            </main>
        </div>
    </div>
</template>
