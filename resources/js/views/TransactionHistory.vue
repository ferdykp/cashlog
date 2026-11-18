<!-- resources/js/views/TransactionHistory.vue -->
<script setup>
import { localDate, localMonth } from "@/utils/dates";
import { ref, onMounted, onUnmounted, computed, watch } from "vue";
import { useRouter } from "vue-router";
import { storeToRefs } from "pinia";
import { useAccountStore } from "@/stores/account";
import { useCategoryStore } from "@/stores/category";
import { formatRupiah } from "@/utils/formatters";
import {
    Plus,
    Search,
    ChevronDown,
    Utensils,
    Car,
    ShoppingBag,
    Building2,
    Tag,
    X,
    History,
    Trash2,
    Pencil,
    Copy,
    AlertTriangle,
    Loader2,
    ArrowLeftRight,
    CalendarDays,
    ChevronLeft,
    ChevronRight,
    List,
} from "@lucide/vue";
import api from "@/lib/axios";
import { previousMonth } from "@/utils/dates";
import { useNotificationStore } from "@/stores/notification";
import { addDecimal, compareDecimal, subtractDecimal } from "@/utils/decimal";

const router = useRouter();
const accountStore = useAccountStore();
const categoryStore = useCategoryStore();

const { accounts } = storeToRefs(accountStore);
const { categories } = storeToRefs(categoryStore);

const transactions = ref([]);
const page = ref(1),
    lastPage = ref(1);
let requestVersion = 0;
const notify = useNotificationStore();
const isLoading = ref(true);
const viewMode = ref("list");
const calendarMonth = ref(localMonth());
const selectedCalendarDate = ref(localDate());

// Filter & Search State
const searchQuery = ref("");
const selectedTime = ref("all");
const selectedCategory = ref("all");
const selectedAccount = ref("all");
const selectedType = ref("all");

// State Delete Modal
const isDeleteModalOpen = ref(false);
const transactionToDelete = ref(null);
const isDeleting = ref(false);

const iconMap = {
    makanan: Utensils,
    transportasi: Car,
    belanja: ShoppingBag,
    pendapatan: Building2,
};

function getCategoryIcon(categoryName) {
    if (!categoryName) return Tag;
    const key = categoryName.toLowerCase();
    for (const k in iconMap) {
        if (key.includes(k)) return iconMap[k];
    }
    return Tag;
}

onMounted(async () => {
    fetchTransactions();
    if (accounts.value.length === 0) accountStore.fetchAccounts();
    if (categories.value.length === 0) categoryStore.fetchCategories();
});

async function fetchTransactions(append = false) {
    const version = ++requestVersion;
    const requestedPage = append === true ? page.value + 1 : 1;
    isLoading.value = true;
    try {
        const calendarView = viewMode.value === "calendar";
        const params = { page: requestedPage, per_page: calendarView ? 100 : 50 };
        if (searchQuery.value.trim()) params.search = searchQuery.value.trim();
        if (selectedCategory.value !== "all")
            params.category_id = selectedCategory.value;
        if (selectedAccount.value !== "all")
            params.account_id = selectedAccount.value;
        if (selectedType.value !== "all") params.type = selectedType.value;
        if (calendarView) {
            params.month = calendarMonth.value;
        } else {
            if (selectedTime.value === "this_month") params.month = localMonth();
            if (selectedTime.value === "last_month") {
                params.month = previousMonth();
            }
        }
        const res = await api.get("/api/transactions", { params });
        if (version !== requestVersion) return;
        let data = res.data.data || [];
        let responseMeta = res.data.meta;
        if (calendarView) {
            for (let nextPage = 2; nextPage <= responseMeta.last_page; nextPage++) {
                const next = await api.get("/api/transactions", {
                    params: { ...params, page: nextPage },
                });
                if (version !== requestVersion) return;
                data = [...data, ...(next.data.data || [])];
                responseMeta = next.data.meta;
            }
        }
        transactions.value =
            append === true ? [...transactions.value, ...data] : data;
        page.value = responseMeta.current_page;
        lastPage.value = responseMeta.last_page;
    } catch (err) {
        console.warn("Gagal memuat riwayat transaksi:", err.message);
    } finally {
        if (version === requestVersion) isLoading.value = false;
    }
}

let filterTimer;
watch(
    [
        searchQuery,
        selectedTime,
        selectedCategory,
        selectedAccount,
        selectedType,
        viewMode,
        calendarMonth,
    ],
    () => {
        clearTimeout(filterTimer);
        filterTimer = setTimeout(fetchTransactions, 250);
    },
);

// Filtering Logic
const filteredTransactions = computed(() => transactions.value);
onUnmounted(() => {
    clearTimeout(filterTimer);
    requestVersion++;
});

