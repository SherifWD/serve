<template>
  <OwnerLayout>
    <div class="menus-layout" :class="{ 'drawer-open': rightDrawer || viewDrawer }">
      <div class="menus-main">
        <v-container class="py-6 menus-container" fluid>
          <v-card
            elevation="3"
            class="mb-8"
            color="card"
            style="border-radius: 2rem;"
          >
            <v-card-title class="d-flex align-center" style="border-radius: 2rem 2rem 0 0;">
              <span class="text-h5 font-weight-bold" style="color:#2a9d8f;">Menus</span>
              <v-spacer />
              <v-btn
                color="primary"
                variant="elevated"
                class="rounded-pill"
                size="large"
                style="color:#181818;font-weight:bold;"
                @click="openAddDrawer"
              >
                <v-icon start>mdi-plus</v-icon>Add Menu
              </v-btn>
            </v-card-title>
            <v-card-text>
              <v-text-field
                v-model="search"
                label="Search menus..."
                class="mb-4"
                rounded
                variant="solo"
                style="max-width:400px;"
                color="primary"
              />
              <v-data-table
                :headers="headers"
                :items="filteredMenus"
                class="rounded-xl"
                style="border-radius:1.5rem;overflow:hidden;"
                density="comfortable"
                item-value="id"
                :items-per-page="10"
              >
                <template #item.categories="{ item }">
                  <span v-if="item.categories && item.categories.length">
                    <v-chip
                      v-for="cat in item.categories"
                      :key="cat.id"
                      class="ma-1"
                      color="primary"
                      text-color="#181818"
                      size="small"
                      label
                      style="border-radius: 1rem;"
                    >
                      {{ cat.name }}
                    </v-chip>
                  </span>
                  <span v-else>—</span>
                </template>
                <template #item.actions="{ item }">
                  <v-btn icon class="rounded-xl" size="small" color="accent" @click="openViewDrawer(item)">
                    <v-icon>mdi-eye</v-icon>
                  </v-btn>
                  <v-btn icon class="rounded-xl" size="small" color="primary" @click="openEditDrawer(item)">
                    <v-icon>mdi-pencil</v-icon>
                  </v-btn>
                  <v-btn icon class="rounded-xl" size="small" color="red" @click="deleteMenu(item)">
                    <v-icon>mdi-delete</v-icon>
                  </v-btn>
                </template>
              </v-data-table>
            </v-card-text>
          </v-card>
        </v-container>
      </div>

      <!-- Add/Edit Drawer -->
      <v-navigation-drawer
        v-model="rightDrawer"
        location="right"
        temporary
        width="410"
        color="surface"
        transition="slide-x-reverse-transition"
        class="drawer-fade"
        style="border-radius:2rem 0 0 2rem;box-shadow:-6px 0 40px rgba(0,0,0,0.10);"
      >
        <v-toolbar flat color="surface" class="rounded-t-xl">
          <v-toolbar-title class="font-weight-bold" style="color:#2a9d8f;">
            {{ drawerTitle }}
          </v-toolbar-title>
          <v-spacer />
          <v-btn icon class="rounded-xl" @click="rightDrawer = false">
            <v-icon color="black">mdi-close</v-icon>
          </v-btn>
        </v-toolbar>
        <v-divider />
        <v-form @submit.prevent="saveMenu" class="pa-6">
          <v-text-field
            label="Menu Name"
            v-model="drawerForm.name"
            required
            variant="outlined"
            color="primary"
            class="mb-4 rounded-xl"
            style="border-radius: 1.5rem;"
          />
          <v-select
            label="Branch"
            :items="branches"
            item-title="name"
            item-value="id"
            v-model="drawerForm.branch_id"
            required
            variant="outlined"
            color="primary"
            class="mb-4 rounded-xl"
            style="border-radius: 1.5rem;"
          />
          <v-select
            label="Categories"
            :items="categories"
            item-title="name"
            item-value="id"
            v-model="drawerForm.categories"
            multiple
            chips
            variant="outlined"
            color="primary"
            class="mb-4 rounded-xl"
            style="border-radius: 1.5rem;"
          />
          <v-btn
            color="primary"
            class="rounded-pill"
            block
            size="large"
            style="color:#181818;font-weight:bold;"
            type="submit"
          >
            <v-icon start>mdi-check</v-icon>{{ isEditing ? 'Update' : 'Add' }}
          </v-btn>
        </v-form>
      </v-navigation-drawer>

      <!-- View Drawer (Read Only) -->
      <v-navigation-drawer
        v-model="viewDrawer"
        location="right"
        temporary
        width="410"
        color="surface"
        transition="slide-x-reverse-transition"
        class="drawer-fade"
        style="border-radius:2rem 0 0 2rem;box-shadow:-6px 0 40px rgba(0,0,0,0.10);"
      >
        <v-toolbar flat color="surface" class="rounded-t-xl">
          <v-toolbar-title class="font-weight-bold" style="color:#2a9d8f;">
            Menu Details
          </v-toolbar-title>
          <v-spacer />
          <v-btn icon class="rounded-xl" @click="viewDrawer = false">
            <v-icon color="black">mdi-close</v-icon>
          </v-btn>
        </v-toolbar>
        <v-divider />
        <div class="pa-6">
          <div class="mb-5">
            <div class="text-h6 font-weight-bold mb-2" style="color:#2a9d8f;">{{ viewMenu.name }}</div>
            <div class="my-2" style="color:#fff;">
              <v-icon size="small" color="primary" class="mr-1">mdi-store</v-icon>
              <span>{{ viewMenu.branch?.name || '—' }}</span>
            </div>
            <div class="my-2" style="color:#2a9d8f;">
              <v-icon size="small" color="primary" class="mr-1">mdi-shape</v-icon>
              <span>Categories: </span>
              <span v-if="viewMenu.categories && viewMenu.categories.length">
                <v-chip
                  v-for="cat in viewMenu.categories"
                  :key="cat.id"
                  class="ma-1"
                  color="primary"
                  text-color="#181818"
                  size="small"
                  label
                  style="border-radius: 1rem;"
                >
                  {{ cat.name }}
                </v-chip>
              </span>
              <span v-else>—</span>
            </div>
            <v-divider class="my-4" />
            <div class="text-caption" style="color:#bbb;">
              ID: <span class="ml-1">{{ viewMenu.id }}</span>
            </div>
          </div>
        </div>
      </v-navigation-drawer>
    </div>
  </OwnerLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import axios from 'axios'
