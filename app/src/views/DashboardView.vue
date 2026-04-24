<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { getUserPayload, logout } from '@/utils/auth'
import { api, authApi } from '@/api/client'
import DashboardHeader from '@/components/DashboardHeader.vue'

const router = useRouter()

const userPayload = computed(() => getUserPayload())
const message = ref('');

onMounted(async () => {
  const dashboardResponse = await api.get('/dashboard')
  message.value = dashboardResponse.data.message
})

async function handleLogout() {
  try {
    await authApi.logout()
  } catch {
    // Ignore network/logout endpoint failures and clear client auth anyway.
  }
  logout()
  router.push('/login')
}
</script>

<template>
  <div class="dashboard">
    <DashboardHeader @logout="handleLogout" />
    <main v-if="userPayload" class="content">
      <p>Welcome, <strong>{{ userPayload.name }}</strong>.</p>
      <p v-if="userPayload.email" class="email">{{ userPayload.email }}</p>
      <p>{{ message }}</p>
    </main>
  </div>
</template>

<style scoped>
.dashboard {
  max-width: 640px;
  margin: 0 auto;
  padding: 1.5rem;
}
.content {
  font-size: 1rem;
}
.content .email {
  color: #6b7280;
  margin-top: 0.25rem;
}
</style>