// Group Transactions by Date
const groupedTransactions = computed(() => {
    const groups = {};

    filteredTransactions.value.forEach((tx) => {
        const dateKey = tx.date;
        if (!groups[dateKey]) {
            groups[dateKey] = {
                dateLabel: formatDateLabel(dateKey),
                transactions: [],
                dailyTotal: "0.00",
            };
        }

        groups[dateKey].transactions.push(tx);

        const amount = tx.amount || "0.00";
        if (tx.type === "income") {
            groups[dateKey].dailyTotal = addDecimal(
                groups[dateKey].dailyTotal,
                amount,
            );
        } else if (tx.type === "expense") {
            groups[dateKey].dailyTotal = subtractDecimal(
                groups[dateKey].dailyTotal,
                amount,
            );
        }
    });

    return Object.values(groups).sort(
        (a, b) =>
            new Date(b.transactions[0].date) - new Date(a.transactions[0].date),
    );
});

const calendarYear = computed(() => Number(calendarMonth.value.slice(0, 4)));
const calendarMonthIndex = computed(() => Number(calendarMonth.value.slice(5, 7)) - 1);
const calendarDays = computed(() => {
    const first = new Date(calendarYear.value, calendarMonthIndex.value, 1);
    const daysInMonth = new Date(calendarYear.value, calendarMonthIndex.value + 1, 0).getDate();
    const offset = (first.getDay() + 6) % 7;
    const cells = Array.from({ length: offset }, (_, index) => ({ key: `blank-${index}`, day: null }));
    for (let day = 1; day <= daysInMonth; day++) {
        const date = `${calendarMonth.value}-${String(day).padStart(2, "0")}`;
        const dayTransactions = transactions.value.filter((tx) => tx.date === date);
        const expense = dayTransactions
            .filter((tx) => tx.type === "expense")
            .reduce((total, tx) => addDecimal(total, tx.amount || "0"), "0");
        cells.push({
            key: date,
            day,
            date,
            expense,
            hasIncome: dayTransactions.some((tx) => tx.type === "income"),
            transactions: dayTransactions,
        });
    }
    while (cells.length % 7) cells.push({ key: `blank-${cells.length}`, day: null });
    return cells;
});

const calendarDayMap = computed(() => new Map(calendarDays.value.filter((cell) => cell.day).map((cell) => [cell.date, cell])));
const monthlyExpense = computed(() => calendarDays.value
    .filter((cell) => cell.day)
    .reduce((total, cell) => addDecimal(total, cell.expense || "0"), "0"));
const maxDailyExpense = computed(() => Math.max(0, ...calendarDays.value.map((cell) => Number(cell.expense || 0))));
const calendarScopeDays = computed(() => {
    const daysInMonth = new Date(calendarYear.value, calendarMonthIndex.value + 1, 0).getDate();
    const now = new Date();
    if (calendarYear.value === now.getFullYear() && calendarMonthIndex.value === now.getMonth()) return now.getDate();
    return calendarMonth.value < localMonth(now) ? daysInMonth : 0;
});
const daysWithoutExpenses = computed(() => {
    if (!calendarScopeDays.value) return 0;
    return calendarDays.value.filter((cell) => cell.day && cell.day <= calendarScopeDays.value && Number(cell.expense || 0) === 0).length;
});
const averageDailyExpense = computed(() => calendarScopeDays.value
    ? formatRupiah((Number(monthlyExpense.value) / calendarScopeDays.value).toFixed(0), 0)
    : formatRupiah(0, 0));
const selectedDay = computed(() => calendarDayMap.value.get(selectedCalendarDate.value));
const selectedDayTransactions = computed(() => selectedDay.value?.transactions || []);
const calendarMonthLabel = computed(() => new Date(calendarYear.value, calendarMonthIndex.value, 1)
    .toLocaleDateString("id-ID", { month: "long", year: "numeric" }));

function shiftCalendarMonth(amount) {
    const next = new Date(calendarYear.value, calendarMonthIndex.value + amount, 1);
    calendarMonth.value = localMonth(next);
    selectedCalendarDate.value = calendarMonth.value === localMonth()
        ? localDate()
        : `${calendarMonth.value}-01`;
}

function selectCalendarDate(date) {
    if (date) selectedCalendarDate.value = date;
}

function calendarCellStyle(cell) {
    if (!cell.day || !Number(cell.expense)) return {};
    const intensity = maxDailyExpense.value ? 10 + Math.sqrt(Number(cell.expense) / maxDailyExpense.value) * 26 : 10;
    return { "--calendar-spend-tint": `color-mix(in srgb, var(--negative) ${intensity}%, var(--surface))` };
}

function shortRupiah(amount) {
    const value = Number(amount || 0);
    if (value >= 1_000_000) return `${(value / 1_000_000).toLocaleString("id-ID", { maximumFractionDigits: 1 })} jt`;
    if (value >= 1_000) return `${Math.round(value / 1_000).toLocaleString("id-ID")} rb`;
    return value.toLocaleString("id-ID");
}

function formatCalendarDate(date) {
    return new Date(`${date}T00:00:00`).toLocaleDateString("id-ID", {
        weekday: "long", day: "numeric", month: "long",
    });
}

