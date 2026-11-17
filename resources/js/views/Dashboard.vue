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
import { addDecimal, compareDecimal } from "@/utils/decimal";
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
    Sparkles,
} from "@lucide/vue";
import api from "@/lib/axios";

const accountStore = useAccountStore();
const authStore = useAuthStore();
const budgetStore = useBudgetStore();

const { cashAvailableTotal, savingsCashTotal } = storeToRefs(accountStore);
const {
    budgets,
    isLoading: isLoadingBudget,
    totalBudgetLimit,
    totalBudgetSpent,
    totalBudgetPercentage,
} = storeToRefs(budgetStore);

const recentTransactions = ref([]);
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
function privateTxMoney(tx) {
    if (!balanceVisible.value) return "Rp ••••••";
    return `${tx.type === "income" ? "+" : "-"}${formatRupiah(Number(tx.amount || 0))}`;
}
const monthlySavings = computed(
    () => monthlyIncome.value - monthlyExpense.value,
);
const investmentAssets = computed(() =>
    addDecimal(
        netWorth.value.investment_market_value || "0.00",
        netWorth.value.investment_book_value_fallback || "0.00",
    ),
);
const monthlyReport = ref({ income: 0, expense: 0, categories: [] });
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
function getGoalIcon(iconName) {
    return goalIconMap[iconName] || Target;
}

function getCurrentMonthStr() {
    // Format sama seperti currentMonth di Budget.vue: localMonth()
    return localMonth();
}
const currentMonthStr = computed(() => getCurrentMonthStr());

// 1. Total Pemasukan Bulan Ini (real, difilter bulan berjalan)
const monthlyIncome = computed(() => Number(monthlyReport.value.income));
const monthlyExpense = computed(() => Number(monthlyReport.value.expense));

// 3. Sisa Anggaran bulanan — REAL, dari budget store (bukan dihitung ulang dari transaksi,
// karena totalBudgetSpent di store sudah dihitung per kategori yang di-budget-kan)
const hasBudgetSet = computed(() => budgets.value.length > 0);
const remainingBudget = computed(() => {
    const remaining = totalBudgetLimit.value - totalBudgetSpent.value;
    return remaining < 0 ? 0 : remaining;
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
    return {
        ...funding,
        progress_percent: funding.funding_coverage_percent ?? 0,
        funding_coverage: funding.funding_coverage ?? "0.00",
    };
});

// Apex chart config: Arus Kas 6 Bulan Terakhir
const cashFlowSeries = computed(() => [
    { name: "Pemasukan", data: cashFlowIncome.value },
    { name: "Pengeluaran", data: cashFlowExpense.value },
]);

const cashFlowChartOptions = computed(() => ({
    chart: {
        type: "area",
        toolbar: { show: false },
        fontFamily: "inherit",
        parentHeightOffset: 0,
    },
    dataLabels: { enabled: false },
    stroke: { curve: "smooth", width: 2.5 },
    colors: ["#10B981", "#EF4444"],
    fill: { type: "gradient", gradient: { opacityFrom: 0.35, opacityTo: 0 } },
    grid: { borderColor: "#EDEBF3", strokeDashArray: 4 },
    legend: { show: false },
    xaxis: {
        categories: cashFlowMonths.value.map((m) => m.label),
        labels: {
            style: { fontSize: "11px", fontWeight: 700, colors: "#8B8698" },
        },
        axisBorder: { show: false },
        axisTicks: { show: false },
    },
    yaxis: {
        labels: {
            style: { fontSize: "11px", fontWeight: 600, colors: "#8B8698" },
            formatter: (val) => formatRupiah(val),
        },
    },
    tooltip: { y: { formatter: (val) => formatRupiah(val) } },
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
    <div class="app-page bank-dashboard">
        <header class="bank-topbar">
            <div>
                <p class="bank-kicker">
                    {{
                        new Date().toLocaleDateString("id-ID", {
                            weekday: "long",
                            day: "numeric",
                            month: "long",
                        })
                    }}
                </p>
                <h1>Selamat datang, {{ authStore.user?.name || "User" }}.</h1>
                <p class="bank-subtitle">Ringkasan keuanganmu hari ini.</p>
            </div>
            <div class="bank-header-actions">
                <router-link
                    to="/scan"
                    class="bank-icon-action"
                    title="Pindai bukti"
                    aria-label="Pindai bukti"
                    ><ScanLine class="w-5 h-5"
                /></router-link>
                <router-link
                    to="/transactions/create"
                    class="bank-primary-action"
                    ><Plus class="w-4 h-4" /> Transaksi Baru</router-link
                >
            </div>
        </header>

        <section
            v-if="!accountStore.isLoading && accountStore.accounts.length === 0"
            class="onboarding-card"
        >
            <div>
                <h2>Mulai dari dompet pertama Anda</h2>
                <p>
                    Tambahkan uang tunai, rekening, atau e-wallet beserta saldo
                    awalnya. Setelah itu, catat transaksi harian Anda.
                </p>
            </div>
            <router-link to="/accounts" class="primary-button shrink-0"
                ><Plus class="w-4 h-4" />Tambah dompet</router-link
            >
        </section>
        <section class="bank-hero-grid">
            <article class="bank-balance-card">
                <div class="bank-balance-top">
                    <div>
                        <span class="bank-card-label">SALDO HARIAN</span
                        ><span class="bank-live-pill"
                            ><i></i> Akun operasional</span
                        >
                    </div>
                    <button
                        class="bank-eye"
                        @click="toggleBalanceVisibility"
                        :aria-label="
                            balanceVisible
                                ? 'Sembunyikan nominal'
                                : 'Tampilkan nominal'
                        "
                        :title="
                            balanceVisible
                                ? 'Sembunyikan nominal'
                                : 'Tampilkan nominal'
                        "
                    >
                        <Eye v-if="balanceVisible" class="w-5 h-5" /><EyeOff
                            v-else
                            class="w-5 h-5"
                        />
                    </button>
                </div>
                <div
                    class="bank-main-balance"
                    :class="{ 'bank-masked': !balanceVisible }"
                >
                    <AnimatedMoney
                        :value="cashAvailableTotal"
                        :masked="!balanceVisible"
                    />
                </div>
                <div class="bank-balance-meta">
                    <span
                        >Saldo untuk kebutuhan sehari-hari, di luar
                        tabungan</span
                    ><router-link to="/accounts"
                        >Kelola akun <ChevronRight class="w-4 h-4"
                    /></router-link>
                </div>
                <div class="bank-quick-actions">
                    <router-link to="/transactions/create?type=income"
                        ><span><ArrowDownLeft /></span
                        ><b>Pemasukan</b></router-link
                    >
                    <router-link to="/transactions/create?type=expense"
                        ><span><ArrowUpRight /></span
                        ><b>Pengeluaran</b></router-link
                    >
                    <router-link to="/transactions/create?type=transfer"
                        ><span><Send /></span><b>Transfer</b></router-link
                    >
                    <router-link to="/scan"
                        ><span><ScanLine /></span
                        ><b>Pindai bukti</b></router-link
                    >
                </div>
            </article>

            <div class="bank-metrics">
                <article class="bank-metric-card income">
                    <div class="bank-metric-head">
                        <span><ArrowDownLeft /></span><small>Bulan ini</small>
                    </div>
                    <p>Pemasukan</p>
                    <strong
                        ><AnimatedMoney
                            :value="monthlyIncome"
                            :masked="!balanceVisible"
                    /></strong>
                    <div class="bank-metric-foot">
                        Dana masuk bulan berjalan
                    </div>
                </article>
                <article class="bank-metric-card expense">
                    <div class="bank-metric-head">
                        <span><ArrowUpRight /></span><small>Bulan ini</small>
                    </div>
                    <p>Pengeluaran</p>
                    <strong
                        ><AnimatedMoney
                            :value="monthlyExpense"
                            :masked="!balanceVisible"
                    /></strong>
                    <div class="bank-metric-foot">
                        Total belanja bulan berjalan
                    </div>
                </article>
                <article class="bank-metric-card savings">
                    <div class="bank-metric-head">
                        <span><Sparkles /></span><small>Net cash</small>
                    </div>
                    <p>Selisih Kas</p>
                    <strong
                        ><AnimatedMoney
                            :value="monthlySavings"
                            :masked="!balanceVisible"
                    /></strong>
                    <div class="bank-metric-foot">
                        Pemasukan dikurangi pengeluaran
                    </div>
                </article>
                <article class="bank-metric-card savings">
                    <div class="bank-metric-head">
                        <span><Target /></span><small>Kas disisihkan</small>
                    </div>
                    <p>Tabungan kas</p>
                    <strong
                        ><AnimatedMoney
                            :value="savingsCashTotal"
                            :masked="!balanceVisible"
                    /></strong>
                    <div class="bank-metric-foot">
                        Rekening tabungan dan dana yang dialokasikan ke target
                    </div>
                </article>
                <article class="bank-metric-card wealth">
                    <div class="bank-metric-head">
                        <span><Wallet /></span><small>Emas + RDPU</small>
                    </div>
                    <p>Aset investasi</p>
                    <strong
                        ><AnimatedMoney
                            :value="investmentAssets"
                            :masked="!balanceVisible"
                    /></strong>
                    <div class="bank-metric-foot">
                        Nilai aset investasi yang dicatat
                        <template
                            v-if="
                                compareDecimal(
                                    netWorth.investment_in_transit,
                                    '0.00',
                                ) > 0
                            "
                        >
                            ·
                            {{
                                privateMoney(netWorth.investment_in_transit)
                            }}
                            sedang diproses
                        </template>
                    </div>
                </article>
            </div>
        </section>

        <section class="bank-content-grid">
            <article ref="chartPanel" class="bank-panel bank-chart-panel">
                <div class="bank-panel-head">
                    <div>
                        <span class="bank-section-kicker">ANALITIK</span>
                        <h2>Arus Kas</h2>
                        <p>Pergerakan uang 6 bulan terakhir</p>
                    </div>
                    <router-link to="/report" class="bank-text-link"
                        >Laporan lengkap <ChevronRight
                    /></router-link>
                </div>
                <div class="bank-chart-legend">
                    <span><i class="income-dot"></i>Pemasukan</span
                    ><span><i class="expense-dot"></i>Pengeluaran</span>
                </div>
                <div v-if="isLoadingCashFlow" class="bank-loading">
                    Memuat arus kas...
                </div>
                <VueApexCharts
                    v-else-if="chartVisible"
                    type="area"
                    height="285"
                    :options="cashFlowChartOptions"
                    :series="cashFlowSeries"
                />
                <div
                    v-else
                    class="bank-chart-placeholder"
                    aria-hidden="true"
                ></div>
            </article>

            <aside ref="planningPanel" class="bank-side-stack">
                <article class="bank-panel bank-budget">
                    <div class="bank-panel-head compact">
                        <div>
                            <span class="bank-section-kicker">PERENCANAAN</span>
                            <h2>Anggaran bulanan</h2>
                        </div>
                        <Wallet />
                    </div>
                    <div v-if="isLoadingBudget" class="bank-loading small">
                        Memuat budget...
                    </div>
                    <template v-else-if="hasBudgetSet">
                        <strong
                            ><AnimatedMoney
                                :value="remainingBudget"
                                :masked="!balanceVisible"
                        /></strong>
                        <p>
                            tersisa dari
                            <AnimatedMoney
                                :value="totalBudgetLimit"
                                :masked="!balanceVisible"
                            />
                        </p>
                        <div class="bank-progress">
                            <i
                                :style="{
                                    width: `${Math.min(100, totalBudgetPercentage)}%`,
                                }"
                            ></i>
                        </div>
                        <div class="bank-progress-label">
                            <span>{{ totalBudgetPercentage }}% terpakai</span
                            ><router-link to="/budget">Kelola</router-link>
                        </div>
                    </template>
                    <div v-else class="bank-empty-compact">
                        <p>Belum ada anggaran untuk bulan ini.</p>
                        <router-link to="/budget"
                            >Atur anggaran <ChevronRight
                        /></router-link>
                    </div>
                </article>

                <article class="bank-panel bank-goal">
                    <div class="bank-panel-head compact">
                        <div>
                            <span class="bank-section-kicker">TARGET</span>
                            <h2>Target utama</h2>
                        </div>
                        <Target />
                    </div>
                    <div v-if="isLoadingGoals" class="bank-loading small">
                        Memuat target...
                    </div>
                    <div
                        v-else-if="goalSummaryError"
                        class="bank-empty-compact"
                    >
                        <p>Target belum dapat dimuat.</p>
                    </div>
                    <template v-else-if="activeGoal"
                        ><div class="bank-goal-row">
                            <span class="bank-goal-icon"
                                ><component :is="getGoalIcon(activeGoal.icon)"
                            /></span>
                            <div>
                                <b>{{ activeGoal.name }}</b>
                                <p>
                                    {{ activeGoal.progress_percent }}% tercapai
                                </p>
                            </div>
                        </div>
                        <div class="bank-progress">
                            <i
                                :style="{
                                    width: `${Math.min(100, activeGoal.progress_percent || 0)}%`,
                                }"
                            ></i></div
                    ></template>
                    <div v-else class="bank-empty-compact">
                        <p>Belum ada target tabungan.</p>
                        <router-link to="/goals"
                            >Buat target <ChevronRight
                        /></router-link>
                    </div>
                </article>
            </aside>
        </section>

        <section class="bank-bottom-grid">
            <article ref="activityPanel" class="bank-panel bank-transactions">
                <div class="bank-panel-head">
                    <div>
                        <span class="bank-section-kicker">AKTIVITAS</span>
                        <h2>Transaksi Terakhir</h2>
                    </div>
                    <router-link to="/transactions" class="bank-text-link"
                        >Lihat semua <ChevronRight
                    /></router-link>
                </div>
                <div v-if="isLoadingTransactions" class="bank-loading">
                    Memuat transaksi...
                </div>
                <div
                    v-else-if="recentTransactions.length === 0"
                    class="bank-empty-compact roomy"
                >
                    <p>
                        Belum ada transaksi. Mulai catat aktivitas keuanganmu.
                    </p>
                    <router-link to="/transactions/create"
                        >Tambah transaksi <ChevronRight
                    /></router-link>
                </div>
                <div v-else class="bank-tx-list">
                    <div
                        v-for="tx in recentTransactions"
                        :key="tx.id"
                        class="bank-tx-row"
                    >
                        <span class="bank-tx-icon" :class="tx.type"
                            ><component
                                :is="getCategoryIcon(tx.category?.name)"
                        /></span>
                        <div class="bank-tx-main">
                            <b>{{
                                tx.note || tx.category?.name || "Transaksi"
                            }}</b
                            ><span
                                >{{ tx.category?.name || "Umum" }} ·
                                {{ tx.date }}</span
                            >
                        </div>
                        <strong :class="tx.type">{{
                            privateTxMoney(tx)
                        }}</strong>
                    </div>
                </div>
            </article>

            <article class="bank-panel bank-insight">
                <span class="bank-section-kicker">INSIGHT BULAN INI</span>
                <div class="bank-insight-icon"><Sparkles /></div>
                <h2 v-if="topSpendingCategory">
                    Pengeluaran terbesar ada di {{ topSpendingCategory.name }}
                </h2>
                <h2 v-else>Mulai bangun pola keuanganmu</h2>
                <p v-if="topSpendingCategory">
                    Kamu sudah mengeluarkan
                    <b>{{ privateMoney(topSpendingCategory.amount) }}</b> pada
                    kategori ini.
                </p>
                <p v-else>
                    Tambahkan transaksi agar Ledger dapat menampilkan insight
                    yang lebih berguna.
                </p>
                <router-link to="/report"
                    >Lihat analisis <ArrowUpRight
                /></router-link>
            </article>
        </section>
    </div>
</template>
