<template>
  <q-page class="row items-center justify-center q-pa-md">
    <q-card class="auth-card q-pa-lg">
      <q-card-section class="q-px-none q-pt-none">
        <div class="text-h5 text-weight-bold">Staff / Admin Login</div>
        <div class="text-body2 text-grey-7 q-mt-xs">
          Use your staff or admin account to access management and queue controls.
        </div>
      </q-card-section>

      <q-banner v-if="errorMessage" class="bg-red-1 text-negative rounded-borders q-mb-md">
        {{ errorMessage }}
      </q-banner>

      <q-form class="q-gutter-md" @submit.prevent="handleLogin">
        <q-input
          v-model="form.email"
          type="email"
          label="Email"
          outlined
          dense
          lazy-rules
          :rules="[(val) => !!val || 'Email is required']"
        />

        <q-input
          v-model="form.password"
          type="password"
          label="Password"
          outlined
          dense
          lazy-rules
          :rules="[(val) => !!val || 'Password is required']"
        />

        <q-btn
          label="Login"
          type="submit"
          color="primary"
          class="full-width"
          :loading="authStore.loading"
        />
      </q-form>

      <q-separator class="q-my-md" />

      <div class="text-caption text-grey-7">
        Default seed accounts:
        <div>`admin@qserve.local` / `password123`</div>
        <div>`registrar.staff@qserve.local` / `password123`</div>
      </div>
    </q-card>
  </q-page>
</template>

<script setup>
import { reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from 'src/stores/auth-store'

const authStore = useAuthStore()
const router = useRouter()
const route = useRoute()

const errorMessage = ref('')
const form = reactive({
  email: 'admin@qserve.local',
  password: 'password123',
})

function getDefaultRouteByRole(role) {
  if (role === 'admin') {
    return '/admin/offices'
  }

  if (role === 'staff') {
    return '/staff/queue'
  }

  return '/queue'
}

async function handleLogin() {
  errorMessage.value = ''

  try {
    const user = await authStore.login(form)
    const redirectPath = route.query.redirect || getDefaultRouteByRole(user?.role)
    await router.push(redirectPath)
  } catch (error) {
    errorMessage.value = error.message
  }
}
</script>

<style scoped lang="scss"></style>
