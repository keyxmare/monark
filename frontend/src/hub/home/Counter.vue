<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';

interface CounterProps {
  duration?: number;
  to: number | string;
}

const props = withDefaults(defineProps<CounterProps>(), { duration: 1400 });

const current = ref<number | string>(typeof props.to === 'number' ? 0 : props.to);

let raf = 0;
let t0 = 0;

function animate() {
  if (typeof props.to !== 'number') {
    current.value = props.to;
    return;
  }
  const target = props.to;
  t0 = 0;
  const step = (t: number) => {
    if (!t0) t0 = t;
    const p = Math.min(1, (t - t0) / props.duration);
    const ease = 1 - Math.pow(1 - p, 3);
    current.value = Math.round(target * ease);
    if (p < 1) raf = requestAnimationFrame(step);
  };
  raf = requestAnimationFrame(step);
}

onMounted(animate);
watch(
  () => props.to,
  () => {
    cancelAnimationFrame(raf);
    animate();
  },
);
onBeforeUnmount(() => cancelAnimationFrame(raf));

const display = computed(() => current.value);
</script>

<template>{{ display }}</template>
