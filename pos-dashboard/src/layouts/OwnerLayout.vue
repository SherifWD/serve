<template>
  <v-app :class="['owner-shell', isDarkTheme ? 'theme-dark' : 'theme-light']">
    <v-navigation-drawer
      v-model="drawer"
      temporary
      location="left"
      width="292"
      color="transparent"
      scrim="rgba(3, 9, 20, 0.48)"
      class="shell-drawer"
    >
      <div class="drawer-panel">
        <div class="brand-block">
          <div class="brand-icon">RS</div>
          <div>
            <div class="brand-title">Janova Suite</div>
            <div class="brand-subtitle">
              {{ auth.isAdmin ? 'Admin Dashboard' : 'Owner Dashboard' }}
            </div>
          </div>
        </div>

        <div class="workspace-card">
          <div class="workspace-label">Current Access</div>
          <div class="workspace-name">{{ auth.displayWorkspace }}</div>
          <div class="workspace-meta">
            {{ auth.displayRole }}
            <span v-if="auth.user?.branch?.name"> · {{ auth.user.branch.name }}</span>
          </div>
        </div>

        <div class="nav-sections">
          <div v-for="section in visibleSections" :key="section.title" class="nav-section">
            <div class="section-title">{{ section.title }}</div>
            <v-list density="comfortable" bg-color="transparent" nav>
              <v-list-item
                v-for="item in section.items"
                :key="item.to"
                :to="item.to"
                rounded="xl"
                class="nav-item"
                @click="drawer = false"
              >
                <template #prepend>
                  <v-icon :icon="item.icon" />
                </template>
                <v-list-item-title>{{ item.title }}</v-list-item-title>
                <template #append>
                  <v-chip
                    v-if="item.badge && item.badge()"
                    size="x-small"
                    color="primary"
                    variant="tonal"
                    class="rs-pill"
                  >
                    {{ item.badge() }}
                  </v-chip>
                </template>
              </v-list-item>
            </v-list>
          </div>
        </div>
      </div>
    </v-navigation-drawer>

    <v-app-bar app flat color="transparent" height="88" class="shell-app-bar">
      <div class="app-bar-panel">
        <div class="d-flex align-center ga-3">
          <button
            class="shell-toggle"
            type="button"
            :aria-expanded="drawer.toString()"
            :aria-label="drawer ? 'Hide navigation' : 'Show navigation'"
            @click="drawer = !drawer"
          >
            <v-icon :icon="drawer ? 'mdi-close' : 'mdi-menu'" />
          </button>
          <div>
            <div class="page-kicker">{{ currentSection }}</div>
            <div class="page-title">{{ currentTitle }}</div>
          </div>
        </div>

        <div class="toolbar-actions">
          <button
            class="theme-button"
            type="button"
            :aria-label="isDarkTheme ? 'Switch to light theme' : 'Switch to dark theme'"
            @click="toggleTheme"
          >
            <v-icon :icon="isDarkTheme ? 'mdi-weather-night' : 'mdi-white-balance-sunny'" />
            <span>{{ isDarkTheme ? 'Dark' : 'Light' }}</span>
          </button>

          <v-chip variant="tonal" color="primary" class="rs-pill">
            {{ auth.displayRole }}
          </v-chip>

          <v-menu>
            <template #activator="{ props }">
              <button class="account-button" v-bind="props">
                <div class="account-copy">
                  <span class="account-name">{{ auth.user?.name || 'User' }}</span>
                  <span class="account-email">{{ auth.user?.email || 'No email' }}</span>
                </div>
                <div class="account-avatar">
                  {{ initials }}
                </div>
              </button>
            </template>

            <v-list class="menu-surface">
              <v-list-item title="Logout" prepend-icon="mdi-logout" @click="logout" />
            </v-list>
          </v-menu>
        </div>
      </div>
    </v-app-bar>

    <v-main class="shell-main">
      <slot />
    </v-main>
  </v-app>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useTheme } from 'vuetify'
import axios from 'axios'
import { API_BASE_URL } from '../lib/api'
import { useAuthStore } from '../store/auth'

const auth = useAuthStore()
const route = useRoute()
const router = useRouter()
const vuetifyTheme = useTheme()
const drawer = ref(false)
const dashboardTheme = ref(localStorage.getItem('janova-dashboard-theme') || 'dark')
const isDarkTheme = computed(() => dashboardTheme.value !== 'light')

