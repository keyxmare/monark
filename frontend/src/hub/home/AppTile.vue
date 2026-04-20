<script setup lang="ts">
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';

interface AppTileProps {
  accentHue: number;
  code: string;
  glyph: string;
  index: number;
  mounted: boolean;
  name: string;
  primaryStat?: null | { label: string; value: string };
  stats: { label: string; value: string }[];
  status: 'live' | 'soon';
  subtitle: string;
}

const props = withDefaults(defineProps<AppTileProps>(), {
  primaryStat: null,
});
defineEmits<{ (e: 'launch'): void }>();

const { t } = useI18n();

const tileRef = ref<HTMLButtonElement | null>(null);
const hovered = ref(false);
const tilt = ref({ x: 0, y: 0 });

const disabled = computed(() => props.status !== 'live');

function onEnter() {
  hovered.value = true;
}
function onLeave() {
  hovered.value = false;
  tilt.value = { x: 0, y: 0 };
}
function onMove(e: MouseEvent) {
  const el = tileRef.value;
  if (!el) return;
  const r = el.getBoundingClientRect();
  tilt.value = {
    x: (e.clientX - r.left) / r.width - 0.5,
    y: (e.clientY - r.top) / r.height - 0.5,
  };
}

const transform = computed(() => {
  if (!props.mounted) return 'translateY(20px)';
  const rx = tilt.value.y * -4;
  const ry = tilt.value.x * 4;
  const ty = hovered.value ? -4 : 0;
  return `perspective(900px) rotateX(${rx}deg) rotateY(${ry}deg) translateY(${ty}px)`;
});

const glowBg = computed(
  () =>
    `radial-gradient(240px at ${50 + tilt.value.x * 100}% ${
      50 + tilt.value.y * 100
    }%, hsla(${props.accentHue}, 80%, 60%, ${hovered.value ? 0.22 : 0}), transparent 60%)`,
);

const glyphTransform = computed(() =>
  hovered.value ? 'scale(1.06) rotate(-4deg)' : 'scale(1) rotate(0)',
);

const arrowTransform = computed(() => (hovered.value ? 'translate(4px, 0)' : 'translate(0, 0)'));
</script>

<template>
  <button
    ref="tileRef"
    class="app-tile"
    :class="{ show: mounted, disabled, hovered }"
    :style="{
      '--hue': accentHue,
      '--delay': `${200 + index * 60}ms`,
      '--tile-transform': transform,
      '--tile-border': hovered ? 'var(--hub-line-strong)' : 'var(--hub-line)',
    }"
    :disabled="disabled"
    :data-testid="`hub-app-tile`"
    @click="$emit('launch')"
    @mousemove="onMove"
    @mouseenter="onEnter"
    @mouseleave="onLeave"
    @focusin="onEnter"
    @focusout="onLeave"
  >
    <span class="glow" aria-hidden="true" :style="{ background: glowBg }" />

    <div class="head">
      <div class="glyph" :style="{ transform: glyphTransform }">{{ glyph }}</div>
      <div class="head-right">
        <span class="pill" :class="{ live: status === 'live' }">
          <span class="pill-dot" />
          {{ status === 'live' ? t('hub.home.pill.live') : t('hub.home.pill.soon') }}
        </span>
        <div v-if="primaryStat" class="primary-stat" data-testid="app-tile-primary-stat">
          <span class="primary-value">{{ primaryStat.value }}</span>
          <span class="primary-label">{{ primaryStat.label }}</span>
        </div>
      </div>
    </div>

    <div class="title-row">
      <span class="code">{{ code }}</span>
      <h3>{{ name }}</h3>
    </div>
    <p class="subtitle">{{ subtitle }}</p>

    <div class="stats">
      <div v-for="s in stats" :key="s.label" class="stat">
        <span class="k">{{ s.label.toUpperCase() }}</span>
        <span class="v">{{ s.value }}</span>
      </div>
    </div>

    <span class="arrow" aria-hidden="true" :style="{ transform: arrowTransform }">→</span>
  </button>
</template>

