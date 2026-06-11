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
                <v-icon start>mdi-plus</v-icon>
                Add tables
              </v-btn>
            </v-card-title>
            <v-card-text>
              <v-text-field
                v-model="search"
                label="Search tables..."
                prepend-inner-icon="mdi-magnify"
                clearable
                class="mb-4"
              />
              <v-row dense class="mb-4">
                <v-col cols="12" md="3">
                  <v-select
                    v-model="filters.restaurant_id"
                    :items="restaurants"
                    item-title="name"
                    item-value="id"
                  label="Restaurant"
                  clearable
                  variant="outlined"
                  density="comfortable"
                  @update:model-value="filters.branch_id = null"
                />
              </v-col>
              <v-col cols="12" md="3">
                <v-select
                  v-model="filters.branch_id"
                  :items="filterBranches"
                    item-title="name"
                    item-value="id"
                    label="Branch"
                    clearable
                    variant="outlined"
                    density="comfortable"
                  />
                </v-col>
                <v-col cols="12" md="3">
                  <v-select
                    v-model="filters.status"
                    :items="statusFilterItems"
                    label="Status"
                    clearable
                    variant="outlined"
                    density="comfortable"
                  />
                </v-col>
                <v-col cols="12" md="3" class="d-flex align-center">
                  <v-btn variant="tonal" color="primary" class="rounded-pill" @click="resetFilters">
                    <v-icon start>mdi-filter-remove-outline</v-icon>Reset filters
                  </v-btn>
                </v-col>
              </v-row>
              <v-data-table
                :headers="headers"
                :items="filteredTables"
                :items-per-page="10"
                class="rounded-xl"
                style="border-radius:1.5rem;overflow:hidden;"
                density="comfortable"
                item-value="id"
              >
                <template #item.restaurant="{ item }">
                  {{ restaurantNameForTable(item) }}
                </template>

                <template #item.branch="{ item }">
                  {{ item.branch?.name || branchName(item.branch_id) }}
                </template>

                <template #item.seats="{ item }">
                  {{ displaySeats(item.seats) }}
                </template>

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

      <v-navigation-drawer
        v-model="drawer"
        location="right"
        temporary
        width="560"
        color="surface"
        transition="slide-x-reverse-transition"
        class="drawer-fade"
        style="border-radius:2rem 0 0 2rem;box-shadow:-6px 0 40px rgba(0,0,0,0.10);"
      >
        <v-toolbar flat color="surface" class="rounded-t-xl">
          <v-toolbar-title class="font-weight-bold" style="color:#2a9d8f;">
            {{ editing ? 'Edit table' : 'Add tables' }}
          </v-toolbar-title>
          <v-spacer />
          <v-btn icon class="rounded-xl" @click="drawer = false">
            <v-icon color="black">mdi-close</v-icon>
          </v-btn>
        </v-toolbar>
        <v-divider />
        <v-form @submit.prevent="saveTable" class="pa-6">
          <v-select
            label="Restaurant"
            :items="restaurants"
            item-title="name"
            item-value="id"
            v-model="form.restaurant_id"
            variant="outlined"
            color="primary"
            class="mb-4 rounded-xl"
            :disabled="!auth.isAdmin && restaurants.length <= 1"
            @update:modelValue="syncBranchForRestaurant"
          />
          <v-select
            label="Branch"
            :items="filteredBranches"
            item-title="name"
            item-value="id"
            v-model="form.branch_id"
            required
            variant="outlined"
            color="primary"
            class="mb-4 rounded-xl"
            no-data-text="Choose a restaurant with branches"
          />

          <template v-if="editing">
            <v-text-field
              label="Table name"
              v-model="form.name"
              required
              variant="outlined"
              color="primary"
              class="mb-4 rounded-xl"
            />
            <v-text-field
              label="Seats (optional)"
              v-model="form.seats"
              type="number"
              min="1"
              clearable
              variant="outlined"
              color="primary"
              class="mb-4 rounded-xl"
            />
          </template>

          <template v-else>
            <div
              v-for="(row, index) in form.tables"
              :key="row.key"
              class="table-row mb-3"
            >
              <v-row dense align="center">
                <v-col cols="12" sm="7">
                  <v-text-field
                    :label="`Table ${index + 1} name`"
                    v-model="row.name"
                    required
                    variant="outlined"
                    color="primary"
                    hide-details="auto"
                  />
                </v-col>
                <v-col cols="10" sm="4">
                  <v-text-field
                    label="Seats (optional)"
                    v-model="row.seats"
                    type="number"
                    min="1"
                    clearable
                    variant="outlined"
                    color="primary"
                    hide-details="auto"
                  />
                </v-col>
                <v-col cols="2" sm="1" class="d-flex justify-end">
                  <v-btn
                    icon
                    variant="text"
                    color="red"
                    :disabled="form.tables.length === 1"
                    @click="removeTableRow(index)"
                  >
                    <v-icon>mdi-delete-outline</v-icon>
                  </v-btn>
                </v-col>
              </v-row>
            </div>

            <v-btn variant="tonal" color="primary" class="mb-5 rounded-pill" @click="addTableRow">
              <v-icon start>mdi-plus</v-icon>
              Add another table
            </v-btn>
          </template>

          <v-btn
            color="primary"
            class="rounded-pill"
            block
            size="large"
            style="color:#181818;font-weight:bold;"
            type="submit"
          >
            <v-icon start>mdi-check</v-icon>
            {{ editing ? 'Save table' : saveLabel }}
          </v-btn>
        </v-form>
      </v-navigation-drawer>
    </v-container>
  </OwnerLayout>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import axios from 'axios'