import OwnerLayout from '@/layouts/OwnerLayout.vue'

const menus = ref([])
const branches = ref([])
const categories = ref([])
const rightDrawer = ref(false)
const viewDrawer = ref(false)
const drawerTitle = ref('Add Menu')
const isEditing = ref(false)
const drawerForm = ref({
  id: null, name: '', branch_id: null, categories: []
})
const viewMenu = ref({})
const search = ref('')

const headers = [
  { title: 'Name', value: 'name' },
  { title: 'Branch', value: 'branch.name' },
  { title: 'Categories', value: 'categories' },
  { title: 'Actions', value: 'actions', sortable: false }
]

const filteredMenus = computed(() => {
  if (!search.value) return menus.value
  const q = search.value.toLowerCase()
  return menus.value.filter(menu =>
    menu.name.toLowerCase().includes(q) ||
    (menu.branch?.name ?? '').toLowerCase().includes(q)
  )
})

async function loadMenus() {
  try {
    const token = localStorage.getItem('token')
    const res = await axios.get('http://localhost:8000/api/menus', {
      headers: { Authorization: `Bearer ${token}` }
    })
    menus.value = res.data.data || res.data
  } catch (err) { console.error('Failed to load menus', err) }
}

async function loadBranches() {
  try {
    const token = localStorage.getItem('token')
    const res = await axios.get('http://localhost:8000/api/branches', {
      headers: { Authorization: `Bearer ${token}` }
    })
    branches.value = res.data.data || res.data
  } catch (err) { console.error('Failed to load branches', err) }
}

async function loadCategories() {
  try {
    const token = localStorage.getItem('token')
    const res = await axios.get('http://localhost:8000/api/categories', {
      headers: { Authorization: `Bearer ${token}` }
    })
    categories.value = res.data.data || res.data
  } catch (err) { console.error('Failed to load categories', err) }
}

function openAddDrawer() {
  isEditing.value = false
  drawerTitle.value = 'Add Menu'
  drawerForm.value = { id: null, name: '', branch_id: null, categories: [] }
  rightDrawer.value = true
}

function openEditDrawer(menu) {
  isEditing.value = true
  drawerTitle.value = 'Edit Menu'
  drawerForm.value = {
    id: menu.id,
    name: menu.name,
    branch_id: menu.branch_id,
    categories: (menu.categories ?? []).map(c => c.id)
  }
  rightDrawer.value = true
}

function openViewDrawer(menu) {
  viewMenu.value = { ...menu }
  viewDrawer.value = true
}

async function saveMenu() {
  const token = localStorage.getItem('token')
  const url = isEditing.value
    ? `http://localhost:8000/api/menus/${drawerForm.value.id}`
    : 'http://localhost:8000/api/menus'
  const method = isEditing.value ? 'put' : 'post'
  await axios[method](url, drawerForm.value, {
    headers: { Authorization: `Bearer ${token}` }
  })
  rightDrawer.value = false
  loadMenus()
}

async function deleteMenu(menu) {
  if (!confirm(`Delete ${menu.name}?`)) return
  const token = localStorage.getItem('token')
  await axios.delete(`http://localhost:8000/api/menus/${menu.id}`, {
    headers: { Authorization: `Bearer ${token}` }
  })
  loadMenus()
}

onMounted(() => {
  loadMenus()
  loadBranches()
  loadCategories()
})
</script>

<style scoped>
.menus-layout {
  display: flex;
  min-height: calc(100vh - 64px - 48px);
  transition: all 0.35s cubic-bezier(.4,0,.2,1);
  align-items: stretch;
}
.menus-main {
  flex: 1 1 auto;
  max-width: 100%;
  transition: max-width 0.35s cubic-bezier(.4,0,.2,1);
  display: flex;
  flex-direction: column;
  align-items: stretch;
}
.menus-layout.drawer-open .menus-main {
  max-width: calc(100% - 410px);
}
.menus-container {
  padding-left: 0 !important;
  padding-right: 0 !important;
  width: 100%;
}
.v-card, .v-data-table, .v-toolbar, .v-navigation-drawer, .v-form, .v-btn, .v-text-field, .v-select, .v-switch {
  border-radius: 2rem !important;
}
</style>
