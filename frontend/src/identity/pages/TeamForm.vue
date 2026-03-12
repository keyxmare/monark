<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { RouterLink, useRoute, useRouter } from 'vue-router'

import { useTeamStore } from '@/identity/stores/team'
import DashboardLayout from '@/shared/layouts/DashboardLayout.vue'

const route = useRoute()
const router = useRouter()
const { t } = useI18n()
const teamStore = useTeamStore()

const teamId = computed(() => route.params.id as string | undefined)
const isEditMode = computed(() => !!teamId.value)

const name = ref('')
const slug = ref('')
const description = ref('')
const submitting = ref(false)
const error = ref('')

onMounted(async () => {
  if (isEditMode.value && teamId.value) {
    await teamStore.fetchOne(teamId.value)
    if (teamStore.selectedTeam) {
      name.value = teamStore.selectedTeam.name
      slug.value = teamStore.selectedTeam.slug
      description.value = teamStore.selectedTeam.description ?? ''
    }
  }
})

async function handleSubmit() {
  error.value = ''
  submitting.value = true

  try {
    if (isEditMode.value && teamId.value) {
      await teamStore.update(teamId.value, {
        description: description.value || undefined,
        name: name.value,
        slug: slug.value,
      })
      router.push({ name: 'identity-teams-detail', params: { id: teamId.value } })
    } else {
      const team = await teamStore.create({
        description: description.value || undefined,
        name: name.value,
        slug: slug.value,
      })
      router.push({ name: 'identity-teams-detail', params: { id: team.id } })
    }
  } catch {
    error.value = isEditMode.value ? t('identity.teams.updateFailed') : t('identity.teams.createFailed')
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <DashboardLayout>
    <div data-testid="team-form-page">
      <div class="mb-6">
        <RouterLink
          :to="{ name: 'identity-teams-list' }"
          class="text-sm text-primary hover:text-primary-dark"
          data-testid="team-form-back"
        >
          &larr; {{ t('common.backTo', { page: t('identity.teams.title').toLowerCase() }) }}
        </RouterLink>
      </div>

      <div class="max-w-lg rounded-xl border border-border bg-surface p-6">
        <h2 class="mb-6 text-2xl font-bold text-text">
          {{ isEditMode ? t('identity.teams.editTeam') : t('identity.teams.createTeam') }}
        </h2>

        <form
          data-testid="team-form"
          @submit.prevent="handleSubmit"
        >
          <div
            v-if="error"
            class="mb-4 rounded-lg bg-danger/10 p-3 text-sm text-danger"
            role="alert"
            data-testid="team-form-error"
          >
            {{ error }}
          </div>

          <div class="mb-4">
            <label
              for="name"
              class="mb-1 block text-sm font-medium text-text"
            >{{ t('identity.teams.name') }}</label>
            <input
              id="name"
              v-model="name"
              type="text"
              required
              class="w-full rounded-lg border border-border px-3 py-2 text-text focus:border-primary focus:ring-2 focus:ring-primary/20 focus:outline-none"
              data-testid="team-form-name"
            >
          </div>

          <div class="mb-4">
            <label
              for="slug"
              class="mb-1 block text-sm font-medium text-text"
            >{{ t('identity.teams.slug') }}</label>
            <input
              id="slug"
              v-model="slug"
              type="text"
              required
              pattern="[a-z0-9]+(?:-[a-z0-9]+)*"
              class="w-full rounded-lg border border-border px-3 py-2 text-text focus:border-primary focus:ring-2 focus:ring-primary/20 focus:outline-none"
              data-testid="team-form-slug"
            >
          </div>

          <div class="mb-6">
            <label
              for="description"
              class="mb-1 block text-sm font-medium text-text"
            >{{ t('identity.teams.description') }}</label>
            <textarea
              id="description"
              v-model="description"
              rows="3"
              class="w-full rounded-lg border border-border px-3 py-2 text-text focus:border-primary focus:ring-2 focus:ring-primary/20 focus:outline-none"
              data-testid="team-form-description"
            />
          </div>

          <button
            type="submit"
            :disabled="submitting"
            class="w-full rounded-lg bg-primary px-4 py-2.5 font-medium text-white transition-colors hover:bg-primary-dark disabled:opacity-50"
            data-testid="team-form-submit"
          >
            {{ submitting ? t('common.saving') : (isEditMode ? t('identity.teams.updateTeam') : t('identity.teams.createTeam')) }}
          </button>
        </form>
      </div>
    </div>
  </DashboardLayout>
</template>
