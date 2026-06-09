<template>
  <OwnerLayout>
    <v-container class="py-6 leads-page" fluid>
      <v-card class="leads-panel" color="card">
        <v-card-title class="leads-title-row">
          <div>
            <div class="text-h5 font-weight-bold leads-title">Marketing Leads</div>
            <div class="text-body-2 leads-subtitle">
              Restaurant and cafe requirements submitted from the public contact survey.
            </div>
          </div>
          <v-spacer />
          <v-chip color="primary" variant="tonal" class="rs-pill">
            {{ meta.total }} total
          </v-chip>
          <v-chip color="warning" variant="tonal" class="rs-pill">
            {{ meta.new_count }} new
          </v-chip>
        </v-card-title>

        <v-card-text>
          <v-row dense class="mb-5">
            <v-col cols="12" md="7">
              <v-text-field
                v-model="filters.search"
                label="Search name, business, phone, email, city, or pain points"
                prepend-inner-icon="mdi-magnify"
                variant="outlined"
                density="comfortable"
                clearable
                @keyup.enter="loadInquiries"
                @click:clear="loadInquiries"
              />
            </v-col>
            <v-col cols="12" md="3">
              <v-select
                v-model="filters.status"
                :items="statusOptions"
                item-title="title"
                item-value="value"
                label="Status"
                clearable
                variant="outlined"
                density="comfortable"
                :menu-props="{ contentClass: 'dashboard-select-menu' }"
                @update:model-value="loadInquiries"
              />
            </v-col>
            <v-col cols="12" md="2" class="d-flex align-start">
              <v-btn color="primary" variant="elevated" class="leads-button" block @click="loadInquiries">
                <v-icon start>mdi-refresh</v-icon>
                Refresh
              </v-btn>
            </v-col>
          </v-row>

          <v-alert
            v-if="error"
            type="error"
            variant="tonal"
            class="mb-4"
          >
            {{ error }}
          </v-alert>

          <v-data-table
            :headers="headers"
            :items="inquiries"
            :loading="loading"
            item-value="id"
            density="comfortable"
            class="leads-table"
            :items-per-page="10"
          >
            <template #item.business="{ item }">
              <div class="lead-main">
                <strong>{{ item.business_name }}</strong>
                <span>{{ formatType(item.business_type) }} · {{ item.city || 'City not set' }}</span>
              </div>
            </template>

            <template #item.contact="{ item }">
              <div class="lead-main">
                <strong>{{ item.full_name }}</strong>
                <span>{{ item.phone }} · {{ item.email }}</span>
              </div>
            </template>

            <template #item.timeline="{ item }">
              <v-chip color="info" size="small" variant="tonal">{{ formatType(item.timeline) }}</v-chip>
            </template>

            <template #item.status="{ item }">
              <v-chip :color="statusColor(item.status)" size="small" variant="tonal">
                {{ formatType(item.status) }}
              </v-chip>
            </template>

            <template #item.created_at="{ item }">
              {{ formatDate(item.created_at) }}
            </template>

            <template #item.actions="{ item }">
              <v-btn icon size="small" color="primary" variant="tonal" @click="openDrawer(item)">
                <v-icon>mdi-eye-outline</v-icon>
              </v-btn>
            </template>
          </v-data-table>
        </v-card-text>
      </v-card>

      <v-navigation-drawer
        v-model="drawer"
        location="right"
        temporary
        width="460"
        color="surface"
        class="lead-drawer"
      >
        <v-toolbar flat color="surface">
          <v-toolbar-title class="font-weight-bold leads-title">Lead Details</v-toolbar-title>
          <v-spacer />
          <v-btn icon @click="drawer = false">
            <v-icon color="black">mdi-close</v-icon>
          </v-btn>
        </v-toolbar>
        <v-divider />

        <div v-if="selected" class="drawer-body">
          <section class="drawer-section">
            <div class="drawer-kicker">{{ formatType(selected.status) }}</div>
            <h2>{{ selected.business_name }}</h2>
            <p>{{ selected.full_name }} · {{ selected.role || 'Role not set' }}</p>
          </section>

          <section class="drawer-section">
            <h3>Contact</h3>
            <div class="detail-grid">
              <span>Email</span><strong>{{ selected.email }}</strong>
              <span>Phone</span><strong>{{ selected.phone }}</strong>
              <span>Method</span><strong>{{ formatType(selected.preferred_contact_method) }}</strong>
              <span>Best time</span><strong>{{ selected.best_contact_time || 'Not set' }}</strong>
              <span>Website</span><strong>{{ selected.website || 'Not set' }}</strong>
            </div>
          </section>

          <section class="drawer-section">
            <h3>Operation</h3>
            <div class="detail-grid">
              <span>Type</span><strong>{{ formatType(selected.business_type) }}</strong>
              <span>Branches</span><strong>{{ selected.branch_count || 'Not set' }}</strong>
              <span>Staff</span><strong>{{ selected.staff_count || 'Not set' }}</strong>
              <span>Current system</span><strong>{{ selected.current_system || 'Not set' }}</strong>
              <span>Timeline</span><strong>{{ formatType(selected.timeline) }}</strong>
              <span>Budget</span><strong>{{ selected.budget_range || 'Not set' }}</strong>
            </div>
          </section>

          <section class="drawer-section">
            <h3>Needs</h3>
            <div class="chip-list">
              <v-chip
                v-for="need in selected.interest_areas"
                :key="need"
                size="small"
                color="primary"
                variant="tonal"
              >
                {{ formatType(need) }}
              </v-chip>
            </div>
          </section>

          <section class="drawer-section">
            <h3>Channels</h3>
            <div class="chip-list">
              <v-chip
                v-for="channel in selected.order_channels"
                :key="channel"
                size="small"
                color="info"
                variant="tonal"
              >
                {{ formatType(channel) }}
              </v-chip>
            </div>
          </section>

          <section class="drawer-section">
            <h3>Devices</h3>
            <div class="chip-list">
              <v-chip
                v-for="device in selected.devices"
                :key="device"
                size="small"
                color="success"
                variant="tonal"
              >
                {{ formatType(device) }}
              </v-chip>
            </div>
          </section>

          <section class="drawer-section">
            <h3>Pain Points</h3>
            <p>{{ selected.pain_points }}</p>
          </section>

          <section v-if="selected.success_notes" class="drawer-section">
            <h3>Success Notes</h3>
            <p>{{ selected.success_notes }}</p>
          </section>

          <section class="drawer-section">
            <h3>Admin Follow-up</h3>
            <v-select
              v-model="editForm.status"
              :items="statusOptions"
              item-title="title"
              item-value="value"
              label="Status"
              variant="outlined"
              density="comfortable"
              :menu-props="{ contentClass: 'dashboard-select-menu' }"
            />
            <v-textarea
              v-model="editForm.admin_notes"
              label="Admin notes"
              variant="outlined"
              rows="4"
              auto-grow
            />
            <v-btn color="primary" class="leads-button" block :loading="saving" @click="saveLead">
              <v-icon start>mdi-content-save-outline</v-icon>
              Save Lead Status
            </v-btn>
          </section>
        </div>
      </v-navigation-drawer>
    </v-container>
  </OwnerLayout>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import axios from 'axios'
