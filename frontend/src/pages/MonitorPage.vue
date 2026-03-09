<template>
  <q-page class="q-pa-md q-pa-lg-md">
    <div class="page-content">
      <div class="row items-center justify-between q-mb-md q-gutter-sm">
        <div>
          <div class="text-h6 text-weight-bold">Queue Monitoring</div>
          <div class="text-body2 text-grey-7">Live queue status across offices.</div>
        </div>

        <div class="row q-gutter-sm items-center">
          <q-select
            v-model="selectedOfficeId"
            outlined
            dense
            emit-value
            map-options
            clearable
            option-value="id"
            option-label="name"
            :options="officeOptions"
            label="Filter Office"
            style="min-width: 230px"
            @update:model-value="refreshMonitor"
          />

          <q-btn
            color="primary"
            icon="refresh"
            label="Refresh"
            no-caps
            :loading="queueStore.monitorLoading"
            @click="refreshMonitor"
          />
        </div>
      </div>

      <q-banner v-if="errorMessage" class="bg-red-1 text-negative rounded-borders q-mb-md">
        {{ errorMessage }}
      </q-banner>

      <div v-if="queueStore.monitorLoading" class="row q-col-gutter-md">
        <div v-for="n in 3" :key="n" class="col-12 col-md-4">
          <q-skeleton type="QCard" height="220px" />
        </div>
      </div>

      <div v-else-if="queueStore.monitorItems.length" class="row q-col-gutter-md">
        <div
          v-for="office in queueStore.monitorItems"
          :key="office.id"
          class="col-12 col-md-6 col-lg-4"
        >
          <MonitorCard :office="office" />
        </div>
      </div>

      <q-banner v-else class="bg-blue-1 text-primary rounded-borders">
        No monitor data available yet.
      </q-banner>
    </div>
  </q-page>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import MonitorCard from 'src/components/MonitorCard.vue'
import { useOfficeStore } from 'src/stores/office-store'
import { useQueueStore } from 'src/stores/queue-store'

const officeStore = useOfficeStore()
const queueStore = useQueueStore()

const selectedOfficeId = ref(null)
const errorMessage = ref('')
let refreshTimer = null

const officeOptions = computed(() => officeStore.publicOffices)

async function loadOffices() {
  try {
    await officeStore.fetchPublicOffices()
  } catch (error) {
    errorMessage.value = error.message
  }
}

async function refreshMonitor() {
  errorMessage.value = ''

  try {
    await queueStore.fetchMonitor(selectedOfficeId.value)
  } catch (error) {
    errorMessage.value = error.message
  }
}

onMounted(async () => {
  await loadOffices()
  await refreshMonitor()

  refreshTimer = setInterval(() => {
    refreshMonitor()
  }, 8000)
})

onBeforeUnmount(() => {
  if (refreshTimer) {
    clearInterval(refreshTimer)
  }
})
</script>