function formatDateLabel(dateStr) {
    const today = localDate();
    const yesterday = localDate(new Date(Date.now() - 86400000));

    const dateObj = new Date(dateStr);
    const formattedDate = dateObj.toLocaleDateString("id-ID", {
        day: "numeric",
        month: "short",
        year: "numeric",
    });

    if (dateStr === today) return `Hari Ini, ${formattedDate}`;
    if (dateStr === yesterday) return `Kemarin, ${formattedDate}`;
    return formattedDate;
}

async function duplicateTransaction(tx) {
    if (isDeleting.value) return;
    isDeleting.value = true;
    try {
        await api.post("/api/transactions", {
            type: tx.type,
            amount: tx.amount,
            account_id: tx.account_id,
            to_account_id: tx.related_account_id || null,
            category_id: tx.category_id || null,
            date: localDate(),
            note: tx.note
                ? `${tx.note.slice(0, 490)} (salinan)`
                : "Salinan transaksi",
        });
        await fetchTransactions();
        await accountStore.fetchAccounts();
        window.dispatchEvent(new Event("ledger:data-changed"));
    } catch (err) {
        notify.notify({
            message: err.response?.data?.message || "Gagal menyalin transaksi.",
            type: "error",
        });
    } finally {
        isDeleting.value = false;
    }
}

function openDeleteModal(tx) {
    transactionToDelete.value = tx;
    isDeleteModalOpen.value = true;
}

function closeDeleteModal() {
    if (isDeleting.value) return;
    isDeleteModalOpen.value = false;
    transactionToDelete.value = null;
}

async function handleDelete() {
    if (!transactionToDelete.value || isDeleting.value) return;
    isDeleting.value = true;
    try {
        await api.delete(`/api/transactions/${transactionToDelete.value.id}`);
        window.dispatchEvent(new Event("ledger:data-changed"));

        // Hapus dari state lokal
        transactions.value = transactions.value.filter(
            (t) => t.id !== transactionToDelete.value.id,
        );

        // Update saldo dompet di store
        await accountStore.fetchAccounts();

        // Tutup modal
        isDeleteModalOpen.value = false;
        transactionToDelete.value = null;
    } catch (err) {
        notify.notify({
            message:
                err.response?.data?.message || "Gagal menghapus transaksi.",
            type: "error",
        });
    } finally {
        isDeleting.value = false;
    }
}
</script>

