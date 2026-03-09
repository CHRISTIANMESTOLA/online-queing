<template>
  <q-page class="q-pa-md q-pa-lg-md">
    <div class="page-content">
      <div class="row items-center justify-between q-mb-md">
        <div>
          <div class="text-h6 text-weight-bold">Office Management</div>
          <div class="text-body2 text-grey-7">Create offices and assign staff members.</div>
        </div>

        <q-btn color="primary" icon="add" no-caps label="New Office" @click="openCreateOffice" />
      </div>

      <q-banner v-if="errorMessage" class="bg-red-1 text-negative rounded-borders q-mb-md">
        {{ errorMessage }}
      </q-banner>

      <q-card flat bordered class="card-panel q-pa-md">
        <q-table
          flat
          :rows="officeStore.adminOffices"
          :columns="officeColumns"
          row-key="id"
          :loading="officeStore.loading"
          :pagination="{ rowsPerPage: 10 }"
        >
          <template #body-cell-status="props">
            <q-td :props="props">
              <q-badge :color="props.row.is_active ? 'positive' : 'grey'">
                {{ props.row.is_active ? 'Active' : 'Inactive' }}
              </q-badge>
            </q-td>
          </template>

          <template #body-cell-staff="props">
            <q-td :props="props">
              <div class="row q-gutter-xs">
                <q-chip
                  v-for="staff in props.row.staff"
                  :key="staff.id"
                  dense
                  color="blue-1"
                  text-color="primary"
                >
                  {{ staff.name }}
                </q-chip>
                <span v-if="props.row.staff.length === 0" class="text-caption text-grey-6">No staff</span>
              </div>
            </q-td>
          </template>

          <template #body-cell-actions="props">
            <q-td :props="props">
              <div class="row q-gutter-xs">
                <q-btn
                  size="sm"
                  flat
                  color="primary"
                  icon="edit"
                  @click="openEditOffice(props.row)"
                />
                <q-btn
                  size="sm"
                  flat
                  color="secondary"
                  icon="group_add"
                  @click="openStaffDialog(props.row)"
                />
                <q-btn
                  size="sm"
                  flat
                  color="negative"
                  icon="delete"
                  @click="removeOffice(props.row)"
                />
              </div>
            </q-td>
          </template>
        </q-table>
      </q-card>

      <q-dialog v-model="officeDialogOpen" persistent>
        <q-card style="min-width: 380px">
          <q-card-section>
            <div class="text-h6">{{ editingOfficeId ? 'Edit Office' : 'Create Office' }}</div>
          </q-card-section>

          <q-card-section class="q-gutter-md">
            <q-input v-model="officeForm.name" outlined dense label="Office Name" />
            <q-input
              v-model="officeForm.prefix"
              outlined
              dense
              label="Prefix (2-10 letters)"
              maxlength="10"
            />
            <q-toggle v-model="officeForm.is_active" label="Active" color="positive" />
          </q-card-section>

          <q-card-actions align="right">
            <q-btn flat label="Cancel" @click="officeDialogOpen = false" />
            <q-btn
              color="primary"
              :label="editingOfficeId ? 'Update' : 'Create'"
              :loading="officeStore.saving"
              @click="submitOffice"
            />
          </q-card-actions>
        </q-card>
      </q-dialog>

      <q-dialog v-model="staffDialogOpen" persistent>
        <q-card style="min-width: 520px">
          <q-card-section>
            <div class="text-h6">Manage Office Staff</div>
            <div class="text-caption text-grey-7">{{ selectedOffice?.name }}</div>
          </q-card-section>

          <q-card-section>
            <div class="text-subtitle2 q-mb-sm">Assigned Staff</div>
            <div class="row q-gutter-sm q-mb-md">
              <q-chip
                v-for="staff in selectedOffice?.staff || []"
                :key="staff.id"
                removable
                color="blue-1"
                text-color="primary"
                @remove="unassignStaff(staff.id)"
              >
                {{ staff.name }}
              </q-chip>
              <span
                v-if="(selectedOffice?.staff || []).length === 0"
                class="text-caption text-grey-6"
              >
                No staff assigned.
              </span>
            </div>

            <q-select
              v-model="selectedStaffId"
              outlined
              dense
              emit-value
              map-options
              option-value="id"
              option-label="name"
              :options="availableStaffForSelectedOffice"
              label="Assign Staff"
            >
              <template #option="scope">
                <q-item v-bind="scope.itemProps">
                  <q-item-section>
                    <q-item-label>{{ scope.opt.name }}</q-item-label>
                    <q-item-label caption>{{ scope.opt.email }}</q-item-label>
                  </q-item-section>
                </q-item>
              </template>
            </q-select>
          </q-card-section>

          <q-card-actions align="between">
            <q-btn flat label="Close" @click="staffDialogOpen = false" />
            <q-btn
              color="primary"
              label="Assign"
              :disable="!selectedStaffId"
              :loading="officeStore.saving"
              @click="assignStaff"
            />
          </q-card-actions>
        </q-card>
      </q-dialog>
    </div>
  </q-page>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { useQuasar } from 'quasar'
