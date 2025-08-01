<template>
  <OwnerLayout>
    <div class="categories-layout" :class="{ 'drawer-open': rightDrawer }">
      <v-container class="py-6 categories-container" fluid>
        <v-card elevation="3" class="mb-8" color="card" style="border-radius: 2rem;">
          <v-card-title class="d-flex align-center" style="border-radius: 2rem 2rem 0 0;">
            <span class="text-h5 font-weight-bold" style="color:#2a9d8f;">Categories</span>
            <v-spacer />
            <v-btn
              color="primary"
              variant="elevated"
              class="rounded-pill"
              size="large"
              style="color:#181818;font-weight:bold;"
              @click="openAddDrawer"
            >
              <v-icon start>mdi-plus</v-icon>Add Category
            </v-btn>
          </v-card-title>
          <v-card-text>
            <v-text-field
              v-model="search"
              label="Search categories..."
              class="mb-4"
              rounded
              variant="solo"
              style="max-width:400px;"
              color="primary"
            />
            <v-data-table
              :headers="headers"
              :items="filteredCategories"
              class="rounded-xl"
              style="border-radius:1.5rem;overflow:hidden;"
              density="comfortable"
              item-value="id"
              :items-per-page="10"
            >
              <template #item.actions="{ item }">
                <v-btn icon class="rounded-xl" size="small" color="primary" @click="openEditDrawer(item)">
                  <v-icon>mdi-pencil</v-icon>
                </v-btn>
                <v-btn icon class="rounded-xl" size="small" color="red" @click="deleteCategory(item)">
                  <v-icon>mdi-delete</v-icon>
                </v-btn>
              </template>
            </v-data-table>
          </v-card-text>
        </v-card>
      </v-container>

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
            {{ editing ? 'Edit Category' : 'Add Category' }}
          </v-toolbar-title>
          <v-spacer />
          <v-btn icon class="rounded-xl" @click="rightDrawer = false">
            <v-icon color="black">mdi-close</v-icon>
          </v-btn>
        </v-toolbar>
        <v-divider />
        <v-form @submit.prevent="saveCategory" class="pa-6">
          <v-text-field label="Name" v-model="form.name" required variant="outlined" color="primary" class="mb-4 rounded-xl" style="border-radius: 1.5rem;" />
          <v-select
            label="Branch"
            :items="branches"
            item-title="name"
            item-value="id"
            v-model="form.branch_id"
            required
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
            <v-icon start>mdi-check</v-icon>{{ editing ? 'Update' : 'Add' }}
          </v-btn>
        </v-form>
      </v-navigation-drawer>
    </div>
  </OwnerLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import axios from 'axios'
import OwnerLayout from '@/layouts/OwnerLayout.vue'

const categories = ref([])
const branches = ref([])
const rightDrawer = ref(false)
const editing = ref(false)
const form = ref({ id: null, name: '', branch_id: null })
const search = ref('')

const headers = [
  { title: 'Name', value: 'name' },
  { title: 'Branch', value: 'branch.name' },
  { title: 'Actions', value: 'actions', sortable: false }
]

const filteredCategories = computed(() => {
  if (!search.value) return categories.value
  const q = search.value.toLowerCase()
  return categories.value.filter(cat =>
    cat.name.toLowerCase().includes(q) ||
    (cat.branch?.name ?? '').toLowerCase().includes(q)
  )
})

async function loadCategories() {
  const token = localStorage.getItem('token')
  const res = await axios.get('http://localhost:8000/api/categories', {
    headers: { Authorization: `Bearer ${token}` }
  })
  categories.value = res.data.data || res.data
}

async function loadBranches() {
  const token = localStorage.getItem('token')
  const res = await axios.get('http://localhost:8000/api/branches', {
    headers: { Authorization: `Bearer ${token}` }
  })
  branches.value = res.data.data || res.data
}

function openAddDrawer() {
  editing.value = false
  form.value = { id: null, name: '', branch_id: null }
  rightDrawer.value = true
}

function openEditDrawer(cat) {
  editing.value = true
  form.value = { ...cat }
  rightDrawer.value = true
}

async function saveCategory() {
  const token = localStorage.getItem('token')
  if (editing.value) {
    await axios.put(`http://localhost:8000/api/categories/${form.value.id}`, form.value, {
      headers: { Authorization: `Bearer ${token}` }
    })
  } else {
    await axios.post('http://localhost:8000/api/categories', form.value, {
      headers: { Authorization: `Bearer ${token}` }
    })
  }
  rightDrawer.value = false
  loadCategories()
}

async function deleteCategory(cat) {
  if (!confirm(`Delete ${cat.name}?`)) return
  const token = localStorage.getItem('token')
  await axios.delete(`http://localhost:8000/api/categories/${cat.id}`, {
    headers: { Authorization: `Bearer ${token}` }
  })
  loadCategories()
}

onMounted(() => {
  loadCategories()
  loadBranches()
})
</script>

<style scoped>
.categories-layout {
  display: flex;
  min-height: calc(100vh - 64px - 48px);
  transition: all 0.35s cubic-bezier(.4,0,.2,1);
  align-items: stretch;
}
.categories-container {
  padding-left: 0 !important;
  padding-right: 0 !important;
  width: 100%;
}
.v-card, .v-data-table, .v-toolbar, .v-navigation-drawer, .v-form, .v-btn, .v-text-field, .v-select, .v-switch {
  border-radius: 2rem !important;
}
</style>
