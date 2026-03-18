<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { getUserPayload, logout } from '@/utils/auth'
import { api } from '@/api/client'

const router = useRouter()

const userPayload = computed(() => getUserPayload())
const message = ref('');

onMounted(async () => {
  const response = await api.get('/dashboard')
  message.value = response.data.message
})

function handleLogout() {
  logout()
  router.push('/login')
}
</script>

<template>
  <div class="dashboard">
    <header class="header">
      <h1>Dashboard</h1>
      <button type="button" class="btn-logout" @click="handleLogout">
        Log out
      </button>
    </header>
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
.header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2rem;
  padding-bottom: 1rem;
  border-bottom: 1px solid #e5e7eb;
}
h1 {
  margin: 0;
  font-size: 1.5rem;
}
.btn-logout {
  padding: 0.5rem 1rem;
  background: #6b7280;
  color: white;
  border: none;
  border-radius: 6px;
  font-size: 0.875rem;
  cursor: pointer;
}
.btn-logout:hover {
  background: #4b5563;
}
.content {
  font-size: 1rem;
}
.content .email {
  color: #6b7280;
  margin-top: 0.25rem;
}
</style>
