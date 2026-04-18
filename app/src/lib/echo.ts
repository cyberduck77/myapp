import Echo from 'laravel-echo'
import Pusher from 'pusher-js'
import { getAccessToken, getUserPayload } from '@/utils/auth'

const API_BASE = import.meta.env.VITE_API_BASE_URL ?? 'http://localhost:8000/api'

function getBroadcastAuthEndpoint(): string {
  return API_BASE.replace(/\/api\/?$/, '/broadcasting/auth')
}

function getEchoConfig() {
  const key = import.meta.env.VITE_PUSHER_APP_KEY as string | undefined
  const cluster = import.meta.env.VITE_PUSHER_APP_CLUSTER as string | undefined

  if (!key || !cluster) {
    return null
  }

  return {
    broadcaster: 'pusher' as const,
    key,
    cluster,
    forceTLS: true,
    authEndpoint: getBroadcastAuthEndpoint(),
    auth: {
      headers: {
        Authorization: `Bearer ${getAccessToken() ?? ''}`,
      },
    },
  }
}

let echoInstance: Echo<'pusher'> | null = null

export function getEcho(): Echo<'pusher'> | null {
  if (echoInstance) {
    return echoInstance
  }

  const config = getEchoConfig()
  if (!config) {
    return null
  }

  window.Pusher = Pusher
  echoInstance = new Echo(config)
  return echoInstance
}

export function getUserNotificationChannelName(): string | null {
  const payload = getUserPayload()
  if (!payload?.sub) {
    return null
  }

  return `App.Models.User.${payload.sub}`
}
