<template>
    <canvas ref="canvas" :height="height"></canvas>
</template>

<script setup>
import { onMounted, ref, watch } from 'vue';

const props = defineProps({
    labels: { type: Array, default: () => [] },
    values: { type: Array, default: () => [] },
    height: { type: Number, default: 200 },
});

const canvas = ref(null);

function draw() {
    if (!canvas.value) return;

    const ctx = canvas.value.getContext('2d');
    const width = canvas.value.parentElement.clientWidth;
    canvas.value.width = width;
    ctx.clearRect(0, 0, width, props.height);

    const max = Math.max(1, ...props.values.map((v) => Number(v)));
    const barWidth = width / Math.max(1, props.values.length) - 10;

    props.values.forEach((value, index) => {
        const x = index * (barWidth + 10) + 5;
        const h = (Number(value) / max) * (props.height - 20);
        const y = props.height - h;

        ctx.fillStyle = '#2f6fed';
        ctx.fillRect(x, y, barWidth, h);
    });
}

onMounted(draw);
watch(() => [props.labels, props.values], draw, { deep: true });
</script>
