import { defineStore } from 'pinia'

function getStoredUser() {
  const raw = localStorage.getItem('user')
  if (!raw) return null

  try {
    return JSON.parse(raw)
  } catch {
    localStorage.removeItem('user')
    return null
  }
}

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: getStoredUser(),
    token: localStorage.getItem('token'),
  }),
  actions: {
    setUser(user) { this.user = user },
    setToken(token) { this.token = token },
    logout() {
      this.user = null
      this.token = null
      localStorage.removeItem('token')
      localStorage.removeItem('user')
    }
  }
})
