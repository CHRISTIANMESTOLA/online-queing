<template>
  <q-page class="q-pa-md q-pa-lg-md">
    <div class="page-content">
      <div class="row items-center justify-between q-mb-md">
        <div>
          <div class="text-h6 text-weight-bold">Staff Management</div>
          <div class="text-body2 text-grey-7">Create and maintain staff accounts.</div>
        </div>

        <q-btn color="primary" icon="person_add" no-caps label="New Staff" @click="openCreate" />
      </div>

      <q-banner v-if="errorMessage" class="bg-red-1 text-negative rounded-borders q-mb-md">
        {{ errorMessage }}
      </q-banner>

      <q-card flat bordered class="card-panel q-pa-md">
        <q-table
          flat
          :rows="officeStore.staffUsers"
          :columns="staffColumns"
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

          <template #body-cell-offices="props">
            <q-td :props="props">
              <div class="row q-gutter-xs">
                <q-chip
                  v-for="office in props.row.offices"
                  :key="office.id"
                  dense
                  color="orange-1"
                  text-color="orange-10"
                >
                  {{ office.name }}
                </q-chip>
                <span v-if="props.row.offices.length === 0" class="text-caption text-grey-6">
                  Unassigned
                </span>
              </div>
            </q-td>
          </template>

          <template #body-cell-actions="props">
            <q-td :props="props">
              <q-btn
                size="sm"
                flat
                color="primary"
                icon="edit"
                @click="openEdit(props.row)"
              />
              <q-btn
                size="sm"
                flat
                color="negative"
                icon="delete"
                @click="removeStaff(props.row)"
              />
            </q-td>
          </template>
        </q-table>
      </q-card>

      <q-dialog v-model="dialogOpen" persistent>
        <q-card style="min-width: 420px">
          <q-card-section>
            <div class="text-h6">{{ editingStaffId ? 'Edit Staff' : 'Create Staff' }}</div>
          </q-card-section>

          <q-card-section class="q-gutter-md">
            <q-input v-model="form.name" outlined dense label="Name" />
            <q-input v-model="form.email" outlined dense label="Email" type="email" />
            <q-input
              v-model="form.password"
              outlined
              dense
              type="password"
              :label="editingStaffId ? 'Password (optional)' : 'Password'"
            />
            <q-input
              v-model="form.password_confirmation"
              outlined
              dense
              type="password"
              :label="editingStaffId ? 'Confirm Password (optional)' : 'Confirm Password'"
            />
            <q-toggle v-model="form.is_active" label="Active" color="positive" />
          </q-card-section>

          <q-card-actions align="right">
            <q-btn flat label="Cancel" @click="dialogOpen = false" />
            <q-btn
              color="primary"
              :label="editingStaffId ? 'Update' : 'Create'"
              :loading="officeStore.saving"
              @click="submitStaff"
            />
          </q-card-actions>
        </q-card>
      </q-dialog>
    </div>
  </q-page>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue'
import { useQuasar } from 'quasar'
import { useOfficeStore } from 'src/stores/office-store'

const $q = useQuasar()
const officeStore = useOfficeStore()

const errorMessage = ref('')
const dialogOpen = ref(false)
const editingStaffId = ref(null)

const form = reactive({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
  is_active: true,
})

const staffColumns = [
  { name: 'name', label: 'Name', field: 'name', align: 'left' },
  { name: 'email', label: 'Email', field: 'email', align: 'left' },
  { name: 'status', label: 'Status', field: 'status', align: 'left' },
  { name: 'offices', label: 'Assigned Offices', field: 'offices', align: 'left' },
  { name: 'actions', label: 'Actions', field: 'actions', align: 'left' },
]

function resetForm() {
  form.name = ''
  form.email = ''
  form.password = ''
  form.password_confirmation = ''
  form.is_active = true
  editingStaffId.value = null
}

function openCreate() {
  resetForm()
  dialogOpen.value = true
}

function openEdit(staff) {
  editingStaffId.value = staff.id
  form.name = staff.name
  form.email = staff.email
  form.password = ''
  form.password_confirmation = ''
  form.is_active = staff.is_active
  dialogOpen.value = true
}

async function submitStaff() {
  errorMessage.value = ''

  const payload = {
    name: form.name,
    email: form.email,
    is_active: form.is_active,
  }

  if (form.password) {
    payload.password = form.password
    payload.password_confirmation = form.password_confirmation
  }

  try {
    if (editingStaffId.value) {
      await officeStore.updateStaff(editingStaffId.value, payload)
    } else {
      await officeStore.createStaff({
        ...payload,
        password: form.password,
        password_confirmation: form.password_confirmation,
      })
    }

    dialogOpen.value = false
    resetForm()
  } catch (error) {
    errorMessage.value = error.message
  }
}

async function removeStaff(staff) {
  $q.dialog({
    title: 'Delete Staff',
    message: `Delete ${staff.name}?`,
    cancel: true,
    persistent: true,
  }).onOk(async () => {
    try {
      await officeStore.deleteStaff(staff.id)
    } catch (error) {
      errorMessage.value = error.message
    }
  })
}

onMounted(async () => {
  try {
    await officeStore.fetchStaffUsers()
  } catch (error) {
    errorMessage.value = error.message
  }
})
</script>
