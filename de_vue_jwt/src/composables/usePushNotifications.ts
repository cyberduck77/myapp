import { onMounted, ref } from 'vue'
import { pushApi, type PushSubscriptionPayload } from '@/api/client'

function urlBase64ToUint8Array(base64String: string): Uint8Array {
  const padding = '='.repeat((4 - (base64String.length % 4)) % 4)
  const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/')
  const rawData = window.atob(base64)
  const outputArray = new Uint8Array(rawData.length)

  for (let i = 0; i < rawData.length; i += 1) {
    outputArray[i] = rawData.charCodeAt(i)
  }

  return outputArray
}

function normalizeSubscription(subscription: PushSubscription): PushSubscriptionPayload {
  const json = subscription.toJSON()

  return {
    endpoint: json.endpoint ?? subscription.endpoint,
    keys: {
      p256dh: json.keys?.p256dh ?? '',
      auth: json.keys?.auth ?? '',
    },
    contentEncoding: subscription.options?.applicationServerKey ? 'aes128gcm' : undefined,
  }
}

export function usePushNotifications() {
  const initialized = ref(false)
  const enabled = ref(false)
  const loading = ref(false)
  const status = ref('')
  const permissionBlocked = ref(false)

  const init = async () => {
    initialized.value = false
    permissionBlocked.value = Notification.permission === 'denied'

    if (permissionBlocked.value) {
      status.value = 'Notifications are blocked in browser settings.'
      enabled.value = false
      initialized.value = true
      return
    }

    if (!('serviceWorker' in navigator)) {
      status.value = 'Service worker is not supported in this browser.'
      enabled.value = false
      initialized.value = true
      return
    }

    const registration = await navigator.serviceWorker.ready
    const subscription = await registration.pushManager.getSubscription()
    enabled.value = subscription !== null
    initialized.value = true
  }

  const enable = async () => {
    status.value = ''
    loading.value = true

    try {
      permissionBlocked.value = Notification.permission === 'denied'
      if (permissionBlocked.value) {
        status.value = 'Notifications are blocked in browser settings.'
        return
      }

      if (!('serviceWorker' in navigator)) {
        status.value = 'Service worker is not supported in this browser.'
        return
      }
      if (!('PushManager' in window)) {
        status.value = 'Push API is not supported in this browser.'
        return
      }

      const permission = await Notification.requestPermission()
      if (permission !== 'granted') {
        permissionBlocked.value = permission === 'denied'
        status.value = 'Notification permission was not granted.'
        return
      }

      const vapidPublicKey = import.meta.env.VITE_VAPID_PUBLIC_KEY
      if (!vapidPublicKey) {
        status.value = 'Missing VITE_VAPID_PUBLIC_KEY in frontend env.'
        return
      }

      const registration = await navigator.serviceWorker.ready
      let subscription = await registration.pushManager.getSubscription()

      if (!subscription) {
        const applicationServerKey = urlBase64ToUint8Array(vapidPublicKey)
        subscription = await registration.pushManager.subscribe({
          userVisibleOnly: true,
          applicationServerKey: applicationServerKey as BufferSource,
        })
      }

      await pushApi.subscribe(normalizeSubscription(subscription))
      enabled.value = true
      status.value = 'Push notifications enabled.'
    } catch (error) {
      status.value = error instanceof Error ? error.message : 'Failed to enable push notifications.'
    } finally {
      loading.value = false
    }
  }

  onMounted(() => {
    void init()
  })

  return {
    initialized,
    enabled,
    loading,
    status,
    permissionBlocked,
    enable,
    init,
  }
}
