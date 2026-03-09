<template>
  <q-layout view="hHh lpR fFf" class="app-shell">
    <q-header bordered class="bg-white text-dark">
      <q-toolbar class="toolbar-height">
        <q-btn
          flat
          dense
          round
          icon="menu"
          class="lt-md"
          aria-label="Open menu"
          @click="leftDrawerOpen = !leftDrawerOpen"
        />

        <q-toolbar-title class="row items-center q-gutter-sm">
          <q-icon name="support_agent" color="primary" size="28px" />
          <span class="text-weight-bold">QSERVE</span>
        </q-toolbar-title>

        <div class="gt-sm row items-center q-gutter-sm">
          <q-btn
            v-for="link in navLinks"
            :key="link.to"
            flat
            no-caps
            :label="link.label"
            :color="route.path === link.to ? 'primary' : 'dark'"
            :to="link.to"
          />
        </div>

        <q-space />

        <q-btn
          v-if="!authStore.isAuthenticated"
          unelevated
          color="primary"
          no-caps
          label="Login"
          to="/login"
        />

        <q-btn
          v-else
          flat
          no-caps
          color="negative"
          label="Logout"
          @click="handleLogout"
        />
      </q-toolbar>
    </q-header>

    <q-drawer v-model="leftDrawerOpen" side="left" bordered class="lt-md">
      <q-list>
        <q-item-label header class="text-weight-bold">Navigation</q-item-label>

        <q-item
          v-for="link in navLinks"
          :key="link.to"
          clickable
          :to="link.to"
          exact
          v-ripple
        >
          <q-item-section avatar>
            <q-icon :name="link.icon" />
          </q-item-section>
          <q-item-section>{{ link.label }}</q-item-section>
        </q-item>
      </q-list>
    </q-drawer>

    <q-page-container class="page-bg">
      <router-view />
    </q-page-container>
  </q-layout>
</template>

<script setup>
import { computed, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from 'src/stores/auth-store'

const authStore = useAuthStore()
const route = useRoute()
const router = useRouter()
const leftDrawerOpen = ref(false)

const baseLinks = [
  { label: 'Get Queue', to: '/queue', icon: 'confirmation_number' },
  { label: 'Monitor', to: '/monitor', icon: 'monitor_heart' },
]

const navLinks = computed(() => {
  const links = [...baseLinks]

  if (authStore.isAuthenticated && (authStore.role === 'staff' || authStore.role === 'admin')) {
    links.push({ label: 'Staff Console', to: '/staff/queue', icon: 'support_agent' })
  }

  if (authStore.isAuthenticated && authStore.role === 'admin') {
    links.push({ label: 'Manage Offices', to: '/admin/offices', icon: 'apartment' })
    links.push({ label: 'Manage Staff', to: '/admin/staff', icon: 'groups' })
  }

  if (!authStore.isAuthenticated) {
    links.push({ label: 'Login', to: '/login', icon: 'login' })
  }

  return links
})

async function handleLogout() {
  await authStore.logout()
  await router.push('/login')
}
</script>
