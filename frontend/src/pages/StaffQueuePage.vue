<template>
  <q-page class="q-pa-md q-pa-lg-md">
    <div class="page-content">
      <q-card flat bordered class="q-pa-lg card-panel q-mb-md">
        <div class="row q-col-gutter-md items-end">
          <div class="col-12 col-md-6">
            <q-select
              v-model="selectedOfficeId"
              outlined
              dense
              emit-value
              map-options
              option-value="id"
              option-label="name"
              :options="queueStore.staffOffices"
              :loading="queueStore.staffLoading"
              label="Choose Office"
              @update:model-value="loadDashboard"
            />
          </div>

          <div class="col-12 col-md-6 row q-gutter-sm">
            <q-btn
              color="primary"
              label="Call Next"
              icon="campaign"
              no-caps
              :disable="!selectedOfficeId"
              :loading="queueStore.staffActionLoading"
              @click="handleCallNext"
            />
            <q-btn
              flat
              color="primary"
              label="Refresh"
              icon="refresh"
              no-caps
              :disable="!selectedOfficeId"
              :loading="queueStore.staffLoading"
              @click="loadDashboard"
            />
          </div>
        </div>
      </q-card>

      <q-banner v-if="errorMessage" class="bg-red-1 text-negative rounded-borders q-mb-md">
        {{ errorMessage }}
      </q-banner>

      <div v-if="queueStore.staffDashboard" class="row q-col-gutter-md">
        <div class="col-12 col-lg-4">
          <q-card flat bordered class="q-pa-lg card-panel full-height">
            <div class="text-subtitle1 text-weight-bold q-mb-sm">Now Serving</div>
            <div class="text-h4 text-primary text-weight-bold q-mb-sm">
              {{ servingTicket?.queue_number || '---' }}
            </div>
            <div class="text-caption text-grey-7 q-mb-md">
              {{ queueStore.staffDashboard.office.name }}
            </div>

            <div class="row q-gutter-sm">
              <q-btn
                flat
                color="primary"
                label="Call Again"
                icon="campaign"
                no-caps
                :disable="!servingTicket"
                @click="handleCallAgain"
              />
              <q-btn
                color="positive"
                label="Mark Done"
                no-caps
                :disable="!servingTicket"
                :loading="queueStore.staffActionLoading"
                @click="handleMarkDone"
              />
              <q-btn
                flat
                color="negative"
                label="Skip"
                no-caps
                :disable="!servingTicket"
                :loading="queueStore.staffActionLoading"
                @click="handleSkip(servingTicket?.id)"
              />
            </div>
          </q-card>
        </div>

        <div class="col-12 col-lg-8">
          <q-card flat bordered class="q-pa-lg card-panel full-height">
            <div class="text-subtitle1 text-weight-bold q-mb-sm">Waiting Queue</div>

            <q-table
              flat
              :rows="waitingRows"
              :columns="waitingColumns"
              row-key="id"
              :pagination="{ rowsPerPage: 10 }"
              :rows-per-page-options="[10, 20, 50]"
              no-data-label="No waiting queue"
            >
              <template #body-cell-actions="props">
                <q-td :props="props">
                  <div class="row q-gutter-xs">
                    <q-btn
                      size="sm"
                      color="primary"
                      no-caps
                      label="Serve"
                      :loading="queueStore.staffActionLoading"
                      @click="handleMarkServing(props.row.id)"
                    />
                    <q-btn
                      size="sm"
                      flat
                      color="negative"
                      no-caps
                      label="Skip"
                      :loading="queueStore.staffActionLoading"
                      @click="handleSkip(props.row.id)"
                    />
                  </div>
                </q-td>
              </template>
            </q-table>
          </q-card>
        </div>
      </div>

      <q-banner v-else class="bg-blue-1 text-primary rounded-borders">
        Select an office to open the staff queue dashboard.
      </q-banner>
    </div>
  </q-page>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useQueueStore } from 'src/stores/queue-store'
import { announceQueueNumber } from 'src/utils/voice-announcer'

const queueStore = useQueueStore()

const selectedOfficeId = ref(null)
const errorMessage = ref('')

const waitingColumns = [
  { name: 'queue_number', label: 'Queue Number', field: 'queue_number', align: 'left' },
  { name: 'queue_sequence', label: 'Sequence', field: 'queue_sequence', align: 'left' },
  { name: 'actions', label: 'Actions', field: 'actions', align: 'left' },
]

const servingTicket = computed(() => queueStore.staffDashboard?.serving || null)
const waitingRows = computed(() => queueStore.staffDashboard?.waiting || [])

function announceServingTicket(isRecall = false) {
  if (!servingTicket.value?.queue_number) {
    return
  }

  announceQueueNumber(servingTicket.value.queue_number, { isRecall })
}

async function loadStaffOffices() {
  try {
    await queueStore.fetchStaffOffices()

    if (!selectedOfficeId.value && queueStore.staffOffices.length > 0) {
      selectedOfficeId.value = queueStore.staffOffices[0].id
      await loadDashboard()
    }
  } catch (error) {
    errorMessage.value = error.message
  }
}

async function loadDashboard() {
  if (!selectedOfficeId.value) {
    return
  }

  try {
    await queueStore.fetchStaffDashboard(selectedOfficeId.value)
  } catch (error) {
    errorMessage.value = error.message
  }
}

async function handleCallNext() {
  if (!selectedOfficeId.value) {
    return
  }

  errorMessage.value = ''

  try {
    await queueStore.callNext(selectedOfficeId.value)
    announceServingTicket()
  } catch (error) {
    errorMessage.value = error.message
  }
}

async function handleMarkServing(queueTicketId) {
  errorMessage.value = ''

  try {
    await queueStore.markServing(queueTicketId, selectedOfficeId.value)
    announceServingTicket()
  } catch (error) {
    errorMessage.value = error.message
  }
}

function handleCallAgain() {
  announceServingTicket(true)
}

async function handleMarkDone() {
  if (!servingTicket.value) {
    return
  }

  errorMessage.value = ''

  try {
    await queueStore.markDone(servingTicket.value.id, selectedOfficeId.value)
  } catch (error) {
    errorMessage.value = error.message
  }
}

async function handleSkip(queueTicketId) {
  if (!queueTicketId) {
    return
  }

  errorMessage.value = ''

  try {
    await queueStore.skip(queueTicketId, selectedOfficeId.value)
  } catch (error) {
    errorMessage.value = error.message
  }
}

onMounted(async () => {
  await loadStaffOffices()
})
</script>
