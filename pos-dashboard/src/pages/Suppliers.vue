<template>
  <OwnerLayout>
    <div class="suppliers-layout" :class="{ 'drawer-open': rightDrawer || viewDrawer }">
      <div class="suppliers-main">
        <v-container class="py-6 suppliers-container" fluid>
          <v-card
            elevation="3"
            class="mb-8"
            color="card"
            style="border-radius: 2rem;"
          >
            <v-card-title class="d-flex align-center" style="border-radius: 2rem 2rem 0 0;">
              <span class="text-h5 font-weight-bold" style="color:#2a9d8f;">Suppliers</span>
              <v-spacer />
              <v-btn
                color="primary"
                variant="elevated"
                class="rounded-pill"
                size="large"
                style="color:#181818;font-weight:bold;"
                @click="openAddDrawer"
              >
                <v-icon start>mdi-plus</v-icon>Add Supplier
              </v-btn>
            </v-card-title>
            <v-card-text>
              <v-text-field
                v-model="search"
                label="Quick Search (all fields)"
                prepend-inner-icon="mdi-magnify"
                class="mb-4"
                rounded
                variant="solo"
                style="max-width:400px;"
                color="primary"
                clearable
              />
              <v-data-table
                :headers="headers"
                :items="filteredSuppliers"
                class="rounded-xl"
                style="border-radius:1.5rem;overflow:hidden;"
                density="comfortable"
                item-value="id"
                :items-per-page="10"
              >
                <template #item.contact_person="{ item }">
                  <v-chip color="primary" size="small" variant="tonal" class="mr-1">{{ item.contact_person || "—" }}</v-chip>
                </template>
                <template #item.phone="{ item }">
                  <v-chip color="success" size="small" variant="tonal">{{ item.phone || "—" }}</v-chip>
                </template>
                <template #item.email="{ item }">
                  <v-chip color="info" size="small" variant="tonal">{{ item.email || "—" }}</v-chip>
                </template>
                <template #item.actions="{ item }">
                  <v-btn icon class="rounded-xl" size="small" color="accent" @click="openViewDrawer(item)">
                    <v-icon>mdi-eye</v-icon>
                  </v-btn>
                  <v-btn icon class="rounded-xl" size="small" color="primary" @click="openEditDrawer(item)">
                    <v-icon>mdi-pencil</v-icon>
                  </v-btn>
                  <v-btn icon class="rounded-xl" size="small" color="red" @click="deleteSupplier(item)">
                    <v-icon>mdi-delete</v-icon>
                  </v-btn>
                </template>
              </v-data-table>
            </v-card-text>
          </v-card>
        </v-container>
      </div>

      <!-- Add/Edit Drawer with Tabs -->
      <v-navigation-drawer
        v-model="rightDrawer"
        location="right"
        temporary
        width="420"
        color="surface"
        transition="slide-x-reverse-transition"
        class="drawer-fade"
        style="border-radius:2rem 0 0 2rem;box-shadow:-6px 0 40px rgba(0,0,0,0.10);"
      >
        <v-toolbar flat color="surface" class="rounded-t-xl">
          <v-toolbar-title class="font-weight-bold" style="color:#2a9d8f;">
            {{ isEditing ? 'Edit Supplier' : 'Add Supplier' }}
          </v-toolbar-title>
          <v-spacer />
          <v-btn icon class="rounded-xl" @click="rightDrawer = false">
            <v-icon color="black">mdi-close</v-icon>
          </v-btn>
        </v-toolbar>
        <v-divider />
        <v-tabs v-model="tab" fixed-tabs color="primary" class="mb-2 mt-4">
          <v-tab value="general">General Info</v-tab>
          <v-tab value="orders" disabled>Orders</v-tab>
        </v-tabs>
        <v-window v-model="tab">
          <v-window-item value="general">
            <v-form @submit.prevent="saveSupplier" class="pa-6">
              <v-text-field
                label="Name"
                v-model="drawerForm.name"
                required
                variant="outlined"
                color="primary"
                class="mb-4 rounded-xl"
              />
              <v-text-field
                label="Contact Person"
                v-model="drawerForm.contact_person"
                variant="outlined"
                color="primary"
                class="mb-4 rounded-xl"
              />
              <v-text-field
                label="Phone"
                v-model="drawerForm.phone"
                variant="outlined"
                color="primary"
                class="mb-4 rounded-xl"
              />
              <v-text-field
                label="Email"
                v-model="drawerForm.email"
                variant="outlined"
                color="primary"
                class="mb-4 rounded-xl"
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
          </v-window-item>
          <v-window-item value="orders">
            <div class="pa-6 text-center text-caption" style="color:#bbb;">
              Orders history by supplier coming soon!
            </div>
          </v-window-item>
        </v-window>
      </v-navigation-drawer>

      <!-- View Drawer (Read Only) -->
      <v-navigation-drawer
        v-model="viewDrawer"
        location="right"
        temporary
        width="420"
        color="surface"
        transition="slide-x-reverse-transition"
        class="drawer-fade"
        style="border-radius:2rem 0 0 2rem;box-shadow:-6px 0 40px rgba(0,0,0,0.10);"
      >
        <v-toolbar flat color="surface" class="rounded-t-xl">
          <v-toolbar-title class="font-weight-bold" style="color:#2a9d8f;">
            Supplier Details
          </v-toolbar-title>
          <v-spacer />
          <v-btn icon class="rounded-xl" @click="viewDrawer = false">
            <v-icon color="black">mdi-close</v-icon>
          </v-btn>
        </v-toolbar>
        <v-divider />
        <div class="pa-6">
          <div class="mb-5">
            <div class="text-h6 font-weight-bold mb-2" style="color:#2a9d8f;">{{ viewSupplier.name }}</div>
            <div class="my-2">
              <v-icon size="small" color="primary" class="mr-1">mdi-account</v-icon>
              <v-chip color="primary" size="small" variant="tonal" class="mr-2">{{ viewSupplier.contact_person || "—" }}</v-chip>
            </div>
            <div class="my-2">
              <v-icon size="small" color="success" class="mr-1">mdi-phone</v-icon>
              <v-chip color="success" size="small" variant="tonal" class="mr-2">{{ viewSupplier.phone || "—" }}</v-chip>
            </div>
            <div class="my-2">
              <v-icon size="small" color="info" class="mr-1">mdi-email</v-icon>
              <v-chip color="info" size="small" variant="tonal" class="mr-2">{{ viewSupplier.email || "—" }}</v-chip>
            </div>
            <v-divider class="my-4" />
            <div class="text-caption" style="color:#bbb;">
              ID: <span class="ml-1">{{ viewSupplier.id }}</span>
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

