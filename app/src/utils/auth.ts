import type { UserPayload } from "@/types/auth"

export const getAccessToken = () => {
  return localStorage.getItem('at')
}

export const attemptAuth = (accessToken: string | null) => {
  if (accessToken) {
    localStorage.setItem('at', accessToken)
  } else {
    localStorage.removeItem('at')
  }
}

export const logout = () => {
  localStorage.removeItem('at')
}

export const getUserPayload = (): UserPayload | null => {
  const accessToken = localStorage.getItem('at')
  if (!accessToken) return null
  const parts = accessToken.split('.')
  if (parts.length !== 3) return null
  try {
    const base64Url = parts[1] ?? ''
    const base64 = base64Url.replace(/-/g, '+').replace(/_/g, '/')
    const padded = base64.padEnd(Math.ceil(base64.length / 4) * 4, '=')
    const json = atob(padded)
    return JSON.parse(json) as UserPayload
  } catch {
    return null
  }
}