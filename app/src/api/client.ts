import axios from 'axios'
import type { AxiosInstance, AxiosRequestHeaders } from 'axios'
import { getAccessToken } from '@/utils/auth'

const API_BASE = import.meta.env.VITE_API_BASE_URL ?? 'http://localhost:8000/api'

export const api: AxiosInstance = axios.create({
  baseURL: API_BASE,
  withCredentials: true,
  headers: {
    Accept: 'application/json',
    'Content-Type': 'application/json',
  },
})

api.interceptors.request.use((config) => {
  const accessToken = getAccessToken()

  if (!config.headers) config.headers = {} as AxiosRequestHeaders

  if (accessToken) {
    config.headers['Authorization'] = `Bearer ${accessToken}`
  }

  return config
})

export type PushSubscriptionPayload = {
  endpoint: string
  keys: {
    p256dh: string
    auth: string
  }
  contentEncoding?: string
}

export const pushApi = {
  subscribe(subscription: PushSubscriptionPayload) {
    return api.post('/push-subscriptions', subscription).then((r) => r.data)
  },
  unsubscribe(endpoint: string) {
    return api.delete('/push-subscriptions', {
      data: { endpoint },
    }).then((r) => r.data)
  },
}

export type NotificationItem = {
  id: string
  type: string
  data: {
    message?: string
    [key: string]: unknown
  }
  read_at: string | null
  created_at: string
}

export const notificationApi = {
  list() {
    return api
      .get<{ notifications: NotificationItem[]; unread_count: number }>('/notifications')
      .then((r) => r.data)
  },
  markRead(id: string) {
    return api.patch(`/notifications/${id}/read`).then((r) => r.data)
  },
  markAllRead() {
    return api.patch('/notifications/read-all').then((r) => r.data)
  },
}