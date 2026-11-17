<script setup>
import { onMounted, onUnmounted, ref } from "vue";
import Sidebar from "@/components/Sidebar.vue";
import { getApiCacheStatus } from "@/lib/axios";

const apiCacheStatus = ref(getApiCacheStatus());
function updateApiCacheStatus(event) {
  apiCacheStatus.value = event.detail || getApiCacheStatus();
}
onMounted(() => {
  window.addEventListener("ledger:api-cache-status", updateApiCacheStatus);
});
onUnmounted(() => {
  window.removeEventListener("ledger:api-cache-status", updateApiCacheStatus);
});
</script>
<template>
  <div class="app-frame">
    <a href="#main-content" class="skip-link">Langsung ke konten</a>
    <Sidebar />
    <main id="main-content" class="app-content" tabindex="-1">
      <p
        v-if="apiCacheStatus.hasStale && (apiCacheStatus.pending || apiCacheStatus.offline)"
        class="api-cache-status"
        role="status"
        aria-live="polite"
      >
        <template v-if="apiCacheStatus.pending">
          Menampilkan data sesi terakhir sambil memperbarui {{ apiCacheStatus.pending }} sumber data…
        </template>
        <template v-else-if="apiCacheStatus.offline">
          Koneksi belum tersedia. Data sesi terakhir masih ditampilkan.
        </template>
      </p>
      <div class="app-content-inner">
        <router-view v-slot="{ Component, route }">
          <transition name="content-page" mode="out-in" appear>
            <component
              :is="Component"
              :key="['goals.index', 'assets.index'].includes(route.name) ? 'financial-workspace' : route.path"
            />
          </transition>
        </router-view>
      </div>
    </main>
  </div>
</template>
