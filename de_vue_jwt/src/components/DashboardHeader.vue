<script setup lang="ts">
import { usePushNotifications } from '@/composables/usePushNotifications'
import { useNotifications } from '@/composables/useNotifications'

defineEmits<{
  (e: 'logout'): void
}>()

const { initialized, enabled, loading, status, permissionBlocked, enable } = usePushNotifications()
const {
  isOpen,
  loading: notificationsLoading,
  error: notificationsError,
  notifications: notificationItems,
  unreadCount,
  toggleOpen,
  markRead,
  markAllRead,
} = useNotifications()
</script>

<template>
  <header class="header">
    <h1>Dashboard</h1>
    <div class="actions">
      <div class="notification-wrap">
        <button type="button" class="btn-notification" @click="toggleOpen">
          Notifications
          <span v-if="unreadCount > 0" class="badge">{{ unreadCount }}</span>
        </button>

        <div v-if="isOpen" class="notification-dropdown">
          <div class="notification-header">
            <strong>Notifications</strong>
            <button
              v-if="unreadCount > 0"
              type="button"
              class="btn-text"
              @click="markAllRead"
            >
              Mark all read
            </button>
          </div>

          <p v-if="notificationsLoading" class="notification-state">Loading...</p>
          <p v-else-if="notificationsError" class="notification-state">{{ notificationsError }}</p>
          <p v-else-if="notificationItems.length === 0" class="notification-state">
            No notifications yet.
          </p>

          <ul v-else class="notification-list">
            <li
              v-for="item in notificationItems"
              :key="item.id"
              class="notification-item"
              :class="{ unread: !item.read_at }"
            >
              <div class="notification-content">
                <p class="notification-message">{{ item.data.message ?? 'New notification' }}</p>
                <small class="notification-time">
                  {{ new Date(item.created_at).toLocaleString() }}
                </small>
              </div>
              <button
                v-if="!item.read_at"
                type="button"
                class="btn-text"
                @click="markRead(item.id)"
              >
                Mark read
              </button>
            </li>
          </ul>
        </div>
      </div>

      <button
        v-if="initialized && !enabled && !permissionBlocked"
        type="button"
        class="btn-push"
        :disabled="loading"
        @click="enable"
      >
        {{ loading ? 'Enabling…' : 'Enable push notifications' }}
      </button>
      <button type="button" class="btn-logout" @click="$emit('logout')">
        Log out
      </button>
    </div>
  </header>
  <p v-if="status" class="push-status">{{ status }}</p>
</template>

<style scoped>
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
.actions {
  display: flex;
  gap: 0.5rem;
  align-items: center;
}
.notification-wrap {
  position: relative;
}
.btn-notification {
  padding: 0.5rem 0.75rem;
  background: #111827;
  color: white;
  border: none;
  border-radius: 6px;
  font-size: 0.875rem;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
}
.badge {
  background: #ef4444;
  border-radius: 999px;
  min-width: 1.2rem;
  height: 1.2rem;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 0.75rem;
  font-weight: 700;
}
.notification-dropdown {
  position: absolute;
  top: calc(100% + 0.5rem);
  right: 0;
  width: 320px;
  max-height: 360px;
  overflow: auto;
  background: white;
  border: 1px solid #e5e7eb;
  border-radius: 10px;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
  z-index: 20;
}
.notification-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.75rem;
  border-bottom: 1px solid #f3f4f6;
}
.notification-state {
  margin: 0;
  padding: 0.75rem;
  color: #6b7280;
}
.notification-list {
  list-style: none;
  margin: 0;
  padding: 0;
}
.notification-item {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 0.5rem;
  padding: 0.75rem;
  border-bottom: 1px solid #f3f4f6;
}
.notification-item.unread {
  background: #f9fafb;
}
.notification-content {
  min-width: 0;
}
.notification-message {
  margin: 0 0 0.25rem;
}
.notification-time {
  color: #6b7280;
}
.btn-text {
  background: transparent;
  border: none;
  color: #2563eb;
  cursor: pointer;
  font-size: 0.8rem;
  padding: 0;
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
.btn-push {
  padding: 0.5rem 1rem;
  background: #10b981;
  color: white;
  border: none;
  border-radius: 6px;
  font-size: 0.875rem;
  cursor: pointer;
}
.btn-push:hover:not(:disabled) {
  background: #059669;
}
.btn-push:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}
.push-status {
  margin: -1.25rem 0 1.5rem;
  color: #374151;
}
</style>
