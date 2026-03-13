<script setup lang="ts">
import { computed } from 'vue'

const props = withDefaults(defineProps<{
  name: string
  size?: 'sm' | 'md'
}>(), {
  size: 'sm',
})

const TECH_COLORS: Record<string, string> = {
  php: '#4F5D95',
  javascript: '#f1e05a',
  typescript: '#3178c6',
  python: '#3572A5',
  go: '#00ADD8',
  rust: '#dea584',
  java: '#b07219',
  ruby: '#701516',
  'c#': '#178600',
  swift: '#F05138',
  kotlin: '#A97BFF',
  symfony: '#000000',
  laravel: '#FF2D20',
  vue: '#41b883',
  'vue.js': '#41b883',
  react: '#61dafb',
  angular: '#dd0031',
  'next.js': '#000000',
  nuxt: '#00DC82',
  django: '#092E20',
  rails: '#CC0000',
  spring: '#6DB33F',
  express: '#000000',
}

const DEFAULT_COLOR = '#6b7280'

const color = computed(() => TECH_COLORS[props.name.toLowerCase()] ?? DEFAULT_COLOR)
const sizeClass = computed(() => (props.size === 'sm' ? 'text-xs' : 'text-sm'))
</script>

<template>
  <span
    class="inline-flex items-center gap-1.5"
    :data-testid="`tech-badge-${name}`"
  >
    <span
      class="h-2.5 w-2.5 rounded-full"
      :style="{ backgroundColor: color }"
    />
    <span :class="sizeClass">{{ name }}</span>
  </span>
</template>
