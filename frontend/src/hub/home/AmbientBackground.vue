<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref } from 'vue';

interface Blob {
  h: number;
  l: number;
  phase: number;
  s: number;
  size: number;
}

const ACCENT_HUE = 322;
const BLOBS: Blob[] = [
  { h: ACCENT_HUE, l: 45, phase: 0, s: 55, size: 0.35 },
  { h: (ACCENT_HUE + 180) % 360, l: 35, phase: 2, s: 30, size: 0.28 },
];

const canvas = ref<HTMLCanvasElement | null>(null);
let raf = 0;
let stop = false;
const mouse = { tx: 0.5, ty: 0.5, x: 0.5, y: 0.5 };

function onMove(e: MouseEvent) {
  mouse.tx = e.clientX / window.innerWidth;
  mouse.ty = e.clientY / window.innerHeight;
}

onMounted(() => {
  const el = canvas.value;
  if (!el) return;
  const ctx = el.getContext('2d');
  if (!ctx) return;
  let w = 0;
  let h = 0;
  const dpr = Math.min(window.devicePixelRatio || 1, 2);

  const resize = () => {
    w = el.clientWidth;
    h = el.clientHeight;
    el.width = w * dpr;
    el.height = h * dpr;
    ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
  };
  resize();
  window.addEventListener('resize', resize);
  window.addEventListener('mousemove', onMove);

  const t0 = performance.now();
  const tick = (now: number) => {
    if (stop) return;
    const t = (now - t0) / 1000;
    mouse.x += (mouse.tx - mouse.x) * 0.05;
    mouse.y += (mouse.ty - mouse.y) * 0.05;

    ctx.clearRect(0, 0, w, h);

    // solid near-black base
    ctx.fillStyle = '#050505';
    ctx.fillRect(0, 0, w, h);

    // subtle blobs
    BLOBS.forEach((b, i) => {
      const amp = 0.28;
      const angle = t * 0.08 + b.phase;
      const cx = w * (0.5 + Math.cos(angle) * amp + (mouse.x - 0.5) * 0.1);
      const cy = h * (0.5 + Math.sin(angle * 1.2) * amp + (mouse.y - 0.5) * 0.1);
      const r = Math.min(w, h) * b.size * (1 + Math.sin(t * 0.5 + i) * 0.08);
      const g = ctx.createRadialGradient(cx, cy, 0, cx, cy, r);
      g.addColorStop(0, `hsla(${b.h}, ${b.s}%, ${b.l}%, 0.12)`);
      g.addColorStop(0.4, `hsla(${b.h}, ${b.s}%, ${b.l}%, 0.05)`);
      g.addColorStop(1, `hsla(${b.h}, ${b.s}%, ${b.l}%, 0)`);
      ctx.fillStyle = g;
      ctx.fillRect(0, 0, w, h);
    });

    // drifting dot grid (signature of the terminal direction)
    ctx.fillStyle = `hsla(${ACCENT_HUE}, 40%, 65%, 0.05)`;
    const step = 24;
    const off = (t * 10) % step;
    for (let y = -step + off; y < h; y += step) {
      for (let x = 0; x < w; x += step) {
        ctx.fillRect(x, y, 1, 1);
      }
    }

    raf = requestAnimationFrame(tick);
  };
  raf = requestAnimationFrame(tick);
});

onBeforeUnmount(() => {
  stop = true;
  cancelAnimationFrame(raf);
  window.removeEventListener('mousemove', onMove);
});
</script>

<template>
  <div class="ambient-root" aria-hidden="true">
    <canvas ref="canvas" class="ambient-canvas" />
    <div class="ambient-grid" />
    <div class="ambient-grain" />
  </div>
</template>

<style scoped>
.ambient-root {
  position: fixed;
  inset: 0;
  pointer-events: none;
  z-index: 0;
}
.ambient-canvas {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
}
.ambient-grid {
  position: absolute;
  inset: 0;
  background-image:
    linear-gradient(hsla(322, 30%, 60%, 0.04) 1px, transparent 1px),
    linear-gradient(90deg, hsla(322, 30%, 60%, 0.04) 1px, transparent 1px);
  background-size: 64px 64px;
  mask-image: radial-gradient(ellipse at center, rgba(0, 0, 0, 1) 30%, rgba(0, 0, 0, 0) 90%);
  -webkit-mask-image: radial-gradient(
    ellipse at center,
    rgba(0, 0, 0, 1) 30%,
    rgba(0, 0, 0, 0) 90%
  );
  animation: grid-drift 60s linear infinite;
}
.ambient-grain {
  position: absolute;
  inset: 0;
  opacity: 0.12;
  mix-blend-mode: overlay;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='160' height='160'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='2' stitchTiles='stitch'/%3E%3CfeColorMatrix values='0 0 0 0 0.5 0 0 0 0 0.5 0 0 0 0 0.5 0 0 0 1 0'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)'/%3E%3C/svg%3E");
}
@keyframes grid-drift {
  from {
    transform: translate(0, 0);
  }
  to {
    transform: translate(64px, 64px);
  }
}
</style>
