import axios from 'axios'
import type { AxiosError, AxiosInstance, AxiosRequestConfig, AxiosRequestHeaders } from 'axios'
import { attemptAuth, getAccessToken, logout } from '@/utils/auth'

const API_BASE = import.meta.env.VITE_API_BASE_URL ?? 'http://localhost:8000/api'

export const api: AxiosInstance = axios.create({
  baseURL: API_BASE,
  withCredentials: true,
  headers: {
    Accept: 'application/json',
    'Content-Type': 'application/json',
  },
})

const refreshClient: AxiosInstance = axios.create({
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

let refreshRequest: Promise<string | null> | null = null

const refreshAccessToken = async (): Promise<string | null> => {
  const response = await refreshClient.post<{ at: string }>('/refresh')
  const nextAccessToken = response.data?.at ?? null
  attemptAuth(nextAccessToken)
  return nextAccessToken
}

api.interceptors.response.use(
  (response) => response,
  async (error: AxiosError) => {
    const status = error.response?.status
    const originalRequest = error.config as (AxiosRequestConfig & { _retry?: boolean }) | undefined
    const requestUrl = originalRequest?.url ?? ''
    const shouldSkip =
      requestUrl.includes('/login') || requestUrl.includes('/refresh') || requestUrl.includes('/logout')

    if (status !== 401 || !originalRequest || originalRequest._retry || shouldSkip) {
      return Promise.reject(error)
    }

    originalRequest._retry = true

    try {
      if (!refreshRequest) {
        refreshRequest = refreshAccessToken().finally(() => {
          refreshRequest = null
        })
      }

      const nextAccessToken = await refreshRequest
      if (!nextAccessToken) {
        throw error
      }

      if (!originalRequest.headers) {
        originalRequest.headers = {} as AxiosRequestHeaders
      }
      originalRequest.headers.Authorization = `Bearer ${nextAccessToken}`

      return api(originalRequest)
    } catch (refreshError) {
      logout()
      return Promise.reject(refreshError)
    }
  }
)

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

export const authApi = {
  logout() {
    return api.post('/logout').then((r) => r.data)
  },
}