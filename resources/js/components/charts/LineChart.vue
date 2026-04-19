<template>
    <canvas ref="canvas" :height="height"></canvas>
</template>

<script setup>
import { onMounted, ref, watch } from 'vue';

const props = defineProps({
    values: { type: Array, default: () => [] },
    height: { type: Number, default: 220 },
    color: { type: String, default: '#0ea5a6' },
});

const canvas = ref(null);

function draw() {
    if (!canvas.value) return;

    const width = canvas.value.parentElement.clientWidth;
    const ctx = canvas.value.getContext('2d');
    canvas.value.width = width;
    ctx.clearRect(0, 0, width, props.height);

    const values = props.values.map((v) => Number(v));
    if (!values.length) return;

    const max = Math.max(...values, 1);
    const min = Math.min(...values, 0);
    const range = Math.max(1, max - min);
    const step = width / Math.max(1, values.length - 1);

    ctx.beginPath();
    values.forEach((value, index) => {
        const x = index * step;
        const y = props.height - ((value - min) / range) * (props.height - 20) - 10;

        if (index === 0) ctx.moveTo(x, y);
        else ctx.lineTo(x, y);
    });

    ctx.strokeStyle = props.color;
    ctx.lineWidth = 2;
    ctx.stroke();
}

onMounted(draw);
watch(() => props.values, draw, { deep: true });
</script>
