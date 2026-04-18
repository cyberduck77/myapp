import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { notificationApi, type NotificationItem } from '@/api/client'
import { getEcho, getUserNotificationChannelName } from '@/lib/echo'

export function useNotifications() {
  const isOpen = ref(false)
  const loading = ref(false)
  const notifications = ref<NotificationItem[]>([])
  const error = ref('')

  const unreadCount = computed(() => notifications.value.filter((item) => !item.read_at).length)
  let subscribedChannelName: string | null = null

  const fetchNotifications = async () => {
    loading.value = true
    error.value = ''

    try {
      const response = await notificationApi.list()
      notifications.value = response.notifications
    } catch (err) {
      error.value = err instanceof Error ? err.message : 'Failed to load notifications.'
    } finally {
      loading.value = false
    }
  }

  const toggleOpen = async () => {
    isOpen.value = !isOpen.value
    if (isOpen.value && notifications.value.length === 0) {
      await fetchNotifications()
    }
  }

  const markRead = async (id?: string) => {
    if (!id) {
      return
    }
    await notificationApi.markRead(id)
    const target = notifications.value.find((item) => item.id === id)
    if (target && !target.read_at) {
      target.read_at = new Date().toISOString()
    }
  }

  const markAllRead = async () => {
    await notificationApi.markAllRead()
    const now = new Date().toISOString()
    notifications.value = notifications.value.map((item) => ({
      ...item,
      read_at: item.read_at ?? now,
    }))
  }

  const subscribeRealtime = () => {
    const channelName = getUserNotificationChannelName()
    const echo = getEcho()

    if (!channelName || !echo) {
      console.debug('[notifications] skip subscribe', {
        channelName,
        hasEcho: Boolean(echo),
      })
      return
    }

    subscribedChannelName = channelName

    echo.private(channelName).notification((notification: any) => {
      const notificationItem: NotificationItem = {
        id: notification.id,
        type: notification.type,
        data: {
          message: notification.message,
        },
        read_at: null,
        created_at: new Date().toISOString(),
      }

      const exists = notifications.value.some((item) => item.id === notificationItem.id)
      if (exists) {
        return
      }

      notifications.value = [notificationItem, ...notifications.value]
    })
  }

  const unsubscribeRealtime = () => {
    const echo = getEcho()
    if (echo && subscribedChannelName) {
      echo.leave(`private-${subscribedChannelName}`)
    }
    subscribedChannelName = null
  }

  onMounted(() => {
    void fetchNotifications()
    subscribeRealtime()
  })

  onBeforeUnmount(() => {
    unsubscribeRealtime()
  })

  return {
    isOpen,
    loading,
    error,
    notifications,
    unreadCount,
    toggleOpen,
    fetchNotifications,
    markRead,
    markAllRead,
  }
}
