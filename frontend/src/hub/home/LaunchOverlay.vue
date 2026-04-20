<script setup lang="ts">
import { onMounted, ref } from 'vue';

interface LaunchOverlayProps {
  accentHue: number;
  code: string;
  glyph: string;
  name: string;
  subtitle: string;
}

const props = defineProps<LaunchOverlayProps>();

interface BootLine {
  key: string;
  label: string;
}

const BOOT_LINES: BootLine[] = [
  { key: 'acquire', label: '> acquiring target' },
  { key: 'handshake', label: '> handshake · auth token' },
  { key: 'modules', label: '> loading modules' },
  { key: 'shell', label: '> mounting shell' },
  { key: 'state', label: '> hydrating state' },
  { key: 'ready', label: '> ready' },
];

const FIRST_STEP_OFFSET_MS = 240;
const STEP_DELAY_MS = 260;

const revealed = ref(0);

onMounted(() => {
  for (let i = 0; i < BOOT_LINES.length; i++) {
    window.setTimeout(
      () => {
        revealed.value = i + 1;
      },
      FIRST_STEP_OFFSET_MS + i * STEP_DELAY_MS,
    );
  }
});
</script>

<template>
  <div
    class="launch-overlay"
    :style="{ '--hue': String(props.accentHue) }"
    role="status"
    aria-live="polite"
    data-testid="launch-overlay"
  >
    <div class="scanlines" aria-hidden="true" />
    <div class="vignette" aria-hidden="true" />

    <div class="center">
      <div class="glyph">
        <span class="glyph-ring ring-1" aria-hidden="true" />
        <span class="glyph-ring ring-2" aria-hidden="true" />
        <span class="glyph-char">{{ props.glyph }}</span>
      </div>

      <div class="title-block">
        <span class="crumb">{{ props.code }} / launch</span>
        <h2 class="title">{{ props.name }}</h2>
        <span class="subtitle">{{ props.subtitle }}</span>
      </div>

      <ul class="boot">
        <li
          v-for="(line, i) in BOOT_LINES"
          :key="line.key"
          class="boot-line"
          :class="{ shown: revealed > i, active: revealed === i + 1 }"
        >
          <span class="boot-dot" aria-hidden="true" />
          <span class="boot-text">{{ line.label }}</span>
          <span v-if="revealed > i" class="boot-ok" aria-hidden="true">ok</span>
        </li>
      </ul>

      <div class="track" aria-hidden="true">
        <div class="fill" />
      </div>
    </div>
  </div>
</template>

<style scoped>
.launch-overlay {
  position: fixed;
  inset: 0;
  z-index: 40;
  display: grid;
  place-items: center;
  background: color-mix(in oklab, var(--hub-bg-0) 92%, transparent);
  backdrop-filter: blur(14px);
  -webkit-backdrop-filter: blur(14px);
  animation: overlay-in 260ms var(--hub-ease-out) both;
  font-family: var(--hub-font-sans);
  color: var(--hub-fg-0);
}

@keyframes overlay-in {
  from {
    opacity: 0;
    backdrop-filter: blur(0);
  }
  to {
    opacity: 1;
    backdrop-filter: blur(14px);
  }
}

.scanlines {
  position: absolute;
  inset: 0;
  pointer-events: none;
  background-image: repeating-linear-gradient(
    0deg,
    hsla(var(--hue), 60%, 60%, 0.035) 0 1px,
    transparent 1px 3px
  );
  mix-blend-mode: screen;
}

.vignette {
  position: absolute;
  inset: 0;
  pointer-events: none;
  background: radial-gradient(
    ellipse at center,
    hsla(var(--hue), 70%, 55%, 0.1) 0%,
    transparent 60%
  );
  animation: vignette-pulse 2.4s ease-in-out infinite;
}

@keyframes vignette-pulse {
  0%,
  100% {
    opacity: 0.6;
  }
  50% {
    opacity: 1;
  }
}

.center {
  position: relative;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 24px;
  padding: 40px 48px;
  min-width: 340px;
  max-width: 460px;
  text-align: center;
}

