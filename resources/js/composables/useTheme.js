import { ref } from "vue";

const storedTheme = typeof window !== "undefined" ? window.localStorage.getItem("theme") : null;
const isDark = ref(storedTheme === "dark");

function setTheme(mode) {
    const nextMode = mode === "light" ? "light" : "dark";
    isDark.value = nextMode === "dark";

    if (typeof document !== "undefined") {
        document.documentElement.classList.toggle("dark", isDark.value);
    }
    if (typeof window !== "undefined") {
        window.localStorage.setItem("theme", nextMode);
    }
}

function toggleTheme() {
    setTheme(isDark.value ? "light" : "dark");
}

// Apply the persisted preference before the first screen paints.
if (typeof document !== "undefined") {
    document.documentElement.classList.toggle("dark", isDark.value);
}

export function useTheme() {
    return { isDark, setTheme, toggleTheme };
}
