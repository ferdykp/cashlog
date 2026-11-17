<script setup>
import { computed, onUnmounted, ref, watch } from "vue";
import { formatRupiah } from "@/utils/formatters";

const props = defineProps({
    value: { type: [Number, String], default: 0 },
    masked: { type: Boolean, default: false },
    duration: { type: Number, default: 760 },
});

const display = ref(formatRupiah(0));
const animatedValue = ref(0);
const exactValue = computed(() => formatRupiah(props.value));
let frameId = 0;

function stopAnimation() {
    if (frameId) cancelAnimationFrame(frameId);
    frameId = 0;
}

function animateValue(value) {
    stopAnimation();
    if (props.masked) {
        display.value = "Rp ••••••••";
        return;
    }

    const target = Number(value ?? 0);
    const reduceMotion = window.matchMedia?.(
        "(prefers-reduced-motion: reduce)",
    ).matches;

    // The animation is presentation only. Preserve exact source formatting at the end,
    // and skip interpolation for values that Number cannot represent safely.
    if (
        !Number.isFinite(target) ||
        Math.abs(target) > Number.MAX_SAFE_INTEGER ||
        reduceMotion
    ) {
        animatedValue.value = Number.isFinite(target) ? target : 0;
        display.value = exactValue.value;
        return;
    }

    const start = animatedValue.value;
    const startedAt = performance.now();
    const duration = Math.max(280, Math.min(1000, props.duration));

    const tick = (now) => {
        const progress = Math.min(1, (now - startedAt) / duration);
        const eased = 1 - Math.pow(1 - progress, 4);
        const current = start + (target - start) * eased;
        animatedValue.value = current;
        display.value =
            progress === 1
                ? exactValue.value
                : formatRupiah(current.toFixed(2));

        if (progress < 1) frameId = requestAnimationFrame(tick);
        else frameId = 0;
    };

    frameId = requestAnimationFrame(tick);
}

watch(
    [() => props.value, () => props.masked],
    ([value]) => animateValue(value),
    {
        immediate: true,
    },
);

onUnmounted(stopAnimation);
</script>

<template>
    <span
        class="animated-money"
        :aria-label="masked ? 'Nominal disembunyikan' : exactValue"
        aria-live="off"
    >
        {{ display }}
    </span>
</template>
