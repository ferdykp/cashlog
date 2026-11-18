<!-- resources/js/views/Dashboard.vue -->
<script setup>
import { localDate, localMonth } from "@/utils/dates";
import {
    ref,
    onMounted,
    onUnmounted,
    computed,
    defineAsyncComponent,
} from "vue";
import { storeToRefs } from "pinia";
import { useAccountStore } from "@/stores/account";
import { useAuthStore } from "@/stores/auth";
import { useBudgetStore } from "@/stores/budget";
import { formatRupiah } from "@/utils/formatters";
import AnimatedMoney from "@/components/AnimatedMoney.vue";
import { addDecimal, compareDecimal, subtractDecimal } from "@/utils/decimal";
const VueApexCharts = defineAsyncComponent(() => import("@/lib/charts"));
import {
    Plus,
    TrendingUp,
    TrendingDown,
    Utensils,
    Car,
    ShoppingBag,
    Briefcase,
    Tag,
    Target,
    Wallet,
    Building2,
    Flame,
    Plane,
    Laptop,
    Home,
    Smartphone,
    Gift,
    Coins,
    Eye,
    EyeOff,
    ArrowUpRight,
    ArrowDownLeft,
    ScanLine,
    Send,
    ChevronRight,
    ChevronDown,
    X,
    Sparkles,
    UsersRound,
    MessageCircle,
    Tags,
    Settings,
} from "@lucide/vue";
import api from "@/lib/axios";

const accountStore = useAccountStore();
const authStore = useAuthStore();
const budgetStore = useBudgetStore();

const { accounts, cashAvailableTotal, savingsCashTotal } = storeToRefs(accountStore);
const {
    budgets,
    isLoading: isLoadingBudget,
    totalBudgetLimit,
    totalBudgetSpent,
    totalBudgetPercentage,
} = storeToRefs(budgetStore);

const recentTransactions = ref([]);
const showAllApps = ref(false);
const transactionsLoaded = ref(false);
const isLoadingTransactions = ref(true);
const netWorth = ref({
    cash_total: "0.00",
    investment_total: "0.00",
    investment_in_transit: "0.00",
    total_assets: "0.00",
});
const goalFundingSummaries = ref([]);
const isLoadingGoals = ref(true);
const goalSummaryError = ref(false);
const balanceVisible = ref(
    localStorage.getItem("ledger:balance-visible") !== "false",
);
const activeWalletIndex = ref(0);
const walletDrag = ref(null);
const walletDragOffset = ref(0);
const walletCards = computed(() => {
    const visibleAccounts = accounts.value.filter(
        (account) => !account.is_archived && ["cash", "bank", "ewallet"].includes(account.type),
    );
    if (!visibleAccounts.length) return [];
    const start = activeWalletIndex.value % visibleAccounts.length;
    return visibleAccounts.map((_, offset) => {
        const accountIndex = (start + offset) % visibleAccounts.length;
        return { ...visibleAccounts[accountIndex], accountIndex, depth: offset };
    });
});
function toggleBalanceVisibility() {
    balanceVisible.value = !balanceVisible.value;
    localStorage.setItem(
        "ledger:balance-visible",
        String(balanceVisible.value),
    );
}
function privateMoney(value) {
    return balanceVisible.value ? formatRupiah(value) : "Rp ••••••••";
}
function getWalletIcon(type) {
    if (type === "bank") return Building2;
    if (type === "ewallet") return Smartphone;
    return Wallet;
}
function getWalletTypeLabel(type) {
    if (type === "bank") return "Rekening bank";
    if (type === "ewallet") return "E-wallet";
    return "Uang tunai";
}
function showWallet(index) {
    const count = walletCards.value.length;
    if (count < 2) return;
    activeWalletIndex.value = (index + count) % count;
}
function moveWallet(direction) {
    showWallet(activeWalletIndex.value + direction);
}
function startWalletSwipe(event) {
    if (walletCards.value.length < 2) return;
    if (event.pointerType === "mouse" && event.button !== 0) return;
    walletDrag.value = { pointerId: event.pointerId, startX: event.clientX };
    walletDragOffset.value = 0;
    event.currentTarget.setPointerCapture?.(event.pointerId);
}
function moveWalletSwipe(event) {
    if (walletDrag.value?.pointerId !== event.pointerId) return;
    const distance = event.clientX - walletDrag.value.startX;
    walletDragOffset.value = Math.max(-128, Math.min(128, distance * 0.88));
}
function endWalletSwipe(event) {
    if (walletDrag.value?.pointerId !== event.pointerId) return;
    const distance = event.clientX - walletDrag.value.startX;
    walletDrag.value = null;
    if (Math.abs(distance) >= 48) moveWallet(distance < 0 ? 1 : -1);
    walletDragOffset.value = 0;
}
function cancelWalletSwipe() {
    walletDrag.value = null;
    walletDragOffset.value = 0;
}
function privateTxMoney(tx) {
    if (!balanceVisible.value) return "Rp ••••••";
    return `${tx.type === "income" ? "+" : "-"}${formatRupiah(tx.amount || "0.00")}`;
}
const monthlySavings = computed(() =>
    subtractDecimal(monthlyIncome.value, monthlyExpense.value),
);
const investmentAssets = computed(() =>
    addDecimal(
        netWorth.value.investment_market_value || "0.00",
        netWorth.value.investment_book_value_fallback || "0.00",
    ),
);
const monthlyReport = ref({ income: "0.00", expense: "0.00", categories: [] });
const cashFlowMonths = ref([]);
const cashFlowIncome = ref([]);
const cashFlowExpense = ref([]);
const isLoadingCashFlow = ref(true);
const chartPanel = ref(null);
const planningPanel = ref(null);
const activityPanel = ref(null);
const chartVisible = ref(false);
let sectionObserver;
const sectionLoaders = new WeakMap();

