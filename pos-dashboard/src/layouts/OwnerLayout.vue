<template>
  <v-app>
    <!-- Sidebar -->
    <v-navigation-drawer
      app
      v-model="drawer"
      width="220"
      color="#181818"
      class="sidebar"
    >
      <v-list nav dense>
        <v-list-item prepend-avatar="/logo.png" title="Factory Suite" class="mb-3 logo-title" />
        <v-list-item
          :to="navDashboard.to"
          :title="navDashboard.title"
          :prepend-icon="navDashboard.icon"
          class="nav-link"
        />

        <v-list-subheader class="nav-section mt-4">Factory Systems</v-list-subheader>
        <v-list-item
          v-for="item in navFactory"
          :key="item.title"
          :to="item.to"
          :title="item.title"
          :prepend-icon="item.icon"
          class="nav-link"
        />

        <v-divider class="my-2" />

        <!-- Branches & Location -->
        <v-list-subheader class="nav-section">Locations</v-list-subheader>
        <v-list-item
          v-for="item in navLocations"
          :key="item.title"
          :to="item.to"
          :title="item.title"
          :prepend-icon="item.icon"
          class="nav-link"
        />

        <v-divider class="my-2" />

        <!-- Menu & Products -->
        <v-list-subheader class="nav-section">Menu & Products</v-list-subheader>
        <v-list-item
          v-for="item in navMenuProducts"
          :key="item.title"
          :to="item.to"
          :title="item.title"
          :prepend-icon="item.icon"
          class="nav-link"
        />

        <v-divider class="my-2" />

        <!-- Stock & Ingredients -->
        <v-list-subheader class="nav-section">Inventory</v-list-subheader>
        <v-list-item
          v-for="item in navInventory"
          :key="item.title"
          :to="item.to"
          :title="item.title"
          :prepend-icon="item.icon"
          class="nav-link"
        />

        <v-divider class="my-2" />

        <!-- Operations -->
        <v-list-subheader class="nav-section">Operations</v-list-subheader>
        <v-list-item
          v-for="item in navOps"
          :key="item.title"
          :to="item.to"
          :title="item.title"
          :prepend-icon="item.icon"
          class="nav-link"
        />

        <v-divider class="my-2" />

        <!-- People & Settings -->
        <v-list-subheader class="nav-section">Management</v-list-subheader>
        <v-list-item
          v-for="item in navMgmt"
          :key="item.title"
          :to="item.to"
          :title="item.title"
          :prepend-icon="item.icon"
          class="nav-link"
        />
      </v-list>
    </v-navigation-drawer>

    <!-- App Bar -->
    <v-app-bar app color="#181818" flat height="64">
      <v-app-bar-nav-icon @click="drawer = !drawer" color="#b7dbcc" />
      <v-toolbar-title class="font-weight-bold" style="color:#b7dbcc; letter-spacing:0.01em;">
        <span style="color:#2a9d8f;">Factory</span><span style="color:#b7dbcc;"> Suite</span>
      </v-toolbar-title>
      <v-spacer />
      <v-btn icon><v-icon color="#2a9d8f">mdi-bell</v-icon></v-btn>
      <v-menu>
        <template #activator="{ props }">
          <v-btn icon v-bind="props">
            <v-icon color="#b7dbcc">mdi-account-circle</v-icon>
          </v-btn>
        </template>
        <v-list>
          <v-list-item title="Profile" />
          <v-list-item title="Logout" @click="logout" />
        </v-list>
      </v-menu>
    </v-app-bar>

    <!-- Content -->
    <v-main style="background: #181818; min-height: calc(100vh - 64px - 48px);">
      <slot />
    </v-main>

    <!-- Footer -->
    <v-footer
      app
      padless
      class="footer"
      color="#181818"
      height="48"
      style="width: 100vw; left: 0; position: fixed; bottom: 0; z-index: 200;"
    >
      <v-col cols="12" class="text-center" style="color:#b7dbcc;">
        <span>
          <span style="color:#2a9d8f;font-weight:600;">PYRAMAKERZ</span> Technologies &copy; {{ new Date().getFullYear() }}
        </span>
      </v-col>
    </v-footer>
  </v-app>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'
const drawer = ref(true)
const router = useRouter()

// Main nav groups for sidebar (easy to adjust/extend)
const navDashboard = { to: "/dashboard", title: "ERP Home", icon: "mdi-factory" }

