<template>
    <canvas ref="canvas" :height="height"></canvas>
</template>

<script setup>
import { onMounted, ref, watch } from 'vue';

const props = defineProps({
    p10: { type: Array, default: () => [] },
    p50: { type: Array, default: () => [] },
    p90: { type: Array, default: () => [] },
    height: { type: Number, default: 220 },
});

const canvas = ref(null);

function drawSeries(ctx, values, width, color) {
    if (!values.length) return;
    const max = Math.max(...values.map((v) => Number(v)), 1);
    const step = width / Math.max(1, values.length - 1);

    ctx.beginPath();
    values.forEach((value, index) => {
        const x = index * step;
        const y = props.height - (Number(value) / max) * (props.height - 20) - 10;
        if (index === 0) ctx.moveTo(x, y);
        else ctx.lineTo(x, y);
    });
    ctx.strokeStyle = color;
    ctx.lineWidth = 2;
    ctx.stroke();
}

function draw() {
    if (!canvas.value) return;

    const width = canvas.value.parentElement.clientWidth;
    const ctx = canvas.value.getContext('2d');
    canvas.value.width = width;
    ctx.clearRect(0, 0, width, props.height);

    drawSeries(ctx, props.p10, width, '#ef4444');
    drawSeries(ctx, props.p50, width, '#2f6fed');
    drawSeries(ctx, props.p90, width, '#10b981');
}

onMounted(draw);
watch(() => [props.p10, props.p50, props.p90], draw, { deep: true });
</script>