<template>
    <div class="app-page page-shell space-y-6 font-body">
        <!-- Header Halaman -->
        <div
            class="flex flex-col sm:flex-row sm:items-center justify-between gap-4"
        >
            <div>
                <h1
                    class="font-display text-2xl md:text-3xl font-bold text-ink-900"
                >
                    Aktivitas
                </h1>
                <p class="text-ink-600 text-sm mt-1">
                    Kelola dan pantau semua aktivitas keuangan Anda.
                </p>
            </div>

            <div class="activity-header-actions">
                <div class="activity-view-switch" role="group" aria-label="Tampilan riwayat transaksi">
                    <button type="button" :aria-pressed="viewMode === 'list'" :class="{ 'is-active': viewMode === 'list' }" @click="viewMode = 'list'">
                        <List class="w-4 h-4" /> <span>Daftar</span>
                    </button>
                    <button type="button" :aria-pressed="viewMode === 'calendar'" :class="{ 'is-active': viewMode === 'calendar' }" @click="viewMode = 'calendar'">
                        <CalendarDays class="w-4 h-4" /> <span>Kalender</span>
                    </button>
                </div>
                <router-link
                    to="/transactions/create"
                    class="inline-flex items-center justify-center gap-2 px-5 py-3 bg-violet-600 text-white rounded-2xl font-bold text-sm shadow-violet btn-bounce self-start sm:self-auto"
                >
                    <Plus class="w-4 h-4 stroke-[2.5]" />
                    <span>Tambah Transaksi</span>
                </router-link>
            </div>
        </div>

        <!-- FILTER & SEARCH BAR -->
        <div
            class="flex flex-col lg:flex-row items-stretch lg:items-center justify-between gap-3"
        >
            <div
                class="chip-scroll-rail flex items-center gap-2 overflow-x-auto no-scrollbar"
            >
                <!-- Filter Waktu -->
                <div v-if="viewMode === 'list'" class="relative shrink-0">
                    <select
                        v-model="selectedTime"
                        aria-label="Filter waktu transaksi"
                        class="appearance-none pl-4 pr-8 py-2 bg-paper-0 border border-violet-600/40 focus:border-violet-600 text-violet-700 rounded-full text-xs font-bold cursor-pointer focus:outline-none"
                    >
                        <option value="all">Semua Waktu</option>
                        <option value="this_month">Bulan Ini</option>
                        <option value="last_month">Bulan Lalu</option>
                    </select>
                    <ChevronDown
                        class="w-3.5 h-3.5 text-violet-600 absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none"
                    />
                </div>

                <!-- Filter Kategori -->
                <div class="relative shrink-0">
                    <select
                        v-model="selectedCategory"
                        aria-label="Filter kategori transaksi"
                        class="appearance-none pl-4 pr-8 py-2 bg-paper-0 border border-line-200 focus:border-violet-600 text-ink-700 rounded-full text-xs font-semibold cursor-pointer focus:outline-none"
                    >
                        <option value="all">Kategori: Semua</option>
                        <option
                            v-for="cat in categories"
                            :key="cat.id"
                            :value="cat.id"
                        >
                            {{ cat.name }}
                        </option>
                    </select>
                    <ChevronDown
                        class="w-3.5 h-3.5 text-ink-400 absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none"
                    />
                </div>

                <!-- Filter Akun -->
                <div class="relative shrink-0">
                    <select
                        v-model="selectedAccount"
                        aria-label="Filter rekening transaksi"
                        class="appearance-none pl-4 pr-8 py-2 bg-paper-0 border border-line-200 focus:border-violet-600 text-ink-700 rounded-full text-xs font-semibold cursor-pointer focus:outline-none"
                    >
                        <option value="all">Akun: Semua</option>
                        <option
                            v-for="acc in accounts"
                            :key="acc.id"
                            :value="acc.id"
                        >
                            {{ acc.name }}
                        </option>
                    </select>
                    <ChevronDown
                        class="w-3.5 h-3.5 text-ink-400 absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none"
                    />
                </div>

                <!-- Filter Jenis Transaksi -->
                <div class="relative shrink-0">
                    <select
                        v-model="selectedType"
                        aria-label="Filter jenis transaksi"
                        class="appearance-none pl-4 pr-8 py-2 bg-paper-0 border border-line-200 focus:border-violet-600 text-ink-700 rounded-full text-xs font-semibold cursor-pointer focus:outline-none"
                    >
                        <option value="all">Jenis Transaksi</option>
                        <option value="expense">Pengeluaran</option>
                        <option value="income">Pemasukan</option>
                        <option value="transfer">Transfer</option>
                    </select>
                    <ChevronDown
                        class="w-3.5 h-3.5 text-ink-400 absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none"
                    />
                </div>
            </div>

            <!-- Search Input Field -->
            <div class="relative min-w-[240px]">
                <Search
                    class="w-4 h-4 text-ink-400 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none"
                />
                <input
                    v-model="searchQuery"
                    type="text"
                    placeholder="Cari transaksi..."
                    class="w-full pl-9 pr-4 h-9 border border-line-200 rounded-xl bg-paper-0 focus:border-violet-600 focus:outline-none text-xs text-ink-900 placeholder:text-ink-300 transition-colors"
                />
                <button
                    v-if="searchQuery"
                    @click="searchQuery = ''"
                    class="absolute right-3 top-1/2 -translate-y-1/2 text-ink-400 hover:text-ink-900 cursor-pointer"
                    aria-label="Hapus pencarian"
                >
                    <X class="w-3.5 h-3.5" />
                </button>
            </div>
        </div>

        <section v-if="viewMode === 'calendar'" class="activity-calendar" aria-label="Kalender aktivitas transaksi">
            <div class="activity-calendar-toolbar">
                <button type="button" class="activity-calendar-nav" aria-label="Bulan sebelumnya" @click="shiftCalendarMonth(-1)">
                    <ChevronLeft class="w-5 h-5" />
                </button>
                <h2>{{ calendarMonthLabel }}</h2>
                <button type="button" class="activity-calendar-nav" aria-label="Bulan berikutnya" @click="shiftCalendarMonth(1)">
                    <ChevronRight class="w-5 h-5" />
                </button>
            </div>

            <div class="activity-calendar-stats" aria-label="Ringkasan pengeluaran bulanan">
                <div><span>Pengeluaran</span><strong>{{ formatRupiah(monthlyExpense, 0) }}</strong></div>
                <div><span>Rata-rata / hari</span><strong>{{ averageDailyExpense }}</strong></div>
                <div><span>Hari tanpa belanja</span><strong>{{ daysWithoutExpenses }} / {{ calendarScopeDays }}</strong></div>
            </div>

            <div v-if="isLoading" class="activity-calendar-loading" role="status">
                <Loader2 class="w-5 h-5 animate-spin" /> Memuat kalender transaksi…
            </div>
            <div v-else class="activity-calendar-grid">
                <div v-for="weekday in ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min']" :key="weekday" class="activity-calendar-weekday">{{ weekday }}</div>
                <template v-for="cell in calendarDays" :key="cell.key">
                    <div v-if="!cell.day" class="activity-calendar-blank" aria-hidden="true"></div>
                    <button
                        v-else
                        type="button"
                        class="activity-calendar-day"
                        :class="{ 'is-selected': selectedCalendarDate === cell.date, 'is-today': cell.date === localDate() }"
                        :style="calendarCellStyle(cell)"
                        :aria-label="`${formatCalendarDate(cell.date)}${Number(cell.expense) ? `, pengeluaran ${formatRupiah(cell.expense, 0)}` : ', tanpa pengeluaran'}${cell.hasIncome ? ', ada pemasukan' : ''}`"
                        :aria-pressed="selectedCalendarDate === cell.date"
                        @click="selectCalendarDate(cell.date)"
                    >
                        <span class="activity-calendar-day-number">{{ cell.day }}</span>
                        <span v-if="cell.hasIncome" class="activity-calendar-income-dot" aria-hidden="true"></span>
                        <span v-if="Number(cell.expense)" class="activity-calendar-day-amount">{{ shortRupiah(cell.expense) }}</span>
                    </button>
                </template>
            </div>

            <div class="activity-calendar-legend">
                <span><i class="activity-calendar-heat-key" aria-hidden="true"></i>Warna lebih pekat menandakan pengeluaran lebih besar</span>
                <span><i class="activity-calendar-income-dot" aria-hidden="true"></i>Ada pemasukan</span>
            </div>

            <div class="activity-calendar-detail">
                <div class="activity-calendar-detail-head">
                    <div><span class="activity-calendar-kicker">RINGKASAN HARI</span><h3>{{ selectedCalendarDate ? formatCalendarDate(selectedCalendarDate) : 'Pilih tanggal' }}</h3></div>
                    <strong v-if="selectedDay && Number(selectedDay.expense)" class="activity-calendar-detail-total">- {{ formatRupiah(selectedDay.expense, 0) }}</strong>
                </div>
                <p v-if="isLoading" class="activity-calendar-empty" role="status">Memuat aktivitas…</p>
                <p v-else-if="selectedDayTransactions.length === 0" class="activity-calendar-empty">Belum ada transaksi pada tanggal ini.</p>
                <router-link
                    v-for="tx in selectedDayTransactions"
                    :key="tx.id"
                    :to="`/transactions/${tx.id}/edit`"
                    class="activity-calendar-transaction"
                >
                    <span class="activity-calendar-transaction-icon" :class="`is-${tx.type}`">
                        <component :is="tx.type === 'transfer' ? ArrowLeftRight : getCategoryIcon(tx.category?.name)" class="w-4 h-4" />
                    </span>
                    <span class="activity-calendar-transaction-copy">
                        <strong>{{ tx.note || tx.category?.name || (tx.type === 'transfer' ? 'Transfer antar dompet' : 'Transaksi') }}</strong>
                        <small>{{ tx.type === 'income' ? 'Pemasukan' : tx.type === 'expense' ? 'Pengeluaran' : 'Transfer' }} · {{ tx.category?.name || tx.account?.name || 'Umum' }}</small>
                    </span>
                    <strong class="activity-calendar-transaction-amount" :class="`is-${tx.type}`">{{ tx.type === 'income' ? '+' : tx.type === 'expense' ? '−' : '' }} {{ formatRupiah(tx.amount, 0) }}</strong>
                </router-link>
            </div>
        </section>

        <!-- LIST TRANSAKSI BERDASARKAN TANGGAL -->
        <div v-else class="space-y-6">
            <p
                v-if="isLoading && transactions.length"
                role="status"
                class="text-xs text-ink-500"
            >
                Memperbarui transaksi…
            </p>
            <template v-if="isLoading && transactions.length === 0">
                <div v-for="i in 3" :key="i" class="space-y-3">
                    <div
                        class="w-32 h-4 bg-base-100 rounded animate-pulse"
                    ></div>
                    <div
                        class="h-16 bg-paper-0 border border-line-200 rounded-2xl animate-pulse"
                    ></div>
                </div>
            </template>

            <template v-else-if="groupedTransactions.length === 0">
                <div
                    class="py-16 text-center bg-paper-0 border border-line-200 rounded-3xl space-y-3 shadow-soft"
                >
                    <History class="w-10 h-10 text-ink-300 mx-auto" />
                    <p class="text-sm font-semibold text-ink-600">
                        Tidak ada transaksi yang cocok dengan pencarian Anda.
                    </p>
                </div>
            </template>

            <template v-else>
                <div
                    v-for="group in groupedTransactions"
                    :key="group.dateLabel"
                    class="space-y-3"
                >
                    <!-- Date Header & Daily Total -->
                    <div class="flex items-center justify-between px-1">
                        <h3 class="font-display font-bold text-sm text-ink-900">
                            {{ group.dateLabel }}
                        </h3>
                        <span
                            class="font-mono-money font-bold text-xs"
                            :class="
                                compareDecimal(group.dailyTotal, '0.00') >= 0
                                    ? 'text-violet-600'
                                    : 'text-rose-500'
                            "
                        >
                            {{
                                compareDecimal(group.dailyTotal, "0.00") >= 0
                                    ? "+ "
                                    : "- "
                            }}
                            {{
                                formatRupiah(group.dailyTotal.replace(/^-/, ""))
                            }}
                        </span>
                    </div>

                    <!-- Transaction Card Item -->
                    <div class="space-y-2">
                        <div
                            v-for="tx in group.transactions"
                            :key="tx.id"
                            class="transaction-row bg-paper-0 border border-line-200/80 rounded-2xl p-4 shadow-soft hover:shadow-card transition-all group"
                        >
                            <div
                                class="transaction-summary flex items-center gap-3.5"
                            >
                                <div
                                    class="w-11 h-11 rounded-2xl flex items-center justify-center shrink-0 shadow-soft"
                                    :class="
                                        tx.type === 'transfer'
                                            ? 'bg-sky-100 text-sky-700'
                                            : tx.type === 'income'
                                              ? 'bg-violet-600 text-white'
                                              : 'bg-rose-500 text-white'
                                    "
                                >
                                    <component
                                        :is="
                                            tx.type === 'transfer'
                                                ? ArrowLeftRight
                                                : getCategoryIcon(
                                                      tx.category?.name,
                                                  )
                                        "
                                        class="w-5 h-5 stroke-[2.2]"
                                    />
                                </div>

                                <div>
                                    <h4
                                        class="font-display font-bold text-sm text-ink-900 group-hover:text-violet-600 transition-colors"
                                    >
                                        {{
                                            tx.note ||
                                            (tx.type === "transfer"
                                                ? "Transfer antar dompet"
                                                : tx.category?.name ||
                                                  "Transaksi")
                                        }}
                                    </h4>
                                    <p class="text-xs text-ink-600 font-medium">
                                        <template v-if="tx.type === 'transfer'">
                                            <span class="text-sky-700"
                                                >Transfer</span
                                            >
                                            <span aria-hidden="true"> · </span>
                                            {{
                                                tx.account?.name ||
                                                "Dompet asal"
                                            }}
                                            <span
                                                class="px-1 text-sky-700"
                                                aria-label="ke"
                                                >→</span
                                            >
                                            {{
                                                tx.related_account?.name ||
                                                "Dompet tujuan"
                                            }}
                                        </template>
                                        <template v-else>
                                            {{ tx.category?.name || "Umum" }} •
                                            {{ tx.account?.name || "Dompet" }}
                                        </template>
                                    </p>
                                </div>
                            </div>

                            <div class="transaction-details">
                                <div
                                    class="font-mono-money font-bold text-sm sm:text-base text-right"
                                    :class="
                                        tx.type === 'transfer'
                                            ? 'text-sky-700'
                                            : tx.type === 'income'
                                              ? 'text-violet-600'
                                              : 'text-rose-500'
                                    "
                                >
                                    {{
                                        tx.type === "transfer"
                                            ? ""
                                            : tx.type === "income"
                                              ? "+ "
                                              : "- "
                                    }}
                                    {{ formatRupiah(tx.amount) }}
                                </div>

                                <div class="transaction-actions">
                                    <router-link
                                        :to="`/transactions/${tx.id}/edit`"
                                        @click.stop
                                        class="p-1.5 text-ink-400 hover:text-violet-600 rounded-lg hover:bg-violet-50"
                                        title="Edit transaksi"
                                        aria-label="Edit transaksi"
                                    >
                                        <Pencil class="w-4 h-4" />
                                    </router-link>
                                    <button
                                        type="button"
                                        @click.stop="duplicateTransaction(tx)"
                                        class="p-1.5 text-ink-400 hover:text-violet-600 rounded-lg hover:bg-violet-50"
                                        title="Duplikat transaksi"
                                        aria-label="Duplikat transaksi"
                                    >
                                        <Copy class="w-4 h-4" />
                                    </button>
                                    <button
                                        type="button"
                                        @click.stop="openDeleteModal(tx)"
                                        class="p-1.5 text-ink-400 hover:text-rose-500 rounded-lg hover:bg-rose-50"
                                        title="Hapus transaksi"
                                        aria-label="Hapus transaksi"
                                    >
                                        <Trash2 class="w-4 h-4" />
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <!-- MODAL KONFIRMASI HAPUS TRANSAKSI (Bersih dari modifier @click.stop.prevent) -->
        <div
            v-if="isDeleteModalOpen"
            v-dialog="
                () => {
                    isDeleteModalOpen = false;
                }
            "
            class="dialog-backdrop fixed inset-0 z-50 bg-ink-900/40 backdrop-blur-sm flex items-center justify-center p-4"
        >
            <div
                class="w-full max-w-sm bg-paper-0 border border-line-200 rounded-3xl p-6 shadow-card space-y-4 text-center animate-in fade-in zoom-in-95 duration-150"
            >
                <div
                    class="w-12 h-12 rounded-full bg-rose-100 text-rose-500 flex items-center justify-center mx-auto"
                >
                    <AlertTriangle class="w-6 h-6" />
                </div>

                <div>
                    <h3 class="font-display font-bold text-base text-ink-900">
                        Hapus Transaksi?
                    </h3>
                    <p class="text-xs text-ink-600 mt-1">
                        Transaksi
                        <span class="font-bold text-ink-900">{{
                            transactionToDelete?.note ||
                            transactionToDelete?.category?.name ||
                            "ini"
                        }}</span>
                        akan dihapus dan saldo dompet akan dikembalikan.
                    </p>
                </div>

                <div class="grid grid-cols-2 gap-2 pt-2">
                    <button
                        type="button"
                        @click="closeDeleteModal"
                        class="py-2.5 bg-base-50 text-ink-600 font-semibold text-xs rounded-xl hover:bg-base-100 transition-colors cursor-pointer"
                    >
                        Batal
                    </button>

                    <button
                        type="button"
                        @click="handleDelete"
                        :disabled="isDeleting"
                        class="py-2.5 bg-rose-500 text-white font-semibold text-xs rounded-xl shadow-soft hover:bg-rose-600 transition-colors flex items-center justify-center gap-1.5 cursor-pointer disabled:opacity-50"
                    >
                        <Loader2
                            v-if="isDeleting"
                            class="w-3.5 h-3.5 animate-spin"
                        />
                        <span>{{
                            isDeleting ? "Menghapus..." : "Ya, Hapus"
                        }}</span>
                    </button>
                </div>
            </div>
        </div>
        <button
            v-if="page < lastPage"
            :disabled="isLoading"
            @click="fetchTransactions(true)"
            class="primary-button mx-auto"
        >
            {{ isLoading ? "Memuat…" : "Muat transaksi berikutnya" }}
        </button>
    </div>