import OwnerLayout from '@/layouts/OwnerLayout.vue'
import { API_BASE_URL } from '../lib/api'

const inquiries = ref([])
const selected = ref(null)
const drawer = ref(false)
const loading = ref(false)
const saving = ref(false)
const error = ref('')
const meta = ref({ total: 0, new_count: 0 })
const filters = ref({
  search: '',
  status: null,
})
const editForm = ref({
  status: 'new',
  admin_notes: '',
})

const statusOptions = [
  { title: 'New', value: 'new' },
  { title: 'Reviewing', value: 'reviewing' },
  { title: 'Contacted', value: 'contacted' },
  { title: 'Qualified', value: 'qualified' },
  { title: 'Not fit', value: 'not_fit' },
  { title: 'Closed', value: 'closed' },
]

const headers = [
  { title: 'Business', value: 'business' },
  { title: 'Contact', value: 'contact' },
  { title: 'Timeline', value: 'timeline' },
  { title: 'Status', value: 'status' },
  { title: 'Submitted', value: 'created_at' },
  { title: 'Actions', value: 'actions', sortable: false },
]

function authHeaders() {
  return {
    Authorization: `Bearer ${localStorage.getItem('token')}`,
  }
}

async function loadInquiries() {
  loading.value = true
  error.value = ''

  try {
    const response = await axios.get(`${API_BASE_URL}/marketing-inquiries`, {
      headers: authHeaders(),
      params: {
        search: filters.value.search || undefined,
        status: filters.value.status || undefined,
        per_page: 100,
      },
    })

    inquiries.value = response.data.data || []
    meta.value = response.data.meta || { total: inquiries.value.length, new_count: 0 }
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to load marketing leads.'
  } finally {
    loading.value = false
  }
}

