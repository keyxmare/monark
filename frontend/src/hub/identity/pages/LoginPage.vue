<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { RouterLink, useRouter } from 'vue-router';

import { useAuthStore } from '@/hub/identity/stores/auth';
import AuthLayout from '@/hub/shared/layouts/AuthLayout.vue';

const router = useRouter();
const { t } = useI18n();
const authStore = useAuthStore();
const email = ref('');
const password = ref('');
const error = ref('');
const submitting = ref(false);
const mounted = ref(false);

onMounted(() => {
  requestAnimationFrame(() => (mounted.value = true));
});

async function handleSubmit() {
  error.value = '';
  submitting.value = true;

  try {
    await authStore.login(email.value, password.value);
    router.push({ name: 'hub-home' });
  } catch {
    error.value = t('identity.auth.invalidCredentials');
  } finally {
    submitting.value = false;
  }
}
</script>

<template>
  <AuthLayout>
    <div class="login" data-testid="login-page">
      <div class="eyebrow" :class="{ show: mounted }">
        — {{ t('identity.auth.email') }} · access
      </div>
      <h1 class="display">
        <span class="word" :class="{ show: mounted }" style="--d: 80ms">{{
          t('identity.auth.signInTitle')
        }}</span>
      </h1>

      <form
        class="form"
        :class="{ show: mounted }"
        data-testid="login-form"
        @submit.prevent="handleSubmit"
      >
        <div v-if="error" class="alert" role="alert" data-testid="login-error">
          <span class="alert-dot" />
          <span>{{ error }}</span>
        </div>

        <div class="field">
          <label for="email">{{ t('identity.auth.email') }}</label>
          <input
            id="email"
            v-model="email"
            type="email"
            required
            autocomplete="email"
            data-testid="login-email"
          />
        </div>

        <div class="field">
          <label for="password">{{ t('identity.auth.password') }}</label>
          <input
            id="password"
            v-model="password"
            type="password"
            required
            autocomplete="current-password"
            data-testid="login-password"
          />
        </div>

        <button type="submit" class="submit" :disabled="submitting" data-testid="login-submit">
          <span>{{ submitting ? t('identity.auth.signingIn') : t('identity.auth.signIn') }}</span>
          <span class="submit-arrow">→</span>
        </button>

        <p class="swap">
          {{ t('identity.auth.noAccount') }}
          <RouterLink :to="{ name: 'register' }" data-testid="login-register-link">
            {{ t('identity.auth.signUp') }}
          </RouterLink>
        </p>
      </form>
    </div>
  </AuthLayout>
</template>

<style scoped>
.login {
  color: var(--hub-fg-0);
}

.eyebrow {
  font-family: var(--hub-font-mono);
  font-size: 11px;
  letter-spacing: 0.2em;
  text-transform: uppercase;
  color: var(--hub-fg-2);
  opacity: 0;
  transform: translateY(10px);
  transition:
    opacity 600ms var(--hub-ease),
    transform 600ms var(--hub-ease);
}
.eyebrow.show {
  opacity: 1;
  transform: translateY(0);
}

.display {
  margin: 10px 0 28px;
  font-family: var(--hub-font-serif);
  font-style: italic;
  font-weight: 300;
  font-size: clamp(34px, 4vw, 48px);
  line-height: 1;
  letter-spacing: -0.02em;
  color: var(--hub-fg-0);
}
.word {
  display: inline-block;
  opacity: 0;
  transform: translateY(0.25em);
  filter: blur(6px);
  transition:
    opacity 700ms var(--hub-ease-out),
    transform 700ms var(--hub-ease-out),
    filter 700ms var(--hub-ease-out);
  transition-delay: var(--d, 0ms);
}
.word.show {
  opacity: 1;
  transform: translateY(0);
  filter: blur(0);
}

.form {
  display: flex;
  flex-direction: column;
  gap: 20px;
  opacity: 0;
  transform: translateY(10px);
  transition:
    opacity 700ms 300ms var(--hub-ease),
    transform 700ms 300ms var(--hub-ease);
}
.form.show {
  opacity: 1;
  transform: translateY(0);
}

.alert {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 12px 14px;
  border-radius: 10px;
  background: color-mix(in oklab, var(--hub-danger) 12%, transparent);
  border: 1px solid color-mix(in oklab, var(--hub-danger) 40%, transparent);
  color: color-mix(in oklab, var(--hub-danger) 80%, white);
  font-family: var(--hub-font-mono);
  font-size: 12px;
  letter-spacing: 0.02em;
}
.alert-dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: var(--hub-danger);
  box-shadow: 0 0 8px var(--hub-danger);
  flex-shrink: 0;
}

.field {
  display: flex;
  flex-direction: column;
  gap: 6px;
}
.field label {
  font-family: var(--hub-font-mono);
  font-size: 10px;
  letter-spacing: 0.18em;
  text-transform: uppercase;
  color: var(--hub-fg-2);
}
.field input {
  width: 100%;
  padding: 12px 14px;
  font-family: var(--hub-font-sans);
  font-size: 15px;
  color: var(--hub-fg-0);
  background: color-mix(in oklab, var(--hub-bg-1) 80%, transparent);
  border: 1px solid var(--hub-line);
  border-radius: 10px;
  outline: none;
  transition:
    border-color 200ms var(--hub-ease),
    background 200ms var(--hub-ease),
    box-shadow 200ms var(--hub-ease);
}
.field input::placeholder {
  color: var(--hub-fg-3);
}
.field input:hover {
  border-color: var(--hub-line-strong);
}
.field input:focus {
  border-color: color-mix(in oklab, var(--hub-accent) 70%, transparent);
  background: color-mix(in oklab, var(--hub-bg-2) 85%, transparent);
  box-shadow: 0 0 0 3px color-mix(in oklab, var(--hub-accent) 18%, transparent);
}

.submit {
  margin-top: 8px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  padding: 14px 22px;
  font-family: var(--hub-font-sans);
  font-weight: 500;
  font-size: 15px;
  letter-spacing: 0.02em;
  color: var(--hub-bg-0);
  background: linear-gradient(135deg, var(--hub-accent), var(--hub-accent-3));
  border: 1px solid transparent;
  border-radius: 10px;
  cursor: pointer;
  transition:
    transform 180ms var(--hub-ease),
    filter 200ms var(--hub-ease),
    opacity 200ms var(--hub-ease);
}
.submit:hover:not(:disabled) {
  transform: translateY(-1px);
  filter: brightness(1.08);
}
.submit:active:not(:disabled) {
  transform: translateY(0);
}
.submit:disabled {
  opacity: 0.55;
  cursor: progress;
}
.submit-arrow {
  font-family: var(--hub-font-mono);
  transition: transform 240ms var(--hub-ease);
}
.submit:hover:not(:disabled) .submit-arrow {
  transform: translateX(4px);
}

.swap {
  margin: 8px 0 0;
  text-align: center;
  font-family: var(--hub-font-mono);
  font-size: 12px;
  color: var(--hub-fg-2);
  letter-spacing: 0.04em;
}
.swap a {
  color: var(--hub-accent);
  text-decoration: none;
  margin-left: 6px;
  border-bottom: 1px dashed color-mix(in oklab, var(--hub-accent) 50%, transparent);
  transition: color 180ms var(--hub-ease);
}
.swap a:hover {
  color: var(--hub-accent-2);
}
</style>