import { API_BASE_URL } from '../lib/api'
import OwnerLayout from '@/layouts/OwnerLayout.vue'
import { missingField, showValidationAlert } from '../lib/validationAlert'
import { useAuthStore } from '../store/auth'

const auth = useAuthStore()

const tables = ref([])
const restaurants = ref([])
const branches = ref([])
const drawer = ref(false)
const editing = ref(false)
let rowKey = 0
const form = ref(createEmptyForm())
const search = ref('')
const filters = ref({
  restaurant_id: null,
  branch_id: null,
  status: null,
})

const headers = [
  { title: 'ID', key: 'id' },
  { title: 'Restaurant', key: 'restaurant' },
  { title: 'Branch', key: 'branch' },
  { title: 'Table name', key: 'name' },
  { title: 'Seats', key: 'seats' },
  { title: 'Status', key: 'status' },
  { title: 'Actions', key: 'actions', sortable: false },
]

const statusFilterItems = [
  { title: 'Open', value: 'open' },
  { title: 'Occupied', value: 'occupied' },
]

const filteredTables = computed(() => {
  const q = search.value.toLowerCase()

  return tables.value.filter(table =>
    matchesTableSearch(table, q) &&
    (!filters.value.restaurant_id || Number(tableRestaurantId(table)) === Number(filters.value.restaurant_id)) &&
    (!filters.value.branch_id || Number(table.branch_id) === Number(filters.value.branch_id)) &&
    (!filters.value.status || table.status === filters.value.status)
  )
})

const filteredBranches = computed(() => {
  if (!form.value.restaurant_id) return branches.value

  return branches.value.filter((branch) =>
    Number(branchRestaurantId(branch)) === Number(form.value.restaurant_id),
  )
})

const filterBranches = computed(() => {
  if (!filters.value.restaurant_id) return branches.value

  return branches.value.filter((branch) =>
    Number(branchRestaurantId(branch)) === Number(filters.value.restaurant_id),
  )
})

const rowsToSave = computed(() =>
  (form.value.tables || []).filter((row) => row.name.trim()),
)

const saveLabel = computed(() => {
  const count = rowsToSave.value.length
  return count === 1 ? 'Create table' : `Create ${count} tables`
})

const canSave = computed(() => {
  if (!form.value.branch_id) return false

  if (editing.value) {
    return Boolean(form.value.name.trim()) && validSeats(form.value.seats)
  }

  return rowsToSave.value.length > 0 &&
    form.value.tables.every((row) => !row.name.trim() || validSeats(row.seats))
})

function authHeaders() {
  return { Authorization: `Bearer ${auth.token || localStorage.getItem('token')}` }
}

function createTableRow() {
  rowKey += 1
  return { key: rowKey, name: '', seats: null }
}

function createEmptyForm() {
  return {
    id: null,
    restaurant_id: defaultRestaurantId(),
    branch_id: null,
    name: '',
    seats: null,
    tables: [createTableRow()],
  }
}

function defaultRestaurantId() {
  return auth.user?.restaurant_id ??
    auth.user?.branch?.restaurant_id ??
    restaurants.value[0]?.id ??
    branchRestaurantId(branches.value[0]) ??
    null
}

function branchRestaurantId(branch) {
  return branch?.restaurant_id ?? branch?.restaurant?.id ?? null
}

function branchName(branchId) {
  return branches.value.find((branch) => Number(branch.id) === Number(branchId))?.name || 'Not set'
}

function restaurantNameForTable(table) {
  const branch = table.branch ||
    branches.value.find((candidate) => Number(candidate.id) === Number(table.branch_id))
  const restaurantId = branchRestaurantId(branch)

  return branch?.restaurant?.name ||
    restaurants.value.find((restaurant) => Number(restaurant.id) === Number(restaurantId))?.name ||
    'Not set'
}

function tableRestaurantId(table) {
  const branch = table.branch ||
    branches.value.find((candidate) => Number(candidate.id) === Number(table.branch_id))

  return branchRestaurantId(branch)
}

function displaySeats(seats) {
  return seats ? seats : 'Not set'
}

function matchesTableSearch(table, query) {
  if (!query) return true

  return (table.name || '').toLowerCase().includes(query) ||
    (table.status || '').toLowerCase().includes(query) ||
    branchName(table.branch_id).toLowerCase().includes(query) ||
    restaurantNameForTable(table).toLowerCase().includes(query)
}

