<script setup>
import { computed } from "vue";
import { useRoute } from "vue-router";
import { useAuthStore } from "@/stores/auth";
import Sidebar from "@/components/Sidebar.vue";
import BrandMark from "@/components/BrandMark.vue";
import { ChevronRight } from "@lucide/vue";

const route = useRoute();
const authStore = useAuthStore();
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
