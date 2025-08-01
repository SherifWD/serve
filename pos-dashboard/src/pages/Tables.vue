<template>
  <OwnerLayout>
    <v-container class="py-6">
      <v-row>
        <v-col>
          <v-card
            elevation="3"
            color="card"
            class="mb-8"
            style="border-radius: 2rem;"
          >
            <v-card-title class="d-flex align-center" style="border-radius: 2rem 2rem 0 0;">
              <span class="text-h5 font-weight-bold" style="color:#2a9d8f;">Tables</span>
              <v-spacer />
              <v-btn
                color="primary"
                variant="elevated"
                class="rounded-pill"
                size="large"
                style="color:#181818;font-weight:bold;"
                @click="openAdd"
              >
                <v-icon start>mdi-plus</v-icon> Add Table
              </v-btn>
            </v-card-title>
            <v-card-text>
              <v-data-table
                :headers="headers"
                :items="tables"
                :items-per-page="10"
                class="rounded-xl"
                style="border-radius:1.5rem;overflow:hidden;"
                density="comfortable"
                item-value="id"
              >
                <template #item.actions="{ item }">
                  <v-btn size="small" icon color="primary" class="rounded-xl" @click="openEdit(item)">
                    <v-icon>mdi-pencil</v-icon>
                  </v-btn>
                  <v-btn size="small" icon color="red" class="rounded-xl" @click="deleteTable(item.id)">
                    <v-icon>mdi-delete</v-icon>
                  </v-btn>
                </template>
              </v-data-table>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>

      <!-- Add/Edit Drawer -->
      <v-navigation-drawer
        v-model="drawer"
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
            {{ editing ? 'Edit Table' : 'Add Table' }}
          </v-toolbar-title>
          <v-spacer />
          <v-btn icon class="rounded-xl" @click="drawer = false">
            <v-icon color="black">mdi-close</v-icon>
          </v-btn>
        </v-toolbar>
        <v-divider />
        <v-form @submit.prevent="saveTable" class="pa-6">
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
          />
          <v-text-field
            label="Table Name"
            v-model="form.name"
            required
            variant="outlined"
            color="primary"
            class="mb-4 rounded-xl"
          />
          <v-text-field
            label="Seats"
            v-model="form.seats"
            type="number"
            min="1"
            required
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
            <v-icon start>mdi-check</v-icon>{{ editing ? 'Update' : 'Add' }}
          </v-btn>
        </v-form>
      </v-navigation-drawer>
    </v-container>
  </OwnerLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'
import OwnerLayout from '@/layouts/OwnerLayout.vue'

const tables = ref([])
const branches = ref([])
const drawer = ref(false)
const editing = ref(false)
const form = ref({ id: null, branch_id: null, name: '', seats: 1 })

const headers = [
  { title: 'ID', key: 'id' },
  { title: 'Branch', key: 'branch.name' },
  { title: 'Table Name', key: 'name' },
  { title: 'Seats', key: 'seats' },
  { title: 'Actions', key: 'actions', sortable: false }
]

async function loadTables() {
  const token = localStorage.getItem('token')
  const res = await axios.get('http://localhost:8000/api/tables', {
    headers: { Authorization: `Bearer ${token}` }
  })
  tables.value = res.data.data || res.data
}

async function loadBranches() {
  const token = localStorage.getItem('token')
  const res = await axios.get('http://localhost:8000/api/branches', {
    headers: { Authorization: `Bearer ${token}` }
  })
  branches.value = res.data.data || res.data
}

function openAdd() {
  editing.value = false
  form.value = { id: null, branch_id: null, name: '', seats: 1 }
  drawer.value = true
}
function openEdit(table) {
  editing.value = true
  form.value = { ...table }
  drawer.value = true
}
async function saveTable() {
  const token = localStorage.getItem('token')
  if (editing.value) {
    await axios.put(`http://localhost:8000/api/tables/${form.value.id}`, form.value, {
      headers: { Authorization: `Bearer ${token}` }
    })
  } else {
    await axios.post('http://localhost:8000/api/tables', form.value, {
      headers: { Authorization: `Bearer ${token}` }
    })
  }
  drawer.value = false
  loadTables()
}
async function deleteTable(id) {
  const token = localStorage.getItem('token')
  await axios.delete(`http://localhost:8000/api/tables/${id}`, {
    headers: { Authorization: `Bearer ${token}` }
  })
  loadTables()
}
onMounted(() => {
  loadTables()
  loadBranches()
})
</script>

<style scoped>
.v-card, .v-data-table, .v-toolbar, .v-navigation-drawer, .v-form, .v-btn, .v-text-field, .v-select, .v-switch {
  border-radius: 2rem !important;
}
</style>