// Map Ikon untuk Transaksi
const iconMap = {
    makanan: Utensils,
    transportasi: Car,
    belanja: ShoppingBag,
    pendapatan: Briefcase,
    gaji: Briefcase,
};

function getCategoryIcon(categoryName) {
    if (!categoryName) return Tag;
    const key = categoryName.toLowerCase();
    for (const k in iconMap) {
        if (key.includes(k)) return iconMap[k];
    }
    return Tag;
}

// Sama seperti iconMap di Goal.vue, supaya ikon goal konsisten di seluruh app
const goalIconMap = {
    plane: Plane,
    laptop: Laptop,
    car: Car,
    home: Home,
    smartphone: Smartphone,
    gift: Gift,
    coins: Coins,
};
const dashboardShortcuts = [
    { label: "Keuangan bersama", detail: "Atur keuangan bareng", path: "/kolaborasi", icon: UsersRound },
    { label: "Pindai bukti", detail: "Catat dari struk", path: "/scan", icon: ScanLine },
    { label: "WhatsApp", detail: "Catat lewat chat", path: "/settings/whatsapp", icon: MessageCircle },
    { label: "Kategori", detail: "Kelola kategori", path: "/categories", icon: Tags },
    { label: "Pengaturan", detail: "Akun dan preferensi", path: "/settings", icon: Settings },
];
function getGoalIcon(iconName) {
    return goalIconMap[iconName] || Target;
}

function getCurrentMonthStr() {
    // Format sama seperti currentMonth di Budget.vue: localMonth()
    return localMonth();
}
const currentMonthStr = computed(() => getCurrentMonthStr());

// 1. Total Pemasukan Bulan Ini (real, difilter bulan berjalan)
const monthlyIncome = computed(() =>
    String(monthlyReport.value.income ?? "0.00"),
);
const monthlyExpense = computed(() =>
    String(monthlyReport.value.expense ?? "0.00"),
);

// 3. Sisa Anggaran bulanan — REAL, dari budget store (bukan dihitung ulang dari transaksi,
// karena totalBudgetSpent di store sudah dihitung per kategori yang di-budget-kan)
const hasBudgetSet = computed(() => budgets.value.length > 0);
const remainingBudget = computed(() => {
    const remaining = subtractDecimal(
        totalBudgetLimit.value,
        totalBudgetSpent.value,
    );
    return compareDecimal(remaining, "0.00") > 0 ? remaining : "0.00";
});

// 4. Kategori Pengeluaran Terbesar Bulan Ini (real, insight baru)
const topSpendingCategory = computed(
    () => monthlyReport.value.categories[0] || null,
);

