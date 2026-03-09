<template>
  <q-page class="q-pa-md q-pa-lg-md">
    <div class="page-content">
      <div class="row q-col-gutter-lg items-stretch">
        <div class="col-12 col-md-5">
          <q-card flat bordered class="q-pa-lg card-panel full-height">
            <q-card-section class="q-pa-none q-mb-md">
              <div class="text-h6 text-weight-bold">Generate Queue Number</div>
              <div class="text-body2 text-grey-7">
                Select an office and get your queue number instantly.
              </div>
            </q-card-section>

            <q-banner
              v-if="errorMessage"
              class="bg-red-1 text-negative rounded-borders q-mb-md"
            >
              {{ errorMessage }}
            </q-banner>

            <q-select
              v-model="selectedOfficeId"
              outlined
              dense
              emit-value
              map-options
              option-value="id"
              option-label="name"
              label="Select Office"
              :options="officeStore.publicOffices"
              :loading="officeStore.loading"
              class="q-mb-md"
              @update:model-value="handleOfficeChange"
            >
              <template #option="scope">
                <q-item v-bind="scope.itemProps">
                  <q-item-section>
                    <q-item-label>{{ scope.opt.name }}</q-item-label>
                    <q-item-label caption>{{ scope.opt.prefix }}</q-item-label>
                  </q-item-section>
                </q-item>
              </template>
            </q-select>

            <q-btn
              color="primary"
              label="Generate Queue"
              no-caps
              class="full-width"
              :disable="!selectedOfficeId"
              :loading="queueStore.ticketLoading"
              @click="handleGenerateQueue"
            />

            <q-card-section v-if="queueStore.generatedTicket" class="q-pa-none q-mt-lg">
              <div class="text-caption text-grey-7">Your Queue Number</div>
              <div class="ticket-number text-weight-bold q-mt-xs">
                {{ queueStore.generatedTicket.queue_number }}
              </div>
              <div class="text-caption text-grey-6 q-mt-sm">
                Keep this number and wait for your turn on the monitor.
              </div>
            </q-card-section>
          </q-card>
        </div>

        <div class="col-12 col-md-7">
          <q-card flat bordered class="q-pa-lg card-panel full-height">
            <q-card-section class="q-pa-none row items-center justify-between q-mb-md">
              <div>
                <div class="text-h6 text-weight-bold">Now Serving</div>
                <div class="text-body2 text-grey-7">Real-time status of selected office.</div>
              </div>
              <q-btn
                flat
                round
                icon="refresh"
                color="primary"
                :loading="queueStore.monitorLoading"
                @click="refreshMonitor"
              />
            </q-card-section>

            <q-inner-loading :showing="queueStore.monitorLoading">
              <q-spinner-bars color="primary" size="42px" />
            </q-inner-loading>

            <div v-if="selectedOfficeMonitor" class="monitor-focus q-pa-md">
              <div class="text-caption text-grey-7">Current Queue</div>
              <div class="text-h3 text-primary text-weight-bold q-mb-sm">
                {{ selectedOfficeMonitor.now_serving || '---' }}
              </div>

              <div class="row q-col-gutter-md">
                <div class="col-12 col-sm-6">
                  <div class="text-caption text-grey-7">Next In Line</div>
                  <div class="text-subtitle1 text-weight-medium">
                    {{ selectedOfficeMonitor.next_in_line || '---' }}
                  </div>
                </div>
                <div class="col-12 col-sm-6">
                  <div class="text-caption text-grey-7">Waiting Count</div>
                  <div class="text-subtitle1 text-weight-medium">
                    {{ selectedOfficeMonitor.waiting_count || 0 }}
                  </div>
                </div>
              </div>
            </div>

            <q-banner v-else class="bg-blue-1 text-primary rounded-borders">
              Select an office to see its serving status.
            </q-banner>
          </q-card>
        </div>
      </div>
    </div>
  </q-page>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useOfficeStore } from 'src/stores/office-store'
import { useQueueStore } from 'src/stores/queue-store'

const officeStore = useOfficeStore()
const queueStore = useQueueStore()

const selectedOfficeId = ref(null)
const errorMessage = ref('')

const selectedOfficeMonitor = computed(() => queueStore.monitorItems[0] || null)

async function loadOffices() {
  try {
    await officeStore.fetchPublicOffices()
  } catch (error) {
    errorMessage.value = error.message
  }
}

async function refreshMonitor() {
  if (!selectedOfficeId.value) {
    return
  }

  try {
    await queueStore.fetchMonitor(selectedOfficeId.value)
  } catch (error) {
    errorMessage.value = error.message
  }
}

async function handleOfficeChange() {
  await refreshMonitor()
}

async function handleGenerateQueue() {
  if (!selectedOfficeId.value) {
    return
  }

  errorMessage.value = ''

  try {
    await queueStore.generateQueue(selectedOfficeId.value)
    await refreshMonitor()
  } catch (error) {
    errorMessage.value = error.message
  }
}

onMounted(async () => {
  await loadOffices()
})
</script>
