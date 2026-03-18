import axios from 'axios'
import type { AxiosInstance, AxiosRequestHeaders } from 'axios'
import { getAccessToken } from '@/utils/auth'

const API_BASE = import.meta.env.VITE_API_BASE_URL ?? 'http://localhost:8000/api'

export const api: AxiosInstance = axios.create({
  baseURL: API_BASE,
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