</template>

<style scoped>
.no-scrollbar::-webkit-scrollbar {
    display: none;
}
.no-scrollbar {
    -ms-overflow-style: none;
    scrollbar-width: none;
}

.activity-header-actions { display:flex;align-items:center;gap:10px; }
.activity-view-switch { display:inline-flex;align-items:center;gap:3px;padding:4px;border:1px solid var(--line);border-radius:14px;background:var(--surface-muted); }
.activity-view-switch button { display:inline-flex;align-items:center;justify-content:center;gap:7px;min-height:44px;padding:0 13px;border:0;border-radius:10px;color:var(--ink-muted);background:transparent;font-size:12px;font-weight:700;transition:color .18s ease,background .18s ease,box-shadow .18s ease; }
.activity-view-switch button:hover { color:var(--ink); }
.activity-view-switch button.is-active { color:var(--positive);background:var(--accent-soft);box-shadow:inset 0 0 0 1px color-mix(in srgb,var(--accent) 20%,transparent); }
.activity-calendar { overflow:hidden;border:1px solid var(--line);border-radius:22px;background:var(--surface);box-shadow:var(--shadow-glass); }
.activity-calendar-toolbar { display:grid;grid-template-columns:44px minmax(0,1fr) 44px;align-items:center;gap:12px;padding:16px 18px;border-bottom:1px solid var(--line); }
.activity-calendar-toolbar h2 { margin:0;text-align:center;text-transform:capitalize;font-size:16px;font-weight:750;letter-spacing:-.02em; }
.activity-calendar-nav { display:grid;place-items:center;width:44px;height:44px;border:1px solid transparent;border-radius:14px;color:var(--ink-soft);background:transparent;transition:background .18s ease,color .18s ease,border-color .18s ease; }
.activity-calendar-nav:hover { color:var(--positive);border-color:var(--line);background:var(--surface-muted); }
.activity-calendar-stats { display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px;padding:14px 18px;border-bottom:1px solid var(--line); }
.activity-calendar-stats > div { display:flex;flex-direction:column;gap:5px;min-width:0; }
.activity-calendar-stats span { color:var(--ink-muted);font-size:11px;font-weight:550; }
.activity-calendar-stats strong { overflow:hidden;color:var(--ink);font-size:clamp(14px,2vw,20px);font-weight:760;text-overflow:ellipsis;white-space:nowrap;font-variant-numeric:tabular-nums; }
.activity-calendar-stats > div:first-child strong { color:var(--negative); }
.activity-calendar-grid { display:grid;grid-template-columns:repeat(7,minmax(0,1fr));gap:5px;padding:16px 18px 10px; }
.activity-calendar-weekday { display:grid;place-items:center;min-height:28px;color:var(--ink-muted);font-size:11px;font-weight:700; }
.activity-calendar-blank { min-height:86px; }
.activity-calendar-day { position:relative;display:flex;flex-direction:column;align-items:flex-start;gap:4px;min-width:0;min-height:86px;padding:9px;border:1px solid var(--line);border-radius:13px;color:var(--ink);background:var(--surface-muted);text-align:left;transition:transform .16s ease,border-color .16s ease,background .16s ease,box-shadow .16s ease; }
.activity-calendar-day:hover { transform:translateY(-1px);border-color:color-mix(in srgb,var(--accent) 40%,var(--line)); }
.activity-calendar-day[style*="calendar-spend-tint"] { background:var(--calendar-spend-tint); }
.activity-calendar-day.is-selected { border-color:var(--accent);box-shadow:inset 0 0 0 1px var(--accent),0 0 0 2px color-mix(in srgb,var(--accent) 13%,transparent); }
.activity-calendar-day.is-today .activity-calendar-day-number { color:var(--positive); }
.activity-calendar-day-number { font-size:12px;font-weight:700;line-height:1.1; }
.activity-calendar-day-amount { max-width:100%;overflow:hidden;color:var(--ink);font-size:11px;font-weight:750;text-overflow:ellipsis;white-space:nowrap; }
.activity-calendar-income-dot { display:inline-block;width:7px;height:7px;flex:none;border-radius:50%;background:var(--positive);box-shadow:0 0 8px color-mix(in srgb,var(--positive) 48%,transparent); }
.activity-calendar-day > .activity-calendar-income-dot { position:absolute;top:11px;right:10px; }
.activity-calendar-legend { display:flex;flex-wrap:wrap;gap:8px 18px;padding:0 18px 16px;color:var(--ink-muted);font-size:11px; }
.activity-calendar-legend > span { display:inline-flex;align-items:center;gap:7px; }
.activity-calendar-heat-key { width:18px;height:10px;border:1px solid color-mix(in srgb,var(--negative) 35%,var(--line));border-radius:4px;background:color-mix(in srgb,var(--negative) 28%,var(--surface)); }
.activity-calendar-detail { padding:18px;border-top:1px solid var(--line);background:color-mix(in srgb,var(--surface-muted) 75%,transparent); }
.activity-calendar-detail-head { display:flex;align-items:flex-end;justify-content:space-between;gap:16px;margin-bottom:12px; }
.activity-calendar-kicker { color:var(--ink-muted);font-size:10px;font-weight:750;letter-spacing:.12em; }
.activity-calendar-detail-head h3 { margin:4px 0 0;text-transform:capitalize;font-size:17px;font-weight:750; }
.activity-calendar-detail-total { color:var(--negative);font-size:13px;white-space:nowrap; }
.activity-calendar-empty { margin:0;padding:18px 12px;border:1px dashed var(--line);border-radius:14px;color:var(--ink-muted);font-size:13px;text-align:center; }
.activity-calendar-transaction { display:flex;align-items:center;gap:11px;padding:11px 2px;border-top:1px solid var(--line);color:var(--ink);text-decoration:none; }
.activity-calendar-transaction:first-of-type { border-top:0; }
.activity-calendar-transaction-icon { display:grid;place-items:center;width:36px;height:36px;flex:none;border-radius:12px;color:var(--ink-soft);background:var(--surface-muted); }
.activity-calendar-transaction-icon.is-expense { color:var(--negative);background:var(--negative-soft); }
.activity-calendar-transaction-icon.is-income { color:var(--positive);background:var(--positive-soft); }
.activity-calendar-transaction-icon.is-transfer { color:var(--cyan);background:color-mix(in srgb,var(--cyan) 12%,transparent); }
.activity-calendar-transaction-copy { display:flex;flex:1;flex-direction:column;gap:3px;min-width:0; }
.activity-calendar-transaction-copy strong { overflow:hidden;font-size:12px;text-overflow:ellipsis;white-space:nowrap; }
.activity-calendar-transaction-copy small { color:var(--ink-muted);font-size:11px; }
.activity-calendar-transaction-amount { flex:none;font-size:12px;white-space:nowrap; }
.activity-calendar-transaction-amount.is-expense { color:var(--negative); }
.activity-calendar-transaction-amount.is-income { color:var(--positive); }
.activity-calendar-transaction-amount.is-transfer { color:var(--cyan); }
.activity-calendar-loading { display:flex;align-items:center;justify-content:center;gap:9px;min-height:220px;color:var(--ink-muted);font-size:13px; }

