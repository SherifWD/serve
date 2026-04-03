import { defineStore } from 'pinia'

function readJson(key, fallback = null) {
  const raw = localStorage.getItem(key)
  if (!raw) return fallback

  try {
    return JSON.parse(raw)
  } catch {
    localStorage.removeItem(key)
    return fallback
  }
}

function persistUser(user) {
  if (user) {
    localStorage.setItem('user', JSON.stringify(user))
  } else {
    localStorage.removeItem('user')
  }
}

function persistToken(token) {
  if (token) {
    localStorage.setItem('token', token)
  } else {
    localStorage.removeItem('token')
  }
}

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: readJson('user'),
    token: localStorage.getItem('token'),
  }),
  getters: {
    isAuthenticated: (state) => Boolean(state.token),
    role: (state) => state.user?.role ?? null,
    roles: (state) => state.user?.roles ?? [],
    permissions: (state) => state.user?.permissions ?? [],
    isAdmin: (state) => state.user?.role === 'admin' || (state.user?.roles ?? []).includes('admin'),
    isOwner: (state) => state.user?.role === 'owner' || (state.user?.roles ?? []).includes('owner'),
    displayRole: (state) => {
      const role = state.user?.role ?? 'guest'
      return role
        .replaceAll('_', ' ')
        .replace(/\b\w/g, (letter) => letter.toUpperCase())
    },
    displayWorkspace: (state) => {
      if (state.user?.restaurant?.name) return state.user.restaurant.name
      if (state.user?.branch?.name) return state.user.branch.name
      return 'Platform Control'
    },
    can: (state) => (permission) => {
      if (!permission) return true
      const isAdmin = state.user?.role === 'admin' || (state.user?.roles ?? []).includes('admin')
      if (isAdmin) return true
      return (state.user?.permissions ?? []).includes(permission)
    },
  },
  actions: {
    setSession({ user, token }) {
      this.user = user
      this.token = token
      persistUser(user)
      persistToken(token)
    },
    setUser(user) {
      this.user = user
      persistUser(user)
    },
    setToken(token) {
      this.token = token
      persistToken(token)
    },
    logout() {
      this.user = null
      this.token = null
      persistUser(null)
      persistToken(null)
    },
  },
})
