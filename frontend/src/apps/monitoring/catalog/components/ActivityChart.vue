<script setup lang="ts">
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

interface Point {
  x: number;
  y: number;
}

const props = defineProps<{ series: number[] }>();
const { t } = useI18n();

const W = 600;
const H = 160;
const PAD_Y = 16;
const INNER_H = H - PAD_Y * 2;

const points = computed<Point[]>(() => {
  if (props.series.length === 0) return [];
  const max = Math.max(1, ...props.series);
  const len = props.series.length;
  return props.series.map((v, i) => ({
    x: len === 1 ? W / 2 : (i / (len - 1)) * W,
    y: PAD_Y + INNER_H - (v / max) * INNER_H,
  }));
});

const linePath = computed<string>(() => {
  const pts = points.value;
  if (pts.length < 2) return '';
  let d = `M ${pts[0].x.toFixed(2)} ${pts[0].y.toFixed(2)}`;
  for (let i = 0; i < pts.length - 1; i++) {
    const p0 = pts[i - 1] ?? pts[i];
    const p1 = pts[i];
    const p2 = pts[i + 1];
    const p3 = pts[i + 2] ?? p2;
    const t = 0.22;
    const c1x = p1.x + (p2.x - p0.x) * t;
    const c1y = p1.y + (p2.y - p0.y) * t;
    const c2x = p2.x - (p3.x - p1.x) * t;
    const c2y = p2.y - (p3.y - p1.y) * t;
    d += ` C ${c1x.toFixed(2)} ${c1y.toFixed(2)}, ${c2x.toFixed(2)} ${c2y.toFixed(2)}, ${p2.x.toFixed(2)} ${p2.y.toFixed(2)}`;
  }
  return d;
});

const areaPath = computed<string>(() => {
  const pts = points.value;
  if (pts.length < 2) return '';
  const last = pts[pts.length - 1];
  const first = pts[0];
  return `${linePath.value} L ${last.x.toFixed(2)} ${(PAD_Y + INNER_H).toFixed(2)} L ${first.x.toFixed(2)} ${(PAD_Y + INNER_H).toFixed(2)} Z`;
});

const gridlines = [0.25, 0.5, 0.75];

const lastPoint = computed<null | Point>(() => {
  const pts = points.value;
  return pts.length > 0 ? pts[pts.length - 1] : null;
});

const tickLabels = computed<string[]>(() => {
  const n = props.series.length;
  if (n === 0) return [];
  const last = n - 1;
  const t2 = Math.round(n / 3);
  const t3 = Math.round((n * 2) / 3);
  return [
    `J-${last}`,
    `J-${last - t2}`,
    `J-${last - t3}`,
    t('monitoring.repos.detail.activity_today'),
  ];
});

const totalCommits = computed(() => props.series.reduce((sum, v) => sum + v, 0));
</script>