@media (max-width: 640px) {
    .activity-header-actions { width:100%;align-items:stretch;flex-direction:column-reverse; }
    .activity-header-actions > a { align-self:stretch!important; }
    .activity-view-switch { display:grid;grid-template-columns:1fr 1fr;width:100%; }
    .activity-view-switch button { min-height:44px; }
    .activity-calendar { border-radius:18px; }
    .activity-calendar-toolbar { grid-template-columns:40px minmax(0,1fr) 40px;gap:8px;padding:12px; }
    .activity-calendar-toolbar h2 { font-size:15px; }
    .activity-calendar-nav { width:40px;height:40px; }
    .activity-calendar-stats { gap:8px;padding:12px; }
    .activity-calendar-stats span { font-size:10px; }
    .activity-calendar-stats strong { font-size:14px; }
    .activity-calendar-grid { gap:4px;padding:12px 10px 8px; }
    .activity-calendar-weekday { min-height:24px;font-size:10px; }
    .activity-calendar-blank { min-height:60px; }
    .activity-calendar-day { min-height:60px;padding:7px 6px;border-radius:10px; }
    .activity-calendar-day-number { font-size:11px; }
    .activity-calendar-day-amount { font-size:9px; }
    .activity-calendar-day > .activity-calendar-income-dot { top:8px;right:7px;width:6px;height:6px; }
    .activity-calendar-legend { gap:7px 12px;padding:0 12px 12px;font-size:10px; }
    .activity-calendar-detail { padding:14px 12px; }
}

@media (prefers-reduced-motion: reduce) {
    .activity-view-switch button,.activity-calendar-nav,.activity-calendar-day { transition:none; }
    .activity-calendar-day:hover { transform:none; }
}
</style>