function openDrawer(inquiry) {
  selected.value = { ...inquiry }
  editForm.value = {
    status: inquiry.status || 'new',
    admin_notes: inquiry.admin_notes || '',
  }
  drawer.value = true
}

async function saveLead() {
  if (!selected.value) return

  saving.value = true
  error.value = ''

  try {
    const response = await axios.patch(
      `${API_BASE_URL}/marketing-inquiries/${selected.value.id}`,
      editForm.value,
      { headers: authHeaders() },
    )

    selected.value = response.data
    const index = inquiries.value.findIndex((inquiry) => inquiry.id === selected.value.id)
    if (index !== -1) inquiries.value[index] = selected.value
    await loadInquiries()
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to save lead status.'
  } finally {
    saving.value = false
  }
}

function formatType(value) {
  if (!value) return 'Not set'
  return String(value)
    .replaceAll('-', ' ')
    .replaceAll('_', ' ')
    .replace(/\b\w/g, (letter) => letter.toUpperCase())
}

function formatDate(value) {
  if (!value) return 'Not set'
  return new Intl.DateTimeFormat(undefined, {
    dateStyle: 'medium',
    timeStyle: 'short',
  }).format(new Date(value))
}

function statusColor(status) {
  return {
    new: 'warning',
    reviewing: 'info',
    contacted: 'primary',
    qualified: 'success',
    not_fit: 'error',
    closed: 'grey',
  }[status] || 'primary'
}

onMounted(loadInquiries)
</script>

<style scoped>
.leads-page {
  padding-left: 0 !important;
  padding-right: 0 !important;
}

.leads-panel {
  border: 1px solid var(--rs-border);
  border-radius: 24px;
  background: linear-gradient(180deg, rgba(18, 28, 43, 0.98), rgba(12, 20, 33, 0.98)) !important;
}

.leads-title-row {
  gap: 12px;
  padding: 24px;
}

.leads-title {
  color: var(--rs-accent);
}

.leads-subtitle {
  color: var(--rs-text-muted);
}

.leads-table {
  border-radius: 18px;
  overflow: hidden;
}

.lead-main {
  display: grid;
  gap: 3px;
}

.lead-main strong {
  color: var(--rs-text);
  font-weight: 800;
}

.lead-main span {
  color: var(--rs-text-muted);
  font-size: 0.86rem;
}

.leads-button {
  color: #07111f !important;
  font-weight: 800;
}

.lead-drawer {
  border-radius: 24px 0 0 24px;
}

.drawer-body {
  display: grid;
  gap: 18px;
  padding: 22px;
}

.drawer-section {
  display: grid;
  gap: 10px;
  border: 1px solid rgba(15, 23, 42, 0.1);
  border-radius: 14px;
  padding: 16px;
  background: #fff;
  color: #102033;
}

.drawer-section h2,
.drawer-section h3 {
  margin: 0;
  color: #102033;
}

.drawer-section p {
  margin: 0;
  color: #607086;
  white-space: pre-wrap;
}

.drawer-kicker {
  color: #128663;
  font-size: 0.78rem;
  font-weight: 900;
  text-transform: uppercase;
}

.detail-grid {
  display: grid;
  grid-template-columns: 110px minmax(0, 1fr);
  gap: 9px 12px;
  font-size: 0.92rem;
}

.detail-grid span {
  color: #6b7b90;
}

.detail-grid strong {
  color: #102033;
  overflow-wrap: anywhere;
}

.chip-list {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}
</style>
