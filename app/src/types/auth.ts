export type JwtPayload = {
  sub: number | string
  iat?: number
  exp?: number
  name?: string
  email?: string
  // Allow any additional claims without breaking typing
  [key: string]: unknown
}

export type UserPayload = {
  id: number | null
  name: string | null
  email: string | null
}