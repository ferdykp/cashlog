import path from "node:path";
import { fileURLToPath } from "node:url";
import vue from "@vitejs/plugin-vue";
import tailwindcss from "@tailwindcss/vite";
import laravel from "laravel-vite-plugin";
import { VitePWA } from "vite-plugin-pwa";
import { defineConfig } from "vite";

const rootDir = path.dirname(fileURLToPath(import.meta.url));
const iconPaths = ["/favicon.svg", "/pwa-192x192.png", "/pwa-512x512.png"];

export default defineConfig({
    plugins: [
        vue(),
        laravel({
            input: ["resources/js/main.js"],
            refresh: true,
            hotFile:
                process.env.CASHLOG_E2E_HOT_FILE ||
                path.join(rootDir, "public/hot"),
        }),
        tailwindcss(),
        VitePWA({
            strategies: "injectManifest",
            srcDir: "resources/js",
            filename: "sw.js",
            buildBase: "/build/",
            base: "/",
            scope: "/",
            registerType: "autoUpdate",
            injectRegister: "auto",
            includeAssets: [],
            manifest: {
                id: "/",
                name: "Ledger — Personal Finance",
                short_name: "Ledger",
                description:
                    "Kelola transaksi, budget, goals, tagihan, dan bukti pembayaran dalam satu tempat.",
                theme_color: "#0b0f17",
                background_color: "#0b0f17",
                display: "standalone",
                orientation: "any",
                start_url: "/",
                scope: "/",
                categories: ["finance", "productivity"],
                share_target: {
                    action: "/share-target",
                    method: "POST",
                    enctype: "multipart/form-data",
                    params: {
                        files: [
                            {
                                name: "files",
                                accept: [
                                    "image/*",
                                    "image/jpeg",
                                    "image/jpg",
                                    "image/png",
                                    "image/webp",
                                    ".jpg",
                                    ".jpeg",
                                    ".png",
                                    ".webp",
                                ],
                            },
                        ],
                    },
                },
                shortcuts: [
                    {
                        name: "Tambah transaksi",
                        short_name: "Tambah",
                        url: "/transactions/create",
                        icons: [{ src: "/pwa-192x192.png", sizes: "192x192" }],
                    },
                    {
                        name: "Scan bukti",
                        short_name: "Scan",
                        url: "/scan",
                        icons: [{ src: "/pwa-192x192.png", sizes: "192x192" }],
                    },
                ],
                icons: [
                    {
                        src: "/pwa-192x192.png",
                        sizes: "192x192",
                        type: "image/png",
                    },
                    {
                        src: "/pwa-512x512.png",
                        sizes: "512x512",
                        type: "image/png",
                    },
                    {
                        src: "/pwa-512x512.png",
                        sizes: "512x512",
                        type: "image/png",
                        purpose: "any maskable",
                    },
                ],
            },
            injectManifest: {
                rollupFormat: "iife",
                globPatterns: ["**/*.{js,css,html,ico,png,svg,woff2}"],
                globIgnores: ["**/chart-core-*.js", "**/charts-*.js"],
                additionalManifestEntries: [
                    { url: "/", revision: `${Date.now()}` },
                    ...iconPaths.map((url) => ({
                        url,
                        revision: `${Date.now()}`,
                    })),
                ],
                maximumFileSizeToCacheInBytes: 3000000,
            },
            devOptions: { enabled: false },
        }),
    ],
    resolve: {
        alias: { "@": path.resolve(rootDir, "resources/js") },
    },
    build: {
        rolldownOptions: {
            output: {
                codeSplitting: {
                    groups: [
                        {
                            name: "chart-core",
                            test: /apexcharts\/dist\/core\.esm\.js$/,
                        },
                    ],
                },
            },
        },
    },
    optimizeDeps: {
        include: [
            "apexcharts/core",
            "apexcharts/area",
            "apexcharts/bar",
            "apexcharts/features/legend",
            "apexcharts/features/keyboard",
        ],
    },
    server: {
        watch: { ignored: ["**/storage/framework/views/**"] },
    },
});
