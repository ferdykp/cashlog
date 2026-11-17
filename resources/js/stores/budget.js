// resources/js/stores/budget.js
import { defineStore } from "pinia";
import { ref, computed } from "vue";
import api from "@/lib/axios";
import {
    compareDecimal,
    percentOfDecimal,
    subtractDecimal,
    sumDecimal,
} from "@/utils/decimal";

export const useBudgetStore = defineStore("budget", () => {
    const budgets = ref([]);
    const isLoading = ref(true);
    const hasFetched = ref(false);

    // Total keseluruhan limit & pengeluaran bulan ini
    const totalBudgetLimit = computed(() => {
        return sumDecimal(budgets.value.map((budget) => budget.amount_limit));
    });

    const totalBudgetSpent = computed(() => {
        return sumDecimal(budgets.value.map((budget) => budget.spent_amount));
    });

    const totalBudgetRemaining = computed(() => {
        const remaining = subtractDecimal(
            totalBudgetLimit.value,
            totalBudgetSpent.value,
        );
        return compareDecimal(remaining, "0.00") > 0 ? remaining : "0.00";
    });

    const totalBudgetPercentage = computed(() => {
        if (compareDecimal(totalBudgetLimit.value, "0.00") === 0) return 0;
        return Math.min(
            100,
            percentOfDecimal(totalBudgetSpent.value, totalBudgetLimit.value),
        );
    });

    // Fetch daftar budget berdasarkan bulan (e.g. YYYY-MM)
    async function fetchBudgets(month = null) {
        isLoading.value = !hasFetched.value;
        try {
            const url = month ? `/api/budgets?month=${month}` : "/api/budgets";
            const response = await api.get(url);
            const data = response.data.data || response.data;
            budgets.value = Array.isArray(data) ? data : [];
        } catch (error) {
            console.warn("Gagal memuat budget:", error.message);
        } finally {
            hasFetched.value = true;
            isLoading.value = false;
        }
    }

    // Set / Update limit budget
    async function saveBudget(payload) {
        const response = await api.post("/api/budgets", payload);
        const newBudget = response.data.data || response.data;

        const index = budgets.value.findIndex((b) => b.id === newBudget.id);
        if (index !== -1) {
            budgets.value[index] = newBudget;
        } else {
            budgets.value.push(newBudget);
        }
        return newBudget;
    }

    // Hapus limit budget
    async function deleteBudget(id) {
        await api.delete(`/api/budgets/${id}`);
        budgets.value = budgets.value.filter((b) => b.id !== id);
    }

    return {
        budgets,
        isLoading,
        totalBudgetLimit,
        totalBudgetSpent,
        totalBudgetRemaining,
        totalBudgetPercentage,
        fetchBudgets,
        saveBudget,
        deleteBudget,
    };
});
