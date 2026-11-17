<script setup>
import { onMounted } from "vue";
import ToastContainer from "./components/ToastContainer.vue";
import PwaInstallPrompt from "./components/PwaInstallPrompt.vue";
const savedTheme = localStorage.getItem("theme") || "dark";
document.documentElement.classList.toggle("dark", savedTheme === "dark");
onMounted(() => {
    document.documentElement.classList.toggle("dark", savedTheme === "dark");
});
</script>
<template>
    <div class="app-root">
        <router-view v-slot="{ Component, route }">
            <transition name="page" mode="out-in" appear>
                <!-- Keep the authenticated shell mounted while child routes change. -->
                <component
                    :is="Component"
                    :key="route.matched[0]?.path || route.path"
                />
            </transition>
        </router-view>
        <ToastContainer />
        <PwaInstallPrompt />
    </div>
</template>
