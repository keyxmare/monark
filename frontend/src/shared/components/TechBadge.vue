<script setup lang="ts">
import { computed } from 'vue';

const props = withDefaults(
  defineProps<{
    name: string;
    size?: 'md' | 'sm';
    version?: string;
  }>(),
  {
    size: 'sm',
  },
);

const DEVICON_MAP: Record<string, string> = {
  angular: 'angular/angular-original',
  angularjs: 'angularjs/angularjs-original',
  'c#': 'csharp/csharp-original',
  django: 'django/django-plain',
  docker: 'docker/docker-original',
  express: 'express/express-original',
  fastapi: 'fastapi/fastapi-original',
  flask: 'flask/flask-original',
  go: 'go/go-original-wordmark',
  java: 'java/java-original',
  javascript: 'javascript/javascript-original',
  kotlin: 'kotlin/kotlin-original',
  laravel: 'laravel/laravel-original',
  mongodb: 'mongodb/mongodb-original',
  mysql: 'mysql/mysql-original',
  nest: 'nestjs/nestjs-original',
  nestjs: 'nestjs/nestjs-original',
  'next.js': 'nextjs/nextjs-original',
  nextjs: 'nextjs/nextjs-original',
  'node.js': 'nodejs/nodejs-original',
  nodejs: 'nodejs/nodejs-original',
  nuxt: 'nuxtjs/nuxtjs-original',
  'nuxt.js': 'nuxtjs/nuxtjs-original',
  php: 'php/php-original',
  postgresql: 'postgresql/postgresql-original',
  python: 'python/python-original',
  rails: 'rails/rails-plain',
  react: 'react/react-original',
  redis: 'redis/redis-original',
  ruby: 'ruby/ruby-original',
  'ruby on rails': 'rails/rails-plain',
  rust: 'rust/rust-original',
  spring: 'spring/spring-original',
  swift: 'swift/swift-original',
  symfony: 'symfony/symfony-original',
  tailwind: 'tailwindcss/tailwindcss-original',
  tailwindcss: 'tailwindcss/tailwindcss-original',
  typescript: 'typescript/typescript-original',
  vue: 'vuejs/vuejs-original',
  'vue.js': 'vuejs/vuejs-original',
};

const DEVICON_BASE = 'https://cdn.jsdelivr.net/gh/devicons/devicon@latest/icons';

function resolveIconKey(name: string, version?: string): string {
  const key = name.toLowerCase();
  if (key === 'angular' && version) {
    const major = Number.parseInt(version.split('.')[0], 10);
    if (!Number.isNaN(major) && major < 2) {
      return 'angularjs';
    }
  }
  return key;
}

const iconUrl = computed(() => {
  const key = resolveIconKey(props.name, props.version);
  const path = DEVICON_MAP[key];
  return path ? `${DEVICON_BASE}/${path}.svg` : null;
});

const sizeClass = computed(() => (props.size === 'sm' ? 'h-5 w-5' : 'h-6 w-6'));
const textClass = computed(() => (props.size === 'sm' ? 'text-xs' : 'text-sm'));
</script>

<template>
  <span class="inline-flex items-center gap-1.5" :title="name" :data-testid="`tech-badge-${name}`">
    <img v-if="iconUrl" :src="iconUrl" :alt="name" :class="sizeClass" />
    <span v-else :class="textClass" class="font-medium text-text-muted">{{ name }}</span>
  </span>
</template>
