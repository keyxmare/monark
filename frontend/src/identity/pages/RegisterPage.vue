<script setup lang="ts">
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { RouterLink, useRouter } from 'vue-router';

import { useAuthStore } from '@/identity/stores/auth';
import AuthLayout from '@/shared/layouts/AuthLayout.vue';

const router = useRouter();
const { t } = useI18n();
const authStore = useAuthStore();
const firstName = ref('');
const lastName = ref('');
const email = ref('');
const password = ref('');
const error = ref('');
const submitting = ref(false);

async function handleSubmit() {
  error.value = '';
  submitting.value = true;

  try {
    await authStore.register(email.value, password.value, firstName.value, lastName.value);
    await authStore.login(email.value, password.value);
    router.push({ name: 'dashboard' });
  } catch {
    error.value = t('identity.auth.registerFailed');
  } finally {
    submitting.value = false;
  }
}
</script>

<template>
  <AuthLayout>
    <div data-testid="register-page">
      <h1 class="mb-6 text-center text-2xl font-bold text-text">
        {{ t('identity.auth.createAccount') }}
      </h1>

      <form
        data-testid="register-form"
        @submit.prevent="handleSubmit"
      >
        <div
          v-if="error"
          class="mb-4 rounded-lg bg-danger/10 p-3 text-sm text-danger"
          role="alert"
          data-testid="register-error"
        >
          {{ error }}
        </div>

        <div class="mb-4">
          <label
            for="firstName"
            class="mb-1 block text-sm font-medium text-text"
          >{{
            t('identity.users.firstName')
          }}</label>
          <input
            id="firstName"
            v-model="firstName"
            type="text"
            required
            autocomplete="given-name"
            class="w-full rounded-lg border border-border px-3 py-2 text-text focus:border-primary focus:ring-2 focus:ring-primary/20 focus:outline-none"
            data-testid="register-first-name"
          >
        </div>

        <div class="mb-4">
          <label
            for="lastName"
            class="mb-1 block text-sm font-medium text-text"
          >{{
            t('identity.users.lastName')
          }}</label>
          <input
            id="lastName"
            v-model="lastName"
            type="text"
            required
            autocomplete="family-name"
            class="w-full rounded-lg border border-border px-3 py-2 text-text focus:border-primary focus:ring-2 focus:ring-primary/20 focus:outline-none"
            data-testid="register-last-name"
          >
        </div>

        <div class="mb-4">
          <label
            for="email"
            class="mb-1 block text-sm font-medium text-text"
          >{{
            t('identity.auth.email')
          }}</label>
          <input
            id="email"
            v-model="email"
            type="email"
            required
            autocomplete="email"
            class="w-full rounded-lg border border-border px-3 py-2 text-text focus:border-primary focus:ring-2 focus:ring-primary/20 focus:outline-none"
            data-testid="register-email"
          >
        </div>

        <div class="mb-6">
          <label
            for="password"
            class="mb-1 block text-sm font-medium text-text"
          >{{
            t('identity.auth.password')
          }}</label>
          <input
            id="password"
            v-model="password"
            type="password"
            required
            autocomplete="new-password"
            class="w-full rounded-lg border border-border px-3 py-2 text-text focus:border-primary focus:ring-2 focus:ring-primary/20 focus:outline-none"
            data-testid="register-password"
          >
        </div>

        <button
          type="submit"
          :disabled="submitting"
          class="w-full rounded-lg bg-primary px-4 py-2.5 font-medium text-white transition-colors hover:bg-primary-dark disabled:opacity-50"
          data-testid="register-submit"
        >
          {{
            submitting ? t('identity.auth.creatingAccount') : t('identity.auth.createAccountBtn')
          }}
        </button>

        <p class="mt-4 text-center text-sm text-text-muted">
          {{ t('identity.auth.hasAccount') }}
          <RouterLink
            :to="{ name: 'login' }"
            class="text-primary hover:text-primary-dark"
            data-testid="register-login-link"
          >
            {{ t('identity.auth.signIn') }}
          </RouterLink>
        </p>
      </form>
    </div>
  </AuthLayout>
</template>