// 5. Goal prioritas — REAL, dari goal store (progress_percent & remaining_amount
// sudah dihitung backend, sama seperti priorityGoal di Goal.vue)
const activeGoal = computed(() => {
    const funding = goalFundingSummaries.value[0];
    if (!funding) return null;
    const remaining = subtractDecimal(
        funding.target_amount || "0.00",
        funding.current_amount || "0.00",
    );
    return {
        ...funding,
        progress_percent: funding.savings_progress_percent ?? 0,
        remaining_to_target: compareDecimal(remaining, "0.00") > 0 ? remaining : "0.00",
    };
});

// Apex chart config: Arus Kas 6 Bulan Terakhir
const cashFlowSeries = computed(() => [
    { name: "Pemasukan", data: cashFlowIncome.value },
    { name: "Pengeluaran", data: cashFlowExpense.value },
]);
const hasCashFlowData = computed(
    () => cashFlowIncome.value.some((value) => value > 0) || cashFlowExpense.value.some((value) => value > 0),
);

const cashFlowChartOptions = computed(() => ({
    chart: {
        type: "area",
        toolbar: { show: false },
        fontFamily: "inherit",
        parentHeightOffset: 0,
    },
    dataLabels: { enabled: false },
    stroke: { curve: "smooth", width: 2.5 },
    colors: ["#0DF2A6", "#FF6B6B"],
    fill: { type: "gradient", gradient: { opacityFrom: 0.35, opacityTo: 0 } },
    grid: { borderColor: "rgba(255,255,255,0.10)", strokeDashArray: 3 },
    legend: { show: false },
    xaxis: {
        categories: cashFlowMonths.value.map((m) => m.label),
        labels: {
            style: { fontSize: "11px", fontWeight: 700, colors: "#94A3B8" },
        },
        axisBorder: { show: false },
        axisTicks: { show: false },
    },
    yaxis: {
        labels: {
            style: { fontSize: "11px", fontWeight: 600, colors: "#94A3B8" },
            formatter: (val) => formatRupiah(val),
        },
    },
    tooltip: { theme: "dark", y: { formatter: (val) => formatRupiah(val) } },
}));

