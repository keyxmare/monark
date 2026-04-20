<template>
  <svg
    class="monark-mark"
    width="34"
    height="34"
    viewBox="0 0 34 34"
    xmlns="http://www.w3.org/2000/svg"
    aria-hidden="true"
  >
    <defs>
      <linearGradient id="mk-grad" x1="0" y1="0" x2="1" y2="1">
        <stop offset="0" stop-color="var(--hub-accent)" />
        <stop offset="1" stop-color="var(--hub-accent-2)" />
      </linearGradient>
    </defs>
    <g class="outer">
      <path
        d="M 17 4 L 30 11 L 30 23 L 17 30 L 4 23 L 4 11 Z"
        fill="none"
        stroke="url(#mk-grad)"
        stroke-width="1.3"
      />
      <path
        class="inner"
        d="M 17 10 L 24 14 L 24 20 L 17 24 L 10 20 L 10 14 Z"
        fill="none"
        stroke="var(--hub-fg-0)"
        stroke-width="1"
        opacity="0.7"
      />
      <circle class="core" cx="17" cy="17" r="2" fill="var(--hub-accent)" />
    </g>
  </svg>
</template>

<style scoped>
.monark-mark {
  display: block;
}
.outer {
  transform-origin: 17px 17px;
  animation: mark-float 5s ease-in-out infinite;
}
.inner {
  transform-origin: 17px 17px;
  animation: mark-float 5s ease-in-out infinite reverse;
}
.core {
  animation: mark-pulse 3s ease-in-out infinite;
}
@keyframes mark-float {
  0%,
  100% {
    transform: translateY(0);
  }
  50% {
    transform: translateY(-4px);
  }
}
@keyframes mark-pulse {
  0%,
  100% {
    opacity: 0.5;
    transform: scale(1);
  }
  50% {
    opacity: 1;
    transform: scale(1.15);
  }
}
</style>