const navSections = [
  {
    title: 'Overview',
    items: [
      { to: '/dashboard', title: 'Dashboard', icon: 'mdi-view-dashboard-outline', permission: 'dashboard.view' },
    ],
  },
  {
    title: 'Admin',
    items: [
      { to: '/restaurants', title: 'Restaurants', icon: 'mdi-storefront-outline', permission: 'platform.restaurants.manage' },
      { to: '/branches', title: 'Branches', icon: 'mdi-map-marker-radius-outline', permission: 'branches.view' },
      { to: '/users', title: 'Users', icon: 'mdi-account-group-outline', permission: 'users.view' },
      { to: '/roles', title: 'Roles & Permissions', icon: 'mdi-shield-account-outline', permission: 'roles.view' },
    ],
  },
  {
    title: 'Operations',
    items: [
      { to: '/orders', title: 'Orders', icon: 'mdi-receipt-text-outline', permission: 'orders.view' },
      { to: '/customers', title: 'Customers', icon: 'mdi-account-multiple-outline', permission: 'customers.view' },
      { to: '/tables', title: 'Tables', icon: 'mdi-table-furniture', permission: 'tables.view' },
      { to: '/employees', title: 'Employees', icon: 'mdi-badge-account-outline', permission: 'employees.view' },
    ],
  },
  {
    title: 'Catalog',
    items: [
      { to: '/menus', title: 'Menus', icon: 'mdi-food-outline', permission: 'menu.view' },
      { to: '/categories', title: 'Categories', icon: 'mdi-shape-outline', permission: 'categories.view' },
      { to: '/products', title: 'Products', icon: 'mdi-hamburger', permission: 'products.view' },
      { to: '/recipe', title: 'Recipes', icon: 'mdi-silverware-fork-knife', permission: 'recipes.view' },
      { to: '/ingredients', title: 'Ingredients', icon: 'mdi-leaf-circle-outline', permission: 'ingredients.view' },
    ],
  },
  {
    title: 'Inventory',
    items: [
      { to: '/inventory', title: 'Inventory', icon: 'mdi-warehouse', permission: 'inventory.view' },
      { to: '/suppliers', title: 'Suppliers', icon: 'mdi-truck-delivery-outline', permission: 'suppliers.view' },
    ],
  },
  {
    title: 'Settings',
    items: [
      { to: '/marketing-inquiries', title: 'Marketing Leads', icon: 'mdi-bullhorn-outline', permission: 'settings.view' },
      { to: '/settings', title: 'Branch Setup', icon: 'mdi-store-cog-outline', permission: 'settings.view' },
    ],
  },
]

const visibleSections = computed(() =>
  navSections
    .map((section) => ({
      ...section,
      items: section.items.filter((item) => auth.can(item.permission)),
    }))
    .filter((section) => section.items.length > 0),
)

const currentTitle = computed(() => route.meta?.title || 'Dashboard')

const currentSection = computed(() => {
  const section = navSections.find((candidate) =>
    candidate.items.some((item) => item.to === route.path),
  )

  return section?.title || 'Janova Suite'
})

const initials = computed(() => {
  const name = auth.user?.name?.trim()
  if (!name) return 'RS'

  return name
    .split(/\s+/)
    .slice(0, 2)
    .map((part) => part[0]?.toUpperCase())
    .join('')
})

watch(
  () => route.fullPath,
  () => {
    drawer.value = false
  },
)

watch(
  dashboardTheme,
  (value) => {
    const normalized = value === 'light' ? 'light' : 'dark'
    vuetifyTheme.global.name.value =
      normalized === 'dark' ? 'restaurantSuiteDark' : 'restaurantSuiteLight'
    document.documentElement.dataset.theme = normalized
    localStorage.setItem('janova-dashboard-theme', normalized)
  },
  { immediate: true },
)

function toggleTheme() {
  dashboardTheme.value = isDarkTheme.value ? 'light' : 'dark'
}

async function logout() {
  try {
    await axios.post(
      `${API_BASE_URL}/logout`,
      {},
      { headers: { Authorization: `Bearer ${auth.token}` } },
    )
  } catch {
    // Ignore logout API errors and clear local session anyway.
  }

  auth.logout()
  router.push('/login')
}
</script>

<style scoped>
.owner-shell {
  background: transparent;
}

.shell-drawer {
  padding: 18px 0 18px 18px;
}

.drawer-panel {
  height: calc(100vh - 36px);
  overflow: hidden;
  display: flex;
  flex-direction: column;
  gap: 18px;
  padding: 20px 18px;
  border: 1px solid var(--rs-border);
  border-radius: 30px;
  background: var(--rs-drawer-gradient);
  box-shadow: var(--rs-shadow);
}

