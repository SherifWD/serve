<template>
  <OwnerLayout>
    <v-container class="rs-page py-6" fluid>
      <v-row class="mb-4" align="stretch">
        <v-col cols="12" lg="8">
          <div class="rs-panel hero-panel">
            <div>
              <div class="page-kicker">Platform Control</div>
              <h1 class="page-title">Restaurants and cafes across the full SaaS network.</h1>
              <p class="page-copy">
                Create brands, classify them as restaurants or cafes, and track how many
                branches and users already exist under each venue.
              </p>
            </div>

            <v-btn
              v-if="auth.isAdmin"
              color="primary"
              size="large"
              class="rs-pill"
              @click="openCreate"
            >
              <v-icon start icon="mdi-plus" />
              Add restaurant
            </v-btn>
          </div>
        </v-col>

        <v-col cols="12" md="6" lg="2">
          <div class="rs-panel metric-card">
            <div class="metric-label">Venues</div>
            <div class="metric-value">{{ restaurants.length }}</div>
          </div>
        </v-col>

        <v-col cols="12" md="6" lg="2">
          <div class="rs-panel metric-card">
            <div class="metric-label">Branches</div>
            <div class="metric-value">{{ totalBranches }}</div>
          </div>
        </v-col>
      </v-row>

      <v-alert
        v-if="!auth.isAdmin"
        type="info"
        variant="tonal"
        class="mb-4"
      >
        Restaurants are controlled by the platform admin account.
      </v-alert>

      <div class="rs-panel table-panel">
        <div class="panel-header">
          <div>
            <div class="panel-title">Venue Directory</div>
            <div class="panel-subtitle">Every restaurant and cafe registered in the solution.</div>
          </div>
        </div>

        <v-data-table
          :headers="headers"
          :items="restaurants"
          item-value="id"
          class="rs-table"
          hide-default-footer
        >
          <template #item.kind="{ item }">
            <v-chip :color="item.kind === 'cafe' ? 'info' : 'primary'" variant="tonal" class="rs-pill">
              {{ item.kind }}
            </v-chip>
          </template>

          <template #item.actions="{ item }">
            <div class="table-actions">
              <v-btn
                v-if="auth.isAdmin"
                size="small"
                icon="mdi-pencil-outline"
                variant="text"
                @click="openEdit(item)"
              />
              <v-btn
                v-if="auth.isAdmin"
                size="small"
                icon="mdi-delete-outline"
                variant="text"
                color="error"
                @click="removeRestaurant(item.id)"
              />
            </div>
          </template>
        </v-data-table>
      </div>

      <v-dialog v-model="dialog" max-width="520">
        <v-card class="dialog-card">
          <v-card-title class="dialog-title">
            {{ editing ? 'Edit venue' : 'Create venue' }}
          </v-card-title>
          <v-card-text>
            <v-form @submit.prevent="saveRestaurant">
              <v-text-field
                v-model="form.name"
                label="Venue name"
                variant="outlined"
                class="mb-4"
              />
              <v-select
                v-model="form.kind"
                :items="['restaurant', 'cafe']"
                label="Venue type"
                variant="outlined"
                class="mb-4"
              />
            </v-form>
          </v-card-text>
          <v-card-actions class="justify-end px-6 pb-6">
            <v-btn variant="text" @click="dialog = false">Cancel</v-btn>
            <v-btn color="primary" class="rs-pill" @click="saveRestaurant">Save</v-btn>
          </v-card-actions>
        </v-card>
      </v-dialog>
    </v-container>
  </OwnerLayout>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import axios from 'axios'
import OwnerLayout from '@/layouts/OwnerLayout.vue'
import { API_BASE_URL } from '../lib/api'
import { useAuthStore } from '../store/auth'

const auth = useAuthStore()

const restaurants = ref([])
const dialog = ref(false)
const editing = ref(false)
const form = ref({
  id: null,
  name: '',
  kind: 'restaurant',
})

const headers = [
  { title: 'ID', key: 'id' },
  { title: 'Name', key: 'name' },
  { title: 'Type', key: 'kind' },
  { title: 'Branches', key: 'branches_count' },
  { title: 'Users', key: 'users_count' },
  { title: 'Actions', key: 'actions', sortable: false, width: 120 },
]

const totalBranches = computed(() =>
  restaurants.value.reduce((sum, restaurant) => sum + Number(restaurant.branches_count || 0), 0),
)

function authHeaders() {
  return { Authorization: `Bearer ${auth.token}` }
}

async function loadRestaurants() {
  const { data } = await axios.get(`${API_BASE_URL}/restaurants`, {
    headers: authHeaders(),
  })

  restaurants.value = data
}

function openCreate() {
  editing.value = false
  form.value = {
    id: null,
    name: '',
    kind: 'restaurant',
  }
  dialog.value = true
}

function openEdit(restaurant) {
  editing.value = true
  form.value = {
    id: restaurant.id,
    name: restaurant.name,
    kind: restaurant.kind,
  }
  dialog.value = true
}

async function saveRestaurant() {
  const payload = {
    name: form.value.name,
    kind: form.value.kind,
  }

  if (editing.value) {
    await axios.put(`${API_BASE_URL}/restaurants/${form.value.id}`, payload, {
      headers: authHeaders(),
    })
  } else {
    await axios.post(`${API_BASE_URL}/restaurants`, payload, {
      headers: authHeaders(),
    })
  }

  dialog.value = false
  await loadRestaurants()
}

async function removeRestaurant(id) {
  await axios.delete(`${API_BASE_URL}/restaurants/${id}`, {
    headers: authHeaders(),
  })
  await loadRestaurants()
}

onMounted(loadRestaurants)
</script>

<style scoped>
.hero-panel,
.metric-card,
.table-panel,
.dialog-card {
  padding: 24px;
}

.hero-panel {
  min-height: 220px;
  display: flex;
  justify-content: space-between;
  align-items: flex-end;
  gap: 24px;
}

.page-kicker,
.metric-label,
.panel-subtitle {
  color: var(--rs-text-muted);
  text-transform: uppercase;
  letter-spacing: 0.12em;
  font-size: 0.76rem;
}

.page-title {
  max-width: 640px;
  margin-top: 0.8rem;
  color: var(--rs-text);
  font-size: 2.4rem;
  line-height: 1.05;
  letter-spacing: -0.04em;
}

.page-copy {
  max-width: 620px;
  margin-top: 0.8rem;
  color: var(--rs-text-soft);
  line-height: 1.7;
}

.metric-card {
  min-height: 220px;
  display: flex;
  flex-direction: column;
  justify-content: center;
}

.metric-value {
  color: var(--rs-text);
  font-size: 3rem;
  line-height: 1;
  letter-spacing: -0.05em;
  margin-top: 1rem;
}

.table-panel {
  margin-top: 8px;
}

.panel-header {
  display: flex;
  justify-content: space-between;
  gap: 16px;
  margin-bottom: 18px;
}

.panel-title,
.dialog-title {
  color: var(--rs-text);
  font-size: 1.2rem;
  font-weight: 700;
}

.table-actions {
  display: flex;
  justify-content: flex-end;
}

.dialog-card {
  border: 1px solid var(--rs-border);
  border-radius: 24px;
  background: #101926;
}

@media (max-width: 960px) {
  .hero-panel {
    min-height: auto;
    flex-direction: column;
    align-items: flex-start;
  }

  .page-title {
    font-size: 2rem;
  }
}
</style>
