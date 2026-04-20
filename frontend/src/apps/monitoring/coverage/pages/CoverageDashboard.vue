<script setup lang="ts">
import { onMounted } from 'vue';

import CoverageProjectList from '@/apps/monitoring/coverage/components/CoverageProjectList.vue';
import CoverageSummaryCard from '@/apps/monitoring/coverage/components/CoverageSummaryCard.vue';
import { useCoverageStore } from '@/apps/monitoring/coverage/stores/coverage';
import SyncButton from '@/hub/shared/components/SyncButton.vue';
import { useGlobalSync } from '@/hub/shared/composables/useGlobalSync';

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

</template>