const suppliers = ref([])
const rightDrawer = ref(false)
const viewDrawer = ref(false)
const drawerTitle = ref('Add Supplier')
const isEditing = ref(false)
const tab = ref('general')
const drawerForm = ref({
  id: null, name: '', contact_person: '', phone: '', email: ''
})
const viewSupplier = ref({})
const search = ref('')

const headers = [
  { title: 'Name', value: 'name' },
  { title: 'Contact Person', value: 'contact_person' },
  { title: 'Phone', value: 'phone' },
  { title: 'Email', value: 'email' },
  { title: 'Actions', value: 'actions', sortable: false }
]

const filteredSuppliers = computed(() => {
  if (!search.value) return suppliers.value
  const q = search.value.toLowerCase()
  return suppliers.value.filter(supplier =>
    (supplier.name || '').toLowerCase().includes(q) ||
    (supplier.contact_person || '').toLowerCase().includes(q) ||
    (supplier.phone || '').toLowerCase().includes(q) ||
    (supplier.email || '').toLowerCase().includes(q)
  )
})

async function loadSuppliers() {
  try {
    const token = localStorage.getItem('token')
    const res = await axios.get('http://localhost:8000/api/suppliers', {
      headers: { Authorization: `Bearer ${token}` }
    })
    suppliers.value = res.data.data || res.data
  } catch (err) {
    console.error('Failed to load suppliers', err)
  }
}

function openAddDrawer() {
  isEditing.value = false
  drawerTitle.value = 'Add Supplier'
  tab.value = 'general'
  drawerForm.value = { id: null, name: '', contact_person: '', phone: '', email: '' }
  rightDrawer.value = true
}

function openEditDrawer(supplier) {
  isEditing.value = true
  drawerTitle.value = 'Edit Supplier'
  tab.value = 'general'
  drawerForm.value = { ...supplier }
  rightDrawer.value = true
}

function openViewDrawer(supplier) {
  viewSupplier.value = { ...supplier }
  viewDrawer.value = true
}

async function saveSupplier() {
  const token = localStorage.getItem('token')
  const url = isEditing.value
    ? `http://localhost:8000/api/suppliers/${drawerForm.value.id}`
    : 'http://localhost:8000/api/suppliers'
  const method = isEditing.value ? 'put' : 'post'
  await axios[method](url, drawerForm.value, {
    headers: { Authorization: `Bearer ${token}` }
  })
  rightDrawer.value = false
  loadSuppliers()
}

async function deleteSupplier(supplier) {
  if (!confirm(`Delete ${supplier.name}?`)) return
  const token = localStorage.getItem('token')
  await axios.delete(`http://localhost:8000/api/suppliers/${supplier.id}`, {
    headers: { Authorization: `Bearer ${token}` }
  })
  loadSuppliers()
}

onMounted(() => {
  loadSuppliers()
})
</script>

<style scoped>
.suppliers-layout {
  display: flex;
  min-height: calc(100vh - 64px - 48px);
  transition: all 0.35s cubic-bezier(.4,0,.2,1);
  align-items: stretch;
}
.suppliers-main {
  flex: 1 1 auto;
  max-width: 100%;
  transition: max-width 0.35s cubic-bezier(.4,0,.2,1);
  display: flex;
  flex-direction: column;
  align-items: stretch;
}
.suppliers-layout.drawer-open .suppliers-main {
  max-width: calc(100% - 420px);
}
.suppliers-container {
  padding-left: 0 !important;
  padding-right: 0 !important;
  width: 100%;
}
.v-card, .v-data-table, .v-toolbar, .v-navigation-drawer, .v-form, .v-btn, .v-text-field, .v-select, .v-switch {
  border-radius: 2rem !important;
}
</style>
