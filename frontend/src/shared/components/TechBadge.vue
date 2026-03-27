<script setup lang="ts">
import { computed } from 'vue'

const props = withDefaults(defineProps<{
  name: string
  version?: string
  size?: 'sm' | 'md'
}>(), {
  size: 'sm',
})

const DEVICON_MAP: Record<string, string> = {
  php: 'php/php-original',
  javascript: 'javascript/javascript-original',
  typescript: 'typescript/typescript-original',
  python: 'python/python-original',
  go: 'go/go-original-wordmark',
  rust: 'rust/rust-original',
  java: 'java/java-original',
  ruby: 'ruby/ruby-original',
  'c#': 'csharp/csharp-original',
  swift: 'swift/swift-original',
  kotlin: 'kotlin/kotlin-original',
  symfony: 'symfony/symfony-original',
  laravel: 'laravel/laravel-original',
  vue: 'vuejs/vuejs-original',
  'vue.js': 'vuejs/vuejs-original',
  react: 'react/react-original',
  angular: 'angular/angular-original',
  angularjs: 'angularjs/angularjs-original',
  'next.js': 'nextjs/nextjs-original',
  nextjs: 'nextjs/nextjs-original',
  nuxt: 'nuxtjs/nuxtjs-original',
  'nuxt.js': 'nuxtjs/nuxtjs-original',
  django: 'django/django-plain',
  rails: 'rails/rails-plain',
  'ruby on rails': 'rails/rails-plain',
  spring: 'spring/spring-original',
  express: 'express/express-original',
  nest: 'nestjs/nestjs-original',
  nestjs: 'nestjs/nestjs-original',
  flask: 'flask/flask-original',
  fastapi: 'fastapi/fastapi-original',
  tailwind: 'tailwindcss/tailwindcss-original',
  tailwindcss: 'tailwindcss/tailwindcss-original',
  docker: 'docker/docker-original',
  nodejs: 'nodejs/nodejs-original',
  'node.js': 'nodejs/nodejs-original',
  postgresql: 'postgresql/postgresql-original',
  mysql: 'mysql/mysql-original',
  mongodb: 'mongodb/mongodb-original',
  redis: 'redis/redis-original',
}

const DEVICON_BASE = 'https://cdn.jsdelivr.net/gh/devicons/devicon@latest/icons'

function resolveIconKey(name: string, version?: string): string {
  const key = name.toLowerCase()
  if (key === 'angular' && version) {
    const major = Number.parseInt(version.split('.')[0], 10)
    if (!Number.isNaN(major) && major < 2) {
      return 'angularjs'
    }
  }
  return key
}

const iconUrl = computed(() => {
  const key = resolveIconKey(props.name, props.version)
  const path = DEVICON_MAP[key]
  return path ? `${DEVICON_BASE}/${path}.svg` : null
})

const sizeClass = computed(() => (props.size === 'sm' ? 'h-5 w-5' : 'h-6 w-6'))
const textClass = computed(() => (props.size === 'sm' ? 'text-xs' : 'text-sm'))
</script>

<template>
  <span
    class="inline-flex items-center gap-1.5"
    :title="name"
    :data-testid="`tech-badge-${name}`"
  >
    <img
      v-if="iconUrl"
      :src="iconUrl"
      :alt="name"
      :class="sizeClass"
    >
    <span
      v-else
      :class="textClass"
      class="font-medium text-text-muted"
    >{{ name }}</span>
  </span>
</template>
