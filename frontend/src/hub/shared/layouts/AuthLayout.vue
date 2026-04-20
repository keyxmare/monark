<script setup lang="ts">
import { RouterLink } from 'vue-router';

import AmbientBackground from '@/hub/home/AmbientBackground.vue';
import MonarkMark from '@/hub/home/MonarkMark.vue';
</script>

<template>
  <div class="auth-root">
    <AmbientBackground />

    <main class="auth-shell">
      <RouterLink to="/" class="brand" data-testid="auth-brand">
        <MonarkMark />
        <div class="brand-text">
          <span class="brand-tag">MONARK /</span>
          <span class="brand-name">personal dev hub</span>
        </div>
      </RouterLink>

      <section class="auth-card" data-testid="auth-container">
        <slot />
      </section>

      <footer class="auth-foot">
        <span>◈ monark</span>
        <span class="sep">·</span>
        <span>v0.1.0</span>
      </footer>
    </main>
  </div>
</template>

<style scoped>
.auth-root {
  --hub-bg-0: #07070a;
  --hub-bg-1: #101014;
  --hub-bg-2: #16171c;
  --hub-bg-3: #1d1e24;
  --hub-fg-0: #e8e8ea;
  --hub-fg-1: #b4b4b8;
  --hub-fg-2: #72747a;
  --hub-fg-3: #42444a;
  --hub-line: rgba(255, 255, 255, 0.07);
  --hub-line-strong: rgba(255, 255, 255, 0.16);
  --hub-accent: oklch(0.78 0.16 322);
  --hub-accent-2: oklch(0.78 0.16 102);
  --hub-accent-3: oklch(0.78 0.16 242);
  --hub-danger: oklch(0.68 0.18 25);
  --hub-good: oklch(0.78 0.14 150);
  --hub-ease: cubic-bezier(0.2, 0.8, 0.2, 1);
  --hub-ease-out: cubic-bezier(0.16, 1, 0.3, 1);
  --hub-font-sans: 'Darker Grotesque', ui-sans-serif, system-ui, -apple-system, sans-serif;
  --hub-font-mono: 'JetBrains Mono', ui-monospace, Menlo, monospace;
  --hub-font-serif: 'Fraunces', 'Instrument Serif', ui-serif, Georgia, serif;

  position: fixed;
  inset: 0;
  overflow: auto;
  background: var(--hub-bg-0);
  color: var(--hub-fg-0);
  font-family: var(--hub-font-sans);
  -webkit-font-smoothing: antialiased;
  text-rendering: optimizeLegibility;
}

.auth-root ::selection {
  background: var(--hub-accent);
  color: var(--hub-bg-0);
}

.auth-shell {
  position: relative;
  z-index: 5;
  min-height: 100vh;
  display: grid;
  grid-template-rows: auto 1fr auto;
  padding: 32px clamp(24px, 4vw, 80px);
  max-width: 560px;
  margin: 0 auto;
}

.brand {
  display: flex;
  align-items: center;
  gap: 14px;
  color: inherit;
  text-decoration: none;
  padding: 8px 4px;
}
.brand-text {
  display: flex;
  flex-direction: column;
  line-height: 1.1;
}
.brand-tag {
  font-family: var(--hub-font-mono);
  font-size: 11px;
  color: var(--hub-fg-2);
  letter-spacing: 0.08em;
}
.brand-name {
  font-family: var(--hub-font-serif);
  font-style: italic;
  font-size: 20px;
}

.auth-card {
  align-self: center;
  justify-self: stretch;
  padding: clamp(32px, 4vw, 48px);
  background: color-mix(in oklab, var(--hub-bg-2) 80%, transparent);
  border: 1px solid var(--hub-line-strong);
  border-radius: 18px;
  backdrop-filter: blur(14px);
  -webkit-backdrop-filter: blur(14px);
  box-shadow:
    0 1px 0 rgba(255, 255, 255, 0.04) inset,
    0 20px 60px rgba(0, 0, 0, 0.45);
}

.auth-foot {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 10px;
  padding-top: 24px;
  font-family: var(--hub-font-mono);
  font-size: 11px;
  color: var(--hub-fg-3);
  letter-spacing: 0.08em;
}
.auth-foot .sep {
  opacity: 0.6;
}

.auth-root :focus-visible {
  outline: 2px solid var(--hub-accent);
  outline-offset: 2px;
  border-radius: 4px;
}
</style>