function getLastNMonths(n) {
    const months = [];
    const now = new Date();
    for (let i = n - 1; i >= 0; i--) {
        const d = new Date(now.getFullYear(), now.getMonth() - i, 1);
        const monthStr = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, "0")}`;
        const label = d.toLocaleDateString("id-ID", { month: "short" });
        months.push({ monthStr, label });
    }
    return months;
}

let refreshing = false;
let refreshAgain = false;
let cashFlowLoaded = false;
let planningLoaded = false;
let activityLoaded = false;
async function refreshDashboard() {
    if (refreshing) {
        refreshAgain = true;
        return;
    }
    refreshing = true;
    try {
        const requests = [
            accountStore.fetchAccounts(),
            accountStore.fetchAllocationSummary(),
            fetchDashboardSummary(),
            fetchNetWorthData(),
        ];
        if (cashFlowLoaded) requests.push(fetchCashFlowData());
        if (planningLoaded) requests.push(fetchPlanningData());
        if (activityLoaded) requests.push(fetchTransactionsData());
        await Promise.all(requests);
    } finally {
        refreshing = false;
        if (refreshAgain) {
            refreshAgain = false;
            void refreshDashboard();
        }
    }
}

function fetchPlanningData() {
    return Promise.all([
        budgetStore.fetchBudgets(localMonth()),
        fetchGoalFundingSummaries(),
    ]);
}

async function fetchDashboardSummary() {
    try {
        const response = await api.get("/api/reports/dashboard-summary", {
            params: { month: localMonth() },
        });
        monthlyReport.value = response.data.data;
    } catch (err) {
        console.warn("Gagal memuat ringkasan bulan ini:", err.message);
    }
}

async function fetchGoalFundingSummaries() {
    if (goalFundingSummaries.value.length === 0) isLoadingGoals.value = true;
    goalSummaryError.value = false;
    try {
        const response = await api.get("/api/goals/funding-summary");
        goalFundingSummaries.value = Array.isArray(response.data.data)
            ? response.data.data
            : [];
    } catch (err) {
        console.warn("Gagal memuat pendanaan target:", err.message);
        if (goalFundingSummaries.value.length === 0)
            goalSummaryError.value = true;
    } finally {
        isLoadingGoals.value = false;
    }
}

async function fetchNetWorthData() {
    try {
        const response = await api.get("/api/reports/net-worth");
        netWorth.value = response.data.data;
    } catch (err) {
        console.warn("Gagal memuat total aset:", err.message);
    }
}
onMounted(() => {
    void refreshDashboard();
    window.addEventListener("ledger:data-changed", refreshDashboard);

    const loadSection = (element, load) => {
        if (!element) return;
        if (sectionObserver) {
            sectionObserver.observe(element);
            sectionLoaders.set(element, load);
        } else {
            load();
        }
    };

    if ("IntersectionObserver" in window) {
        sectionObserver = new IntersectionObserver(
            (entries) => {
                for (const entry of entries) {
                    if (!entry.isIntersecting) continue;
                    const load = sectionLoaders.get(entry.target);
                    sectionObserver.unobserve(entry.target);
                    load?.();
                }
            },
            { rootMargin: "200px" },
        );
    }

    loadSection(chartPanel.value, () => {
        chartVisible.value = true;
        if (!cashFlowLoaded) {
            cashFlowLoaded = true;
            void fetchCashFlowData();
        }
    });
    loadSection(planningPanel.value, () => {
        if (!planningLoaded) {
            planningLoaded = true;
            void fetchPlanningData();
        }
    });
    loadSection(activityPanel.value, () => {
        if (!activityLoaded) {
            activityLoaded = true;
            void fetchTransactionsData();
        }
    });
});
onUnmounted(() => {
    window.removeEventListener("ledger:data-changed", refreshDashboard);
    sectionObserver?.disconnect();
});

async function fetchTransactionsData() {
    isLoadingTransactions.value = !transactionsLoaded.value;
    try {
        const response = await api.get("/api/transactions?limit=5");
        recentTransactions.value = response.data.data || response.data || [];
    } catch (err) {
        console.warn("Gagal memuat data transaksi:", err.message);
    } finally {
        transactionsLoaded.value = true;
        isLoadingTransactions.value = false;
    }
}

async function fetchCashFlowData() {
    // Keep the current chart visible during refreshes after a transaction change.
    isLoadingCashFlow.value = cashFlowIncome.value.length === 0;
    if (cashFlowMonths.value.length === 0) {
        cashFlowMonths.value = getLastNMonths(6);
    }
    try {
        const res = await api.get("/api/reports/cash-flow", {
            params: { months: 6 },
        });
        const rows = res.data.data || [];
        cashFlowMonths.value = rows.map((r) => ({
            monthStr: r.month,
            label: r.label,
        }));
        cashFlowIncome.value = rows.map((r) => Number(r.income || 0));
        cashFlowExpense.value = rows.map((r) => Number(r.expense || 0));
    } catch (err) {
        console.warn("Gagal memuat data arus kas:", err.message);
        if (cashFlowIncome.value.length === 0) {
            cashFlowIncome.value = cashFlowMonths.value.map(() => 0);
            cashFlowExpense.value = cashFlowMonths.value.map(() => 0);
        }
    } finally {
        isLoadingCashFlow.value = false;
    }
}
</script>

<template>
    <div class="app-page cashlog-home">
        <header class="home-heading">
            <div>
                <p class="home-date">{{ new Date().toLocaleDateString("id-ID", { weekday: "long", day: "numeric", month: "long" }) }}</p>
                <h1>Uangmu, <span>lebih jelas.</span></h1>
                <p class="home-subtitle">Hai {{ authStore.user?.name || "" }}, ini yang perlu kamu tahu hari ini.</p>
            </div>
        </header>

        <section v-if="!accountStore.isLoading && accountStore.accounts.length === 0" class="home-onboarding">
            <div><b>Mulai dari dompet pertamamu</b><p>Tambahkan rekening, e-wallet, atau uang tunai untuk melihat posisi keuangan.</p></div>
            <router-link to="/accounts" class="primary-button"><Plus class="w-4 h-4" />Tambah akun</router-link>
        </section>

        <section class="home-overview" aria-label="Ringkasan keuangan hari ini">
            <div class="home-cash">
                <div class="home-cash-head"><span>Dompetmu</span><button class="home-visibility" @click="toggleBalanceVisibility" :aria-label="balanceVisible ? 'Sembunyikan nominal' : 'Tampilkan nominal'"><Eye v-if="balanceVisible"/><EyeOff v-else/></button></div>

                <div
                    v-if="accountStore.isLoading"
                    class="home-wallet-loading"
                    role="status"
                >Memuat dompet…</div>
                <div
                    v-else-if="walletCards.length"
                    class="home-wallet-deck"
                    :class="{ 'has-multiple-wallets': walletCards.length > 1, 'is-dragging': walletDrag !== null }"
                    role="group"
                    aria-label="Kartu saldo dompet. Geser untuk melihat dompet lain."
                    @pointerdown="startWalletSwipe"
                    @pointermove="moveWalletSwipe"
                    @pointerup="endWalletSwipe"
                    @pointercancel="cancelWalletSwipe"
                    @lostpointercapture="cancelWalletSwipe"
                >
                    <div class="home-wallet-stack">
                        <article
                            v-for="wallet in walletCards"
                            :key="wallet.id"
                            class="home-wallet-card wallet-card"
                            :class="{ 'is-front': wallet.depth === 0, 'is-hidden': wallet.depth > 2 }"
                            :style="{ '--wallet-accent': wallet.color || 'var(--accent)', '--wallet-depth': Math.min(wallet.depth, 3), '--deck-offset': `${Math.min(wallet.depth, 3) * 10}px`, '--deck-zoffset': `${Math.min(wallet.depth, 3) * -8}px`, '--deck-scale': String(1 - Math.min(wallet.depth, 3) * 0.035), '--deck-z': String(4 - Math.min(wallet.depth, 3)), '--deck-drag-x': `${walletDragOffset * (wallet.depth === 0 ? 1 : 0.16)}px` }"
                            :aria-hidden="wallet.depth !== 0"
                        >
                            <span class="home-wallet-card-edge" :style="{ backgroundColor: wallet.color || 'var(--accent)' }"></span>
                            <div class="home-wallet-card-top">
                                <span class="home-wallet-emblem" :style="{ color: wallet.color || 'var(--accent)' }">
                                    <component :is="getWalletIcon(wallet.type)" aria-hidden="true" />
                                </span>
                                <span class="home-wallet-type">{{ getWalletTypeLabel(wallet.type) }}</span>
                                <span class="home-wallet-card-count">{{ String(wallet.accountIndex + 1).padStart(2, '0') }} / {{ String(walletCards.length).padStart(2, '0') }}</span>
                            </div>
                            <div class="home-wallet-card-balance">
                                <span>Saldo {{ wallet.purpose === 'savings' ? 'tabungan' : 'tersedia' }}</span>
                                <strong><AnimatedMoney :value="wallet.balance || '0'" :masked="!balanceVisible" /></strong>
                            </div>
                            <div class="home-wallet-card-bottom">
                                <b>{{ wallet.name }}</b>
                                <span>{{ wallet.purpose === 'savings' ? 'Tabungan' : 'Operasional' }}</span>
                            </div>
                        </article>
                    </div>
                </div>
                <div v-else class="home-wallet-empty">Tambahkan dompet untuk melihat saldo di sini.</div>

                <div class="home-cash-total">
                    <div><span>Kas tersedia</span><strong><AnimatedMoney :value="cashAvailableTotal" :masked="!balanceVisible" /></strong></div>
                    <router-link to="/accounts">Semua akun <ChevronRight /></router-link>
                </div>
                <div class="home-action-row">
                    <router-link to="/transactions/create?type=expense"><ArrowUpRight /><span>Keluar</span></router-link>
                    <router-link to="/transactions/create?type=income"><ArrowDownLeft /><span>Masuk</span></router-link>
                    <router-link to="/transactions/create?type=transfer"><Send /><span>Transfer</span></router-link>
                    <router-link to="/scan"><ScanLine /><span>Pindai</span></router-link>
                </div>
            </div>
            <div class="home-daily-brief">
                <div class="brief-heading"><span class="brief-signal"><Sparkles /></span><div><span>Ringkasan bulan ini</span><small>{{ new Date().toLocaleDateString("id-ID", { month: "long", year: "numeric" }) }}</small></div></div>
                <div class="brief-net"><span>Selisih pemasukan dan pengeluaran</span><strong :class="compareDecimal(monthlySavings, '0') >= 0 ? 'positive' : 'negative'"><AnimatedMoney :value="monthlySavings" :masked="!balanceVisible" /></strong></div>
                <div class="brief-pair"><div><span><i class="brief-dot income-dot"></i>Pemasukan</span><b><AnimatedMoney :value="monthlyIncome" :masked="!balanceVisible" /></b></div><div><span><i class="brief-dot expense-dot"></i>Pengeluaran</span><b><AnimatedMoney :value="monthlyExpense" :masked="!balanceVisible" /></b></div></div>
                <router-link to="/report" class="brief-link">Telusuri arus kas <ArrowUpRight /></router-link>
            </div>
        </section>

        <article ref="activityPanel" class="home-activity home-activity-featured" aria-label="Aktivitas terbaru">
            <div class="home-section-head"><div><span class="home-overline">TERBARU</span><h2>Aktivitas</h2></div><router-link to="/transactions" aria-label="Lihat semua aktivitas">Semua <ChevronRight /></router-link></div>
            <div v-if="isLoadingTransactions" class="home-chart-loading" role="status">Memuat aktivitas…</div>
            <div v-else-if="recentTransactions.length === 0" class="home-empty roomy">Belum ada transaksi.<router-link to="/transactions/create">Catat yang pertama <ArrowUpRight /></router-link></div>
            <div v-else class="home-tx-list"><div v-for="tx in recentTransactions" :key="tx.id" class="home-tx-row"><span class="home-tx-mark" :class="tx.type"><component :is="getCategoryIcon(tx.category?.name)" /></span><div class="home-tx-info"><b>{{ tx.note || tx.category?.name || "Transaksi" }}</b><small>{{ tx.category?.name || "Umum" }} · {{ tx.date }}</small></div><strong :class="tx.type">{{ privateTxMoney(tx) }}</strong></div></div>
        </article>

        <section ref="planningPanel" class="home-plan-grid" aria-label="Rencana keuangan">
            <router-link to="/budget" class="home-budget">
                <div class="home-section-head"><div><span class="home-overline">BATAS BELANJA</span><h2>Anggaran</h2></div><ChevronRight /></div>
                <template v-if="isLoadingBudget"><div class="home-skeleton" aria-label="Memuat anggaran" /></template>
                <template v-else-if="hasBudgetSet"><strong><AnimatedMoney :value="remainingBudget" :masked="!balanceVisible" /></strong><p>tersisa dari batas <AnimatedMoney :value="totalBudgetLimit" :masked="!balanceVisible" /></p><div class="home-meter" role="progressbar" :aria-valuenow="totalBudgetPercentage" aria-valuemin="0" aria-valuemax="100" :aria-label="`${totalBudgetPercentage}% anggaran terpakai`"><i :style="{ transform: `scaleX(${Math.min(100, totalBudgetPercentage) / 100})` }" /></div><small>{{ totalBudgetPercentage }}% digunakan · <AnimatedMoney :value="totalBudgetSpent" :masked="!balanceVisible" /> terpakai</small></template>
                <div v-else class="home-empty">Belum ada batas belanja. <b>Atur anggaran <ArrowUpRight /></b></div>
            </router-link>
            <router-link to="/goals" class="home-goal">
                <div class="home-section-head"><div><span class="home-overline">MOMENTUM MENABUNG</span><h2>Target utama</h2></div><ChevronRight /></div>
                <div v-if="isLoadingGoals" class="home-skeleton" aria-label="Memuat target" />
                <div v-else-if="goalSummaryError" class="home-empty">Target belum dapat dimuat.</div>
                <template v-else-if="activeGoal"><div class="home-goal-name"><span class="goal-glyph"><component :is="getGoalIcon(activeGoal.icon)" /></span><div><b>{{ activeGoal.name }}</b><small><AnimatedMoney :value="activeGoal.current_amount" :masked="!balanceVisible" /> terkumpul dari <AnimatedMoney :value="activeGoal.target_amount" :masked="!balanceVisible" /></small></div></div><div class="home-meter goal-meter" role="progressbar" :aria-valuenow="activeGoal.progress_percent" aria-valuemin="0" aria-valuemax="100" :aria-label="`${activeGoal.progress_percent}% target tercapai`"><i :style="{ transform: `scaleX(${Math.min(100, activeGoal.progress_percent || 0) / 100})` }" /></div><small>{{ activeGoal.progress_percent }}% tercapai · kurang <AnimatedMoney :value="activeGoal.remaining_to_target" :masked="!balanceVisible" /></small></template>
                <div v-else class="home-empty">Belum ada target tabungan. <b>Buat target <ArrowUpRight /></b></div>
            </router-link>
            <router-link to="/investasi" class="home-investments"><span class="home-overline">ASET TERCATAT</span><div class="investment-value"><span>Emas + RDPU</span><Wallet /><strong><AnimatedMoney :value="investmentAssets" :masked="!balanceVisible" /></strong></div><div class="investment-foot"><span>Nilai aset saat ini</span><span v-if="compareDecimal(netWorth.investment_in_transit, '0.00') > 0">{{ privateMoney(netWorth.investment_in_transit) }} diproses</span><span v-else>Kelola portofolio <ArrowUpRight /></span></div></router-link>
        </section>

        <section class="home-apps" aria-labelledby="home-apps-title">
            <div class="home-section-head"><div><span class="home-overline">AKSES CEPAT</span><h2 id="home-apps-title">Aplikasi pilihan</h2></div></div>
            <div class="home-app-grid">
                <router-link v-for="shortcut in dashboardShortcuts.slice(0, 4)" :key="shortcut.path" :to="shortcut.path" class="home-app-tile">
                    <span class="home-app-icon"><component :is="shortcut.icon" aria-hidden="true" /></span>
                    <span class="home-app-copy"><b>{{ shortcut.label }}</b><small>{{ shortcut.detail }}</small></span>
                    <ChevronRight class="home-app-arrow" aria-hidden="true" />
                </router-link>
            </div>
            <button type="button" class="home-apps-more" @click="showAllApps = true" aria-haspopup="dialog">
                <span>Tampilkan semua menu</span><ChevronDown aria-hidden="true" />
            </button>
        </section>

        <Transition name="mobile-sheet">
            <div v-if="showAllApps" v-dialog="() => (showAllApps = false)" class="dialog-backdrop menu-backdrop home-apps-backdrop" @click.self="showAllApps = false">
                <section class="menu-panel home-apps-sheet" role="dialog" aria-modal="true" aria-labelledby="all-apps-title">
                    <div class="sheet-handle" aria-hidden="true"></div>
                    <div class="home-apps-sheet-head">
                        <div><span class="home-overline">CASHLOG</span><h2 id="all-apps-title">Semua menu</h2><p>Pilih fitur yang ingin kamu buka.</p></div>
                        <button type="button" class="icon-button" @click="showAllApps = false" aria-label="Tutup semua menu"><X class="w-5 h-5" /></button>
                    </div>
                    <div class="home-app-grid home-apps-sheet-grid">
                        <router-link v-for="shortcut in dashboardShortcuts" :key="shortcut.path" :to="shortcut.path" class="home-app-tile">
                            <span class="home-app-icon"><component :is="shortcut.icon" aria-hidden="true" /></span>
                            <span class="home-app-copy"><b>{{ shortcut.label }}</b><small>{{ shortcut.detail }}</small></span>
                            <ChevronRight class="home-app-arrow" aria-hidden="true" />
                        </router-link>
                    </div>
                </section>
            </div>
        </Transition>

        <section class="home-cashflow-section">
            <article ref="chartPanel" class="home-cashflow">
                <div class="home-section-head"><div><span class="home-overline">ENAM BULAN TERAKHIR</span><h2>Arus kas</h2><p>Pemasukan dan pengeluaran tercatat</p></div><router-link to="/report" aria-label="Buka laporan arus kas"><ArrowUpRight /></router-link></div>
                <div class="home-chart-legend"><span><i class="income-dot" />Masuk</span><span><i class="expense-dot" />Keluar</span></div>
                <div v-if="isLoadingCashFlow" class="home-chart-loading" role="status">Memuat arus kas…</div>
                <VueApexCharts v-else-if="chartVisible && hasCashFlowData" type="area" height="270" :options="cashFlowChartOptions" :series="cashFlowSeries" />
                <div v-else-if="!isLoadingCashFlow && !hasCashFlowData" class="home-chart-empty"><span>Arus kas belum terbentuk</span><p>Catat pemasukan atau pengeluaran untuk melihat perubahan dari bulan ke bulan.</p><router-link to="/transactions/create">Catat transaksi <ArrowUpRight /></router-link></div>
                <ul v-if="hasCashFlowData" class="sr-only" aria-label="Nilai arus kas per bulan"><li v-for="(month, index) in cashFlowMonths" :key="month.monthStr">{{ month.label }}: pemasukan {{ balanceVisible ? formatRupiah(cashFlowIncome[index]) : 'disembunyikan' }}, pengeluaran {{ balanceVisible ? formatRupiah(cashFlowExpense[index]) : 'disembunyikan' }}.</li></ul>
                <div v-else class="home-chart-placeholder" aria-hidden="true" />
            </article>
        </section>
    </div>
</template>