function resetFilters() {
  search.value = ''
  filters.value = {
    restaurant_id: null,
    branch_id: null,
    status: null,
  }
}

function validSeats(value) {
  if (value === null || value === undefined || value === '') return true
  return Number.isInteger(Number(value)) && Number(value) >= 1
}

function normalizeSeats(value) {
  if (value === null || value === undefined || value === '') return null
  return Number(value)
}

function syncBranchForRestaurant() {
  const branchesForRestaurant = filteredBranches.value
  const selectedBranchStillAvailable = branchesForRestaurant.some((branch) =>
    Number(branch.id) === Number(form.value.branch_id),
  )

  if (!selectedBranchStillAvailable) {
    form.value.branch_id = branchesForRestaurant[0]?.id ?? null
  }
}

function addTableRow() {
  form.value.tables.push(createTableRow())
}

function removeTableRow(index) {
  if (form.value.tables.length === 1) return
  form.value.tables.splice(index, 1)
}

async function loadTables() {
  const res = await axios.get(`${API_BASE_URL}/tables`, {
    headers: authHeaders(),
  })
  tables.value = res.data.data || res.data
}

async function loadRestaurants() {
  const res = await axios.get(`${API_BASE_URL}/restaurants`, {
    headers: authHeaders(),
  })
  restaurants.value = res.data.data || res.data
}

async function loadBranches() {
  const res = await axios.get(`${API_BASE_URL}/branches`, {
    headers: authHeaders(),
  })
  branches.value = res.data.data || res.data
}

function hydrateFallbackRestaurant() {
  if (restaurants.value.length) return

  const restaurantId = defaultRestaurantId()
  if (!restaurantId) return

  const branch = branches.value.find((item) => Number(branchRestaurantId(item)) === Number(restaurantId))
  restaurants.value = [{
    id: restaurantId,
    name: auth.user?.restaurant?.name || branch?.restaurant?.name || 'Assigned restaurant',
  }]
}

function openAdd() {
  editing.value = false
  form.value = createEmptyForm()
  syncBranchForRestaurant()
  drawer.value = true
}

function openEdit(table) {
  editing.value = true
  const branch = table.branch ||
    branches.value.find((candidate) => Number(candidate.id) === Number(table.branch_id))

  form.value = {
    id: table.id,
    restaurant_id: branchRestaurantId(branch) ?? defaultRestaurantId(),
    branch_id: table.branch_id,
    name: table.name,
    seats: table.seats ?? null,
    tables: [createTableRow()],
  }
  drawer.value = true
}

async function saveTable() {
  const validationFields = tableValidationFields()
  if (validationFields.length) {
    await showValidationAlert(validationFields, { title: 'Complete table details' })
    return
  }

  if (editing.value) {
    await axios.put(`${API_BASE_URL}/tables/${form.value.id}`, {
      restaurant_id: form.value.restaurant_id,
      branch_id: form.value.branch_id,
      name: form.value.name.trim(),
      seats: normalizeSeats(form.value.seats),
    }, {
      headers: authHeaders(),
    })
  } else {
    await axios.post(`${API_BASE_URL}/tables`, {
      restaurant_id: form.value.restaurant_id,
      branch_id: form.value.branch_id,
      tables: rowsToSave.value.map((row) => ({
        name: row.name.trim(),
        seats: normalizeSeats(row.seats),
      })),
    }, {
      headers: authHeaders(),
    })
  }

  drawer.value = false
  await loadTables()
}

function tableValidationFields() {
  if (editing.value) {
    return [
      missingField('Branch', Boolean(form.value.branch_id)),
      missingField('Table name', Boolean(form.value.name.trim())),
      missingField('Seats', validSeats(form.value.seats)),
    ].filter(Boolean)
  }

  return [
    missingField('Branch', Boolean(form.value.branch_id)),
    missingField('Table name', rowsToSave.value.length > 0),
    missingField('Seats', form.value.tables.every((row) => !row.name.trim() || validSeats(row.seats))),
  ].filter(Boolean)
}

async function deleteTable(id) {
  await axios.delete(`${API_BASE_URL}/tables/${id}`, {
    headers: authHeaders(),
  })
  await loadTables()
}

onMounted(async () => {
  await Promise.all([
    loadRestaurants(),
    loadBranches(),
    loadTables(),
  ])
  hydrateFallbackRestaurant()
})
</script>

<style scoped>
.v-card, .v-data-table, .v-toolbar, .v-navigation-drawer, .v-form, .v-btn, .v-text-field, .v-select, .v-switch {
  border-radius: 2rem !important;
}

.table-row {
  border: 1px solid rgba(42, 157, 143, 0.18);
  border-radius: 1.25rem;
  padding: 0.75rem;
  background: rgba(42, 157, 143, 0.04);
}
</style>
