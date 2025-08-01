<template>
  <OwnerLayout>
    <div class="branches-layout" :class="{ 'drawer-open': rightDrawer || viewDrawer }">
      <!-- Main Table Area -->
      <v-container class="py-6 branches-container" fluid>
        <v-card elevation="3" class="mb-8" color="card">
          <v-card-title class="d-flex align-center">
            <span class="text-h5 font-weight-bold" style="color:#2a9d8f;">Branches</span>
            <v-spacer />
            <v-btn
              color="primary"
              variant="elevated"
              class="rounded-pill"
              size="large"
              style="color:#181818;font-weight:bold;"
              v-if="auth.user?.role === 'owner'"
              @click="openAddDrawer"
            >
              <v-icon start>mdi-plus</v-icon>Add Branch
            </v-btn>
          </v-card-title>
          <v-card-text>
            <v-data-table
              :headers="headers"
              :items="branches"
              class="rounded-xl"
              density="comfortable"
              item-value="id"
              :items-per-page="10"
              :footer-props="{ itemsPerPageOptions: [10, 20, 50] }"
              style="border-radius:1.5rem;overflow:hidden;"
            >
              <template #item.actions="{ item }">
                <v-btn icon class="rounded-xl" size="small" color="primary" @click="openEditDrawer(item)" v-if="auth.user?.role === 'owner'">
                  <v-icon>mdi-pencil</v-icon>
                </v-btn>
                <v-btn icon class="rounded-xl" size="small" color="red" @click="deleteBranch(item.id)" v-if="auth.user?.role === 'owner'">
                  <v-icon>mdi-delete</v-icon>
                </v-btn>
                <v-btn icon class="rounded-xl" size="small" color="accent" @click="openViewDrawer(item)">
                  <v-icon>mdi-eye</v-icon>
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
            {{ editing ? 'Edit Branch' : 'Add Branch' }}
          </v-toolbar-title>
          <v-spacer />
          <v-btn icon class="rounded-xl" @click="rightDrawer = false">
            <v-icon color="black">mdi-close</v-icon>
          </v-btn>
        </v-toolbar>
        <v-divider />
        <v-form @submit.prevent="saveBranch" class="pa-6">
          <v-text-field v-model="branchForm.name" label="Name" required variant="outlined" color="primary" class="mb-4 rounded-xl" />
          <v-text-field v-model="branchForm.location" label="Location" variant="outlined" color="primary" class="mb-4 rounded-xl" />
          <v-btn color="primary" class="rounded-pill" block size="large" style="color:#181818;font-weight:bold;" type="submit">
            <v-icon start>mdi-check</v-icon>Save
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
            Branch Details
          </v-toolbar-title>
          <v-spacer />
          <v-btn icon class="rounded-xl" @click="viewDrawer = false">
            <v-icon color="black">mdi-close</v-icon>
          </v-btn>
        </v-toolbar>
        <v-divider />
        <div class="pa-6">
          <div class="mb-5">
            <div class="text-h6 font-weight-bold mb-2" style="color:#2a9d8f;">{{ viewBranch.name }}</div>
            <div class="my-2" style="color:#eef3ee;">
              <v-icon size="small" color="primary" class="mr-1">mdi-map-marker</v-icon>
              <span>Location: </span><b style="color:#eef3ee;">{{ viewBranch.location }}</b>
            </div>
            <v-divider class="my-4" />
            <div class="text-caption" style="color:#aaa;">
              ID: <span class="ml-1">{{ viewBranch.id }}</span>
            </div>
          </div>
        </div>
      </v-navigation-drawer>
    </div>
  </OwnerLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'
import { useAuthStore } from '../store/auth'
import OwnerLayout from '@/layouts/OwnerLayout.vue'

const auth = useAuthStore()
const branches = ref([])
const rightDrawer = ref(false)
const viewDrawer = ref(false)
const editing = ref(false)
const branchForm = ref({ id: null, name: '', location: '' })
const viewBranch = ref({})

const headers = [
  { title: 'ID', value: 'id' },
  { title: 'Name', value: 'name' },
  { title: 'Location', value: 'location' },
  { title: 'Actions', value: 'actions', sortable: false }
]

async function loadBranches() {
  const { data } = await axios.get('http://localhost:8000/api/branches', {
    headers: { Authorization: `Bearer ${auth.token}` }
  })
  branches.value = data.data || data
}

function openAddDrawer() {
  branchForm.value = { id: null, name: '', location: '' }
  editing.value = false
  rightDrawer.value = true
}
function openEditDrawer(branch) {
  branchForm.value = { ...branch }
  editing.value = true
  rightDrawer.value = true
}
function openViewDrawer(branch) {
  viewBranch.value = { ...branch }
  viewDrawer.value = true
}
async function saveBranch() {
  if (editing.value) {
    await axios.put(`http://localhost:8000/api/branches/${branchForm.value.id}`, branchForm.value, {
      headers: { Authorization: `Bearer ${auth.token}` }
    })
  } else {
    await axios.post('http://localhost:8000/api/branches', branchForm.value, {
      headers: { Authorization: `Bearer ${auth.token}` }
    })
  }
  rightDrawer.value = false
  loadBranches()
}
async function deleteBranch(id) {
  await axios.delete(`http://localhost:8000/api/branches/${id}`, {
    headers: { Authorization: `Bearer ${auth.token}` }
  })
  loadBranches()
}
onMounted(loadBranches)
</script>

<style scoped>
.branches-layout {
  display: flex;
  min-height: calc(100vh - 64px - 48px);
  transition: all 0.35s cubic-bezier(.4,0,.2,1);
  align-items: stretch;
}
.branches-main {
  flex: 1 1 auto;
  max-width: 100%;
  transition: max-width 0.35s cubic-bezier(.4,0,.2,1);
  display: flex;
  flex-direction: column;
  align-items: stretch;
}
.branches-layout.drawer-open .branches-main {
  max-width: calc(100% - 410px);
}
.branches-container {
  padding-left: 0 !important;
  padding-right: 0 !important;
  width: 100%;
}
</style>
