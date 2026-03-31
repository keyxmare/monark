<script setup lang="ts">
import { onMounted } from 'vue';

import CoverageProjectList from '@/coverage/components/CoverageProjectList.vue';
import CoverageSummaryCard from '@/coverage/components/CoverageSummaryCard.vue';
import { useCoverageStore } from '@/coverage/stores/coverage';
import SyncButton from '@/shared/components/SyncButton.vue';
import { useGlobalSync } from '@/shared/composables/useGlobalSync';
import DashboardLayout from '@/shared/layouts/DashboardLayout.vue';

const coverageStore = useCoverageStore();
const { onStepCompleted } = useGlobalSync();

onMounted(() => {
  coverageStore.fetchDashboard();
});

onStepCompleted((step) => {
  if (step === 'sync_coverage') {
    coverageStore.fetchDashboard();
  }
});
</script>

<template>
  <DashboardLayout>
    <div class="space-y-6 p-6">
      <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold">Coverage</h1>
        <SyncButton />
      </div>
      <div v-if="coverageStore.loading" class="text-text-muted">Chargement...</div>
      <div v-else-if="coverageStore.error" class="text-red-500">{{ coverageStore.error }}</div>
      <template v-else-if="coverageStore.dashboard">
        <CoverageSummaryCard :summary="coverageStore.dashboard.summary" />
        <CoverageProjectList :projects="coverageStore.dashboard.projects" />
      </template>
    </div>
  </DashboardLayout>
</template>
