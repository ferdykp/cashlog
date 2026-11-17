// resources/js/stores/auth.js
import { defineStore } from "pinia";
import { ref, computed } from "vue";
import router from "@/router";
import api from "@/lib/axios";
import { useNotificationStore } from "@/stores/notification";

export const useAuthStore = defineStore("auth", () => {
    const user = ref(null);
    // Deklarasikan variabel token sebagai ref
    const token = ref(localStorage.getItem("token") || "");
    const isAuthenticated = computed(() => Boolean(user.value || token.value));
    const notifyStore = useNotificationStore();
    let authResolved = false;
    let userRequest = null;

    async function fetchUser() {
        if (authResolved) return isAuthenticated.value;
        if (userRequest) return userRequest;

        userRequest = (async () => {
            try {
                const response = await api.get("/api/user");
                user.value = response.data;
                authResolved = true;
                return true;
            } catch (error) {
                if (error.response?.status === 401) {
                    user.value = null;
                    localStorage.removeItem("token");
                    token.value = "";
                }
                authResolved = true;
                return false;
            } finally {
                userRequest = null;
            }
        })();

        return userRequest;
    }

    async function login(credentials) {
        const response = await api.post("/api/session/login", credentials);
        localStorage.removeItem("token");
        token.value = "";
        user.value = response.data.data.user;
        authResolved = true;
        window.dispatchEvent(new Event("ledger:session-changed"));

        notifyStore.notify({
            message: "Berhasil masuk ke akun Anda.",
            type: "success",
        });

        return response.data;
    }

    async function register(payload) {
        const response = await api.post("/api/register", payload);

        notifyStore.notify({
            message: "Registrasi berhasil! Silahkan Login.",
            type: "success",
        });

        return response.data;
    }

    async function logout() {
        try {
            await api.post(token.value ? "/api/logout" : "/api/session/logout");
        } catch {
            // Always finish local logout even if the network is unavailable.
        } finally {
            logoutLocal();
            notifyStore.notify({
                message: "Anda telah keluar dari aplikasi.",
                type: "info",
            });

            await router.replace("/login");
        }
    }

    function logoutLocal() {
        window.dispatchEvent(new Event("ledger:session-cleared"));
        localStorage.removeItem("token");
        token.value = "";
        user.value = null;
        authResolved = true;
    }
    // resources/js/stores/auth.js

    async function updateProfile(formData) {
        const response = await api.post("/api/user/profile", formData);

        user.value = response.data.data || response.data;
        return user.value;
    }

    // Jangan lupa return updateProfile di bagian bawah store:
    return {
        user,
        token,
        isAuthenticated,
        fetchUser,
        login,
        register,
        updateProfile,
        logout,
        logoutLocal,
    };
});