<template>
  <div v-if="series.length === 0" class="activity-empty">
    {{ t('monitoring.repos.detail.no_activity') }}
  </div>
  <div v-else class="activity-chart">
    <svg
      :viewBox="`0 0 ${W} ${H}`"
      width="100%"
      height="auto"
      preserveAspectRatio="none"
      aria-hidden="true"
    >
      <defs>
        <linearGradient id="act-fill" x1="0" y1="0" x2="0" y2="1">
          <stop offset="0" stop-color="var(--hub-accent)" stop-opacity="0.32" />
          <stop offset="1" stop-color="var(--hub-accent)" stop-opacity="0" />
        </linearGradient>
      </defs>

      <line
        v-for="g in gridlines"
        :key="g"
        :x1="0"
        :x2="W"
        :y1="PAD_Y + INNER_H * g"
        :y2="PAD_Y + INNER_H * g"
        stroke="var(--hub-line)"
        stroke-width="0.5"
        stroke-dasharray="2 4"
      />

      <line
        :x1="0"
        :x2="W"
        :y1="PAD_Y + INNER_H"
        :y2="PAD_Y + INNER_H"
        stroke="var(--hub-line-strong)"
        stroke-width="0.6"
      />

      <path v-if="areaPath" :d="areaPath" fill="url(#act-fill)" class="area" />
      <path
        v-if="linePath"
        :d="linePath"
        fill="none"
        stroke="var(--hub-accent)"
        stroke-width="1.6"
        stroke-linecap="round"
        stroke-linejoin="round"
        class="line"
      />

      <circle
        v-for="(p, i) in points.slice(0, -1)"
        :key="`dot-${i}`"
        :cx="p.x"
        :cy="p.y"
        r="1.8"
        fill="var(--hub-bg-1)"
        stroke="var(--hub-accent)"
        stroke-width="1"
        class="point"
        :style="{ animationDelay: `${800 + i * 40}ms` }"
      />

      <template v-if="lastPoint">
        <circle :cx="lastPoint.x" :cy="lastPoint.y" r="6" fill="var(--hub-accent)" class="halo" />
        <circle
          :cx="lastPoint.x"
          :cy="lastPoint.y"
          r="2.6"
          fill="var(--hub-accent)"
          stroke="var(--hub-bg-1)"
          stroke-width="1.2"
          class="last-dot"
        />
        <text
          :x="lastPoint.x - 6"
          :y="lastPoint.y - 10"
          text-anchor="end"
          font-family="var(--hub-font-mono)"
          font-size="9"
          fill="var(--hub-fg-1)"
          class="last-label"
        >
          {{ series[series.length - 1] }}
          {{ t('monitoring.repos.detail.activity_commits_suffix') }}
        </text>
      </template>
    </svg>

    <div class="x-axis">
      <span v-for="(l, i) in tickLabels" :key="`tick-${i}`">{{ l }}</span>
    </div>

    <div class="activity-footer">
      <span class="activity-footer-label">
        {{ t('monitoring.repos.detail.activity_window') }}
      </span>
      <span class="activity-footer-value">
        {{ totalCommits }} {{ t('monitoring.repos.detail.activity_commits_suffix') }}
      </span>
    </div>
  </div>
</template>

<style scoped>
.activity-empty {
  padding: 18px;
  text-align: center;
  border: 1px dashed var(--hub-line);
  border-radius: 10px;
  font-family: var(--hub-font-mono);
  font-size: 10px;
  color: var(--hub-fg-3);
  letter-spacing: 0.06em;
}

.activity-chart {
  position: relative;
}
.activity-chart svg {
  display: block;
  overflow: visible;
}

.line {
  stroke-dasharray: 2000;
  stroke-dashoffset: 2000;
  animation: dash 1.6s var(--hub-ease) forwards;
}
.area {
  opacity: 0;
  animation: fade-in 700ms var(--hub-ease) 200ms forwards;
}
.point {
  opacity: 0;
  animation: fade-in 300ms var(--hub-ease) forwards;
}
.halo {
  transform-origin: center;
  opacity: 0;
  animation: halo-pulse 2s var(--hub-ease) 1.6s infinite;
}
.last-dot {
  opacity: 0;
  animation: fade-in 300ms var(--hub-ease) 1.6s forwards;
}
.last-label {
  opacity: 0;
  animation: fade-in 400ms var(--hub-ease) 2s forwards;
}

@keyframes dash {
  to {
    stroke-dashoffset: 0;
  }
}
@keyframes fade-in {
  to {
    opacity: 1;
  }
}
@keyframes halo-pulse {
  0% {
    opacity: 0.5;
    transform: scale(0.6);
  }
  50% {
    opacity: 0;
    transform: scale(1.6);
  }
  100% {
    opacity: 0;
    transform: scale(1.6);
  }
}

.x-axis {
  display: flex;
  justify-content: space-between;
  padding-top: 6px;
  border-top: 1px dashed var(--hub-line);
  margin-top: 2px;
  font-family: var(--hub-font-mono);
  font-size: 9px;
  color: var(--hub-fg-3);
  letter-spacing: 0.06em;
}

.activity-footer {
  display: flex;
  justify-content: space-between;
  margin-top: 10px;
  font-family: var(--hub-font-mono);
  font-size: 10px;
  letter-spacing: 0.06em;
}
.activity-footer-label {
  color: var(--hub-fg-3);
  text-transform: uppercase;
}
.activity-footer-value {
  color: var(--hub-fg-1);
}
</style>