.brand-block {
  display: flex;
  align-items: center;
  gap: 14px;
}

.brand-icon {
  width: 48px;
  height: 48px;
  display: grid;
  place-items: center;
  border-radius: 16px;
  background: linear-gradient(135deg, var(--rs-accent), #1c8dff);
  color: #04111a;
  font-weight: 800;
  letter-spacing: 0.04em;
}

.brand-title {
  color: var(--rs-text);
  font-size: 1.05rem;
  font-weight: 700;
}

.brand-subtitle {
  color: var(--rs-text-muted);
  font-size: 0.84rem;
}

.workspace-card {
  padding: 16px;
  border-radius: 22px;
  background: var(--rs-workspace-gradient);
  border: 1px solid rgba(62, 207, 142, 0.18);
}

.workspace-label,
.section-title,
.page-kicker,
.account-email {
  color: var(--rs-text-muted);
  font-size: 0.78rem;
  text-transform: uppercase;
  letter-spacing: 0.12em;
}

.workspace-name,
.page-title {
  color: var(--rs-text);
  font-weight: 700;
  letter-spacing: -0.02em;
}

.workspace-name {
  margin-top: 0.35rem;
  font-size: 1rem;
}

.workspace-meta {
  margin-top: 0.35rem;
  color: var(--rs-text-soft);
  font-size: 0.9rem;
}

.nav-sections {
  flex: 1;
  overflow: auto;
  padding-right: 6px;
}

.nav-section + .nav-section {
  margin-top: 12px;
}

.section-title {
  margin: 0 12px 8px;
}

.nav-item {
  min-height: 46px;
  margin-bottom: 6px;
  color: var(--rs-text-soft);
}

:deep(.nav-item .v-list-item__prepend .v-icon),
:deep(.nav-item .v-list-item-title) {
  color: var(--rs-text-soft);
}

:deep(.nav-item.v-list-item--active) {
  background: linear-gradient(90deg, rgba(62, 207, 142, 0.18), rgba(36, 91, 180, 0.14)) !important;
  border: 1px solid rgba(62, 207, 142, 0.18);
}

:deep(.nav-item.v-list-item--active .v-list-item-title),
:deep(.nav-item.v-list-item--active .v-icon) {
  color: var(--rs-text) !important;
}

.shell-app-bar {
  padding: 18px 24px 0 24px;
}

.app-bar-panel {
  width: 100%;
  height: 70px;
  padding: 0 20px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  border: 1px solid var(--rs-border);
  border-radius: 26px;
  background: var(--rs-appbar-bg);
  backdrop-filter: blur(18px);
}

.shell-toggle,
.theme-button {
  width: 50px;
  height: 50px;
  border: 1px solid var(--rs-border);
  background: var(--rs-control-bg);
  color: var(--rs-text);
  border-radius: 16px;
  display: grid;
  place-items: center;
  transition:
    border-color 0.2s ease,
    background 0.2s ease,
    transform 0.2s ease;
}

.shell-toggle:hover,
.theme-button:hover {
  border-color: var(--rs-border-strong);
  background: var(--rs-control-hover);
  transform: translateY(-1px);
}

.theme-button {
  width: auto;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 0 14px;
  font-weight: 700;
}

.page-title {
  font-size: 1.35rem;
}

.toolbar-actions {
  display: flex;
  align-items: center;
  gap: 12px;
}

.account-button {
  border: 1px solid var(--rs-border);
  background: var(--rs-control-bg);
  color: inherit;
  border-radius: 999px;
  padding: 8px 8px 8px 14px;
  display: flex;
  align-items: center;
  gap: 12px;
}

.account-copy {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  text-align: right;
}

.account-name {
  color: var(--rs-text);
  font-size: 0.95rem;
  font-weight: 600;
}

.account-avatar {
  width: 36px;
  height: 36px;
  display: grid;
  place-items: center;
  border-radius: 50%;
  background: var(--rs-avatar-gradient);
  color: var(--rs-text);
  font-weight: 700;
}

.menu-surface {
  border: 1px solid var(--rs-border);
  border-radius: 18px;
  background: var(--rs-menu-bg);
}

.shell-main {
  padding: 118px 24px 24px 24px;
}

@media (max-width: 960px) {
  .shell-drawer {
    padding-left: 0;
  }

  .shell-main {
    padding-inline: 16px;
  }

  .page-title {
    font-size: 1.1rem;
  }

  .account-copy {
    display: none;
  }
}
</style>