import { useOfficeStore } from 'src/stores/office-store'

const $q = useQuasar()
const officeStore = useOfficeStore()

const errorMessage = ref('')
const officeDialogOpen = ref(false)
const staffDialogOpen = ref(false)
const editingOfficeId = ref(null)
const selectedOfficeId = ref(null)
const selectedStaffId = ref(null)

const officeForm = reactive({
  name: '',
  prefix: '',
  is_active: true,
})

const officeColumns = [
  { name: 'name', label: 'Office Name', field: 'name', align: 'left' },
  { name: 'prefix', label: 'Prefix', field: 'prefix', align: 'left' },
  { name: 'status', label: 'Status', field: 'status', align: 'left' },
  { name: 'staff', label: 'Staff', field: 'staff', align: 'left' },
  { name: 'actions', label: 'Actions', field: 'actions', align: 'left' },
]

const selectedOffice = computed(() =>
  officeStore.adminOffices.find((office) => office.id === selectedOfficeId.value),
)

const availableStaffForSelectedOffice = computed(() => {
  if (!selectedOffice.value) {
    return officeStore.staffUsers
  }

  const assignedIds = selectedOffice.value.staff.map((staff) => staff.id)

  return officeStore.staffUsers.filter((staff) => !assignedIds.includes(staff.id))
})

async function loadData() {
  try {
    await Promise.all([officeStore.fetchAdminOffices(), officeStore.fetchStaffUsers()])
  } catch (error) {
    errorMessage.value = error.message
  }
}

function resetOfficeForm() {
  officeForm.name = ''
  officeForm.prefix = ''
  officeForm.is_active = true
  editingOfficeId.value = null
}

function openCreateOffice() {
  resetOfficeForm()
  officeDialogOpen.value = true
}

function openEditOffice(office) {
  editingOfficeId.value = office.id
  officeForm.name = office.name
  officeForm.prefix = office.prefix
  officeForm.is_active = office.is_active
  officeDialogOpen.value = true
}

async function submitOffice() {
  errorMessage.value = ''

  const payload = {
    name: officeForm.name,
    prefix: officeForm.prefix,
    is_active: officeForm.is_active,
  }

  try {
    if (editingOfficeId.value) {
      await officeStore.updateOffice(editingOfficeId.value, payload)
    } else {
      await officeStore.createOffice(payload)
    }

    officeDialogOpen.value = false
    resetOfficeForm()
  } catch (error) {
    errorMessage.value = error.message
  }
}

async function removeOffice(office) {
  $q.dialog({
    title: 'Delete Office',
    message: `Delete ${office.name}?`,
    cancel: true,
    persistent: true,
  }).onOk(async () => {
    try {
      await officeStore.deleteOffice(office.id)
    } catch (error) {
      errorMessage.value = error.message
    }
  })
}

function openStaffDialog(office) {
  selectedOfficeId.value = office.id
  selectedStaffId.value = null
  staffDialogOpen.value = true
}

async function assignStaff() {
  if (!selectedOfficeId.value || !selectedStaffId.value) {
    return
  }

  errorMessage.value = ''

  try {
    await officeStore.assignStaffToOffice(selectedOfficeId.value, selectedStaffId.value)
    selectedStaffId.value = null
  } catch (error) {
    errorMessage.value = error.message
  }
}

async function unassignStaff(staffId) {
  if (!selectedOfficeId.value) {
    return
  }

  errorMessage.value = ''

  try {
    await officeStore.unassignStaffFromOffice(selectedOfficeId.value, staffId)
  } catch (error) {
    errorMessage.value = error.message
  }
}

onMounted(async () => {
  await loadData()
})
</script>
