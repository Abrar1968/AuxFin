<template>
    <canvas ref="canvas" :height="32"></canvas>
</template>

<script setup>
import { onMounted, ref, watch } from 'vue';

const props = defineProps({
    data: { type: Array, default: () => [] },
    color: { type: String, default: '#2f6fed' },
});

const canvas = ref(null);

function draw() {
    if (!canvas.value) return;

    const width = canvas.value.parentElement.clientWidth || 120;
    const ctx = canvas.value.getContext('2d');
    canvas.value.width = width;
    canvas.value.height = 32;

    const values = props.data.map((v) => Number(v));
    if (!values.length) return;

    const max = Math.max(...values, 1);
    const min = Math.min(...values, 0);
    const range = Math.max(1, max - min);
    const step = width / Math.max(1, values.length - 1);

    ctx.clearRect(0, 0, width, 32);
    ctx.beginPath();

    values.forEach((value, i) => {
        const x = i * step;
        const y = 28 - ((value - min) / range) * 24;
        if (i === 0) ctx.moveTo(x, y);
        else ctx.lineTo(x, y);
    });

    ctx.strokeStyle = props.color;
    ctx.lineWidth = 2;
    ctx.stroke();
}

onMounted(draw);
watch(() => props.data, draw, { deep: true });
</script>