.glyph {
  position: relative;
  width: 88px;
  height: 88px;
  display: grid;
  place-items: center;
  animation: glyph-in 420ms var(--hub-ease-out) both;
}

@keyframes glyph-in {
  from {
    opacity: 0;
    transform: translateY(8px) scale(0.92);
  }
  to {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}

.glyph-ring {
  position: absolute;
  border-radius: 50%;
  border: 1px solid hsla(var(--hue), 70%, 60%, 0.35);
}
.glyph-ring.ring-1 {
  inset: 0;
  animation: ring-rot 2.8s linear infinite;
  border-top-color: hsla(var(--hue), 80%, 70%, 0.9);
}
.glyph-ring.ring-2 {
  inset: 10px;
  border-style: dashed;
  opacity: 0.55;
  animation: ring-rot 4.2s linear infinite reverse;
}
@keyframes ring-rot {
  to {
    transform: rotate(360deg);
  }
}

.glyph-char {
  font-family: var(--hub-font-serif);
  font-size: 32px;
  color: hsl(var(--hue), 80%, 75%);
  text-shadow: 0 0 18px hsla(var(--hue), 80%, 60%, 0.5);
}

.title-block {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 4px;
  animation: block-in 500ms 80ms var(--hub-ease-out) both;
}

@keyframes block-in {
  from {
    opacity: 0;
    transform: translateY(6px);
    filter: blur(4px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
    filter: blur(0);
  }
}

.crumb {
  font-family: var(--hub-font-mono);
  font-size: 10px;
  letter-spacing: 0.18em;
  text-transform: uppercase;
  color: var(--hub-fg-3);
}
.title {
  margin: 0;
  font-family: var(--hub-font-serif);
  font-style: italic;
  font-weight: 300;
  font-size: clamp(28px, 3vw, 40px);
  letter-spacing: -0.02em;
  color: var(--hub-fg-0);
}
.subtitle {
  font-family: var(--hub-font-mono);
  font-size: 11px;
  color: var(--hub-fg-2);
  letter-spacing: 0.04em;
}

.boot {
  list-style: none;
  margin: 0;
  padding: 0;
  display: flex;
  flex-direction: column;
  gap: 4px;
  width: 100%;
  max-width: 320px;
  text-align: left;
  font-family: var(--hub-font-mono);
  font-size: 11px;
}

.boot-line {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 4px 8px;
  border-radius: 6px;
  color: var(--hub-fg-3);
  opacity: 0;
  transform: translateX(-6px);
  transition:
    opacity 220ms var(--hub-ease),
    transform 220ms var(--hub-ease),
    color 220ms var(--hub-ease),
    background 220ms var(--hub-ease);
}
.boot-line.shown {
  opacity: 1;
  transform: translateX(0);
  color: var(--hub-fg-1);
}
.boot-line.active {
  color: hsl(var(--hue), 80%, 80%);
  background: hsla(var(--hue), 70%, 55%, 0.08);
}

.boot-dot {
  width: 5px;
  height: 5px;
  border-radius: 50%;
  background: var(--hub-fg-3);
  flex-shrink: 0;
}
.boot-line.shown .boot-dot {
  background: hsl(var(--hue), 80%, 65%);
  box-shadow: 0 0 8px hsla(var(--hue), 80%, 60%, 0.7);
}

.boot-text {
  flex: 1;
}

.boot-ok {
  font-size: 9px;
  letter-spacing: 0.12em;
  text-transform: uppercase;
  color: var(--hub-good);
}

.track {
  width: 100%;
  max-width: 320px;
  height: 2px;
  border-radius: 2px;
  background: color-mix(in oklab, var(--hub-line-strong) 60%, transparent);
  overflow: hidden;
}
.fill {
  height: 100%;
  width: 0;
  background: linear-gradient(
    90deg,
    hsla(var(--hue), 80%, 60%, 0.9),
    hsla(var(--hue), 90%, 75%, 1)
  );
  animation: fill-up 1800ms var(--hub-ease-out) forwards;
  box-shadow: 0 0 12px hsla(var(--hue), 80%, 60%, 0.5);
}
@keyframes fill-up {
  to {
    width: 100%;
  }
}
</style>