const navFactory = [
  { to: "/dms", title: "DMS Library", icon: "mdi-folder-file-outline" },
  { to: "/scm", title: "SCM Hub", icon: "mdi-truck-fast" },
  { to: "/wms", title: "WMS Stores", icon: "mdi-warehouse" },
  { to: "/mes", title: "MES Control", icon: "mdi-robot-industrial" },
  { to: "/iot", title: "IoT Streams", icon: "mdi-access-point-network" },
  { to: "/cmms", title: "CMMS Maintenance", icon: "mdi-cog-outline" },
  { to: "/procurement", title: "Procurement Office", icon: "mdi-briefcase" },
  { to: "/qms", title: "QMS Quality", icon: "mdi-clipboard-check" },
  { to: "/hse", title: "HSE Compliance", icon: "mdi-shield-alert" },
  { to: "/plm", title: "PLM Workspace", icon: "mdi-drawing" },
  { to: "/hrms", title: "HRMS People", icon: "mdi-account-badge" },
  { to: "/visitor", title: "Visitor Access", icon: "mdi-account-badge-outline" },
  { to: "/commerce", title: "Commerce Portal", icon: "mdi-cart-outline" },
  { to: "/crm", title: "CRM Growth", icon: "mdi-account-group" },
  { to: "/finance", title: "Finance Suite", icon: "mdi-currency-usd" },
  { to: "/budgeting", title: "Budgeting Control", icon: "mdi-cash-multiple" },
  { to: "/budgeting/reports", title: "Budgeting Reports", icon: "mdi-file-chart" },
  { to: "/projects", title: "Projects & Changes", icon: "mdi-clipboard-flow" },
  { to: "/projects/reports", title: "Project Insights", icon: "mdi-chart-gantt" },
  { to: "/communication", title: "Workflow Centre", icon: "mdi-forum" },
  { to: "/communication/reports", title: "Workflow Reports", icon: "mdi-chart-box-outline" },
  { to: "/bi", title: "BI Insights", icon: "mdi-chart-areaspline" },
  { to: "/pos", title: "POS Dashboard", icon: "mdi-point-of-sale" },
]

const navLocations = [
  { to: "/branches",   title: "Branches", icon: "mdi-store" },
  { to: "/tables",     title: "Tables",   icon: "mdi-table-furniture" },
]

const navMenuProducts = [
  { to: "/menus",      title: "Menus",      icon: "mdi-food-variant" },
  { to: "/categories", title: "Categories", icon: "mdi-shape" },
  { to: "/products",   title: "Products",   icon: "mdi-food" },
  { to: "/recipe",     title: "Recipes",    icon: "mdi-silverware-fork-knife" },
  { to: "/ingredients",title: "Ingredients",icon: "mdi-leaf" }
]

const navInventory = [
  { to: "/inventory",  title: "Stock Inventory", icon: "mdi-warehouse" },
  { to: "/suppliers",  title: "Suppliers",       icon: "mdi-truck-delivery" }
]

const navOps = [
  { to: "/orders",     title: "Orders",    icon: "mdi-receipt" }
]

const navMgmt = [
  { to: "/employees",  title: "Employees", icon: "mdi-account-group" },
  { to: "/platform",   title: "Platform Control", icon: "mdi-office-building-cog" },
  { to: "/settings",   title: "Settings",  icon: "mdi-cog" }
]

async function logout() {
  try {
    const token = localStorage.getItem('token')
    await axios.post('http://localhost:8000/api/logout', {}, {
      headers: { Authorization: `Bearer ${token}` }
    })
  } catch (e) {}
  localStorage.removeItem('token')
  localStorage.removeItem('user')
  router.push('/login')
}
</script>

<style scoped>
.sidebar {
  background: #181818 !important;
  color: #b7dbcc !important;
  border-right: 1px solid #232a2e;
}
.logo-title .v-list-item__title {
  color: #2a9d8f !important;
  font-weight: 700;
  letter-spacing: 0.05em;
}
.nav-section {
  color: #888 !important;
  font-size: 0.99em;
  text-transform: uppercase;
  margin: 10px 0 0 8px;
  letter-spacing: 0.09em;
  font-weight: 600;
}
.nav-link .v-list-item__title {
  color: #b7dbcc !important;
  font-weight: 500;
}
.v-list-item--active, .nav-link.router-link-exact-active {
  background: linear-gradient(90deg,#232a2e 0%,#2a9d8f11 100%) !important;
  color: #2a9d8f !important;
}
.v-list-item__prepend-icon .v-icon {
  color: #2a9d8f !important;
}
.v-btn {
  color: #2a9d8f !important;
}
.footer {
  background: #181818 !important;
  color: #b7dbcc !important;
  font-weight: bold;
  letter-spacing: 0.03em;
  padding: 0.5rem 0;
  border-top: 1px solid #232a2e;
  min-height: 48px;
}
</style>
