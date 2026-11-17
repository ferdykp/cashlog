import { ref } from "vue";
import api from "@/lib/axios";

export function useGoalDetails({ selectedGoalId, notifyError, isBusy }) {
  const funding = ref(null);
  const contributions = ref([]);
  const contributionNextPage = ref(null);
  const contributionHasMore = ref(false);
  const cashHistory = ref([]);

  async function fetchGoalDetails() {
    if (!selectedGoalId.value) return;

    try {
      const goalId = selectedGoalId.value;
      const [fundingRes, contributionRes, cashRes] = await Promise.all([
        api.get(`/api/goals/${goalId}/funding`),
        api.get(`/api/goals/${goalId}/contributions?page=1`),
        api.get(`/api/goals/${goalId}/cash-allocations`),
      ]);

      // Ignore a late response when the user has opened another goal.
      if (Number(selectedGoalId.value) !== Number(goalId)) return;

      funding.value = fundingRes.data.data;
      contributions.value = contributionRes.data.data || [];
      contributionNextPage.value = contributionRes.data.meta?.current_page
        ? contributionRes.data.meta.current_page + 1
        : null;
      contributionHasMore.value = Boolean(
        contributionRes.data.meta?.current_page &&
          contributionRes.data.meta.current_page <
            contributionRes.data.meta.last_page,
      );
      cashHistory.value = cashRes.data.history?.data || [];
    } catch (err) {
      notifyError(err, "Rincian target gagal dimuat.");
    }
  }

  async function loadMoreContributions() {
    if (!selectedGoalId.value || !contributionNextPage.value || isBusy.value)
      return;

    isBusy.value = true;
    try {
      const goalId = selectedGoalId.value;
      const { data } = await api.get(
        `/api/goals/${goalId}/contributions?page=${contributionNextPage.value}`,
      );
      if (Number(selectedGoalId.value) !== Number(goalId)) return;

      contributions.value.push(...(data.data || []));
      contributionNextPage.value =
        data.meta?.current_page < data.meta?.last_page
          ? data.meta.current_page + 1
          : null;
      contributionHasMore.value = Boolean(contributionNextPage.value);
    } catch (err) {
      notifyError(err, "Riwayat setoran gagal dimuat.");
    } finally {
      isBusy.value = false;
    }
  }

  function clearGoalDetails() {
    funding.value = null;
    contributions.value = [];
    contributionNextPage.value = null;
    contributionHasMore.value = false;
    cashHistory.value = [];
  }

  return {
    funding,
    contributions,
    contributionNextPage,
    contributionHasMore,
    cashHistory,
    fetchGoalDetails,
    loadMoreContributions,
    clearGoalDetails,
  };
}