<style scoped>
.app-tile {
  position: relative;
  text-align: left;
  padding: 20px 22px;
  background: color-mix(in oklab, var(--hub-bg-2) 80%, transparent);
  border: 1px solid var(--tile-border, var(--hub-line));
  border-radius: 14px;
  overflow: hidden;
  color: inherit;
  font: inherit;
  cursor: pointer;
  opacity: 0;
  transform: translateY(20px);
  transition:
    transform 400ms var(--hub-ease),
    border-color 200ms,
    opacity 700ms var(--hub-ease);
  transition-delay: var(--delay, 0ms);
  backdrop-filter: blur(10px);
  -webkit-backdrop-filter: blur(10px);
}
.app-tile.show {
  opacity: 1;
  transform: var(--tile-transform, translateY(0));
}
.app-tile.disabled {
  cursor: not-allowed;
}
.app-tile.disabled::after {
  content: '';
  position: absolute;
  inset: 0;
  background-image: repeating-linear-gradient(
    -45deg,
    rgba(255, 255, 255, 0.02) 0 8px,
    transparent 8px 18px
  );
  pointer-events: none;
}

.glow {
  position: absolute;
  inset: -1px;
  border-radius: 14px;
  pointer-events: none;
  transition: background 250ms ease;
}

.head {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  position: relative;
}
.head-right {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 10px;
}

.primary-stat {
  display: flex;
  align-items: baseline;
  gap: 6px;
  line-height: 1;
}
.primary-value {
  font-family: var(--hub-font-serif);
  font-style: italic;
  font-weight: 300;
  font-size: 36px;
  letter-spacing: -0.02em;
  color: hsl(var(--hue), 82%, 80%);
  text-shadow: 0 0 16px hsla(var(--hue), 80%, 60%, 0.35);
  font-variant-numeric: tabular-nums;
}
.primary-label {
  font-family: var(--hub-font-mono);
  font-size: 9px;
  letter-spacing: 0.14em;
  text-transform: uppercase;
  color: var(--hub-fg-3);
}
.glyph {
  width: 44px;
  height: 44px;
  display: grid;
  place-items: center;
  border-radius: 12px;
  background: hsla(var(--hue), 70%, 55%, 0.12);
  border: 1px solid hsla(var(--hue), 70%, 55%, 0.3);
  color: hsl(var(--hue), 80%, 70%);
  font-size: 20px;
  transition: transform 300ms var(--hub-ease);
}

.pill {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-family: var(--hub-font-mono);
  font-size: 10px;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  padding: 4px 8px;
  border-radius: 999px;
  color: var(--hub-fg-2);
  background: rgba(255, 255, 255, 0.03);
  border: 1px solid var(--hub-line);
}
.pill.live {
  color: hsl(var(--hue), 80%, 75%);
  background: hsla(var(--hue), 70%, 55%, 0.1);
  border-color: hsla(var(--hue), 70%, 55%, 0.3);
}
.pill-dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: var(--hub-fg-3);
}
.pill.live .pill-dot {
  background: var(--hub-good);
  animation: pill-pulse 2s ease-in-out infinite;
}
@keyframes pill-pulse {
  0%,
  100% {
    opacity: 0.6;
    transform: scale(1);
  }
  50% {
    opacity: 1;
    transform: scale(1.15);
  }
}

.title-row {
  margin-top: 16px;
  display: flex;
  align-items: baseline;
  gap: 10px;
  position: relative;
}
.code {
  font-family: var(--hub-font-mono);
  font-size: 10px;
  color: var(--hub-fg-3);
  letter-spacing: 0.15em;
}
h3 {
  margin: 0;
  font-weight: 500;
  font-size: 22px;
  letter-spacing: -0.01em;
}

.subtitle {
  margin: 6px 0 0;
  color: var(--hub-fg-2);
  font-size: 13px;
  line-height: 1.4;
  position: relative;
}

.stats {
  margin-top: 18px;
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 8px;
  font-family: var(--hub-font-mono);
  font-size: 11px;
  position: relative;
}
.stat {
  display: flex;
  flex-direction: column;
  gap: 2px;
}
.stat .k {
  color: var(--hub-fg-3);
  font-size: 9px;
  letter-spacing: 0.12em;
}
.stat .v {
  color: var(--hub-fg-0);
  font-size: 14px;
}

.arrow {
  position: absolute;
  bottom: 16px;
  right: 18px;
  font-family: var(--hub-font-mono);
  font-size: 14px;
  color: var(--hub-fg-2);
  transition: transform 300ms var(--hub-ease);
}
.app-tile.disabled .arrow {
  opacity: 0.3;
}
</style>
