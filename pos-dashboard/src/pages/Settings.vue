<template>
  <OwnerLayout>
    <v-container class="settings-page py-6" fluid>
      <div class="settings-header mb-5">
        <div>
          <div class="eyebrow">Settings</div>
          <h1>Branch Operations</h1>
          <p>{{ scopeLabel }}</p>
        </div>
        <div class="header-actions">
          <v-chip color="primary" variant="tonal">
            {{ branchSettings.length }} branches
          </v-chip>
          <v-btn
            color="primary"
            variant="flat"
            prepend-icon="mdi-refresh"
            :loading="loadingBranchSettings"
            @click="fetchBranchSettings"
          >
            Refresh
          </v-btn>
        </div>
      </div>

      <v-alert
        v-if="settingsError"
        type="error"
        variant="tonal"
        class="mb-4"
        closable
        @click:close="settingsError = ''"
      >
        {{ settingsError }}
      </v-alert>

      <v-alert
        v-if="settingsSaved"
        type="success"
        variant="tonal"
        class="mb-4"
        closable
        @click:close="settingsSaved = ''"
      >
        {{ settingsSaved }}
      </v-alert>

      <v-row>
        <v-col cols="12" xl="9">
          <v-card color="card" elevation="2" class="settings-card rounded-lg">
            <v-card-title class="d-flex align-center ga-3">
              <v-icon icon="mdi-store-cog-outline" color="primary" />
              <span class="settings-title">Cash Drawers and Receipt Printers</span>
            </v-card-title>
            <v-progress-linear
              v-if="loadingBranchSettings"
              color="primary"
              indeterminate
            />

            <v-card-text>
              <div v-if="!loadingBranchSettings && !branchSettings.length" class="empty-state">
                No branches are available for this account.
              </div>

              <div v-else class="branch-grid">
                <section
                  v-for="branch in branchSettings"
                  :key="branch.id"
                  class="branch-panel"
                >
                  <div class="branch-panel__header">
                    <div>
                      <div class="branch-name">{{ branch.name }}</div>
                      <div class="branch-meta">
                        {{ branch.restaurant?.name || 'Restaurant' }}
                        <span v-if="branch.location">/ {{ branch.location }}</span>
                      </div>
                    </div>
                    <v-chip
                      :color="branch.cash_drawer.is_open ? 'success' : 'warning'"
                      variant="tonal"
                      size="small"
                    >
                      {{ branch.cash_drawer.is_open ? 'Drawer open' : 'Drawer closed' }}
                    </v-chip>
                  </div>

                  <v-row>
                    <v-col cols="12" lg="6">
                      <div class="setting-panel">
                        <div class="panel-heading">
                          <v-icon icon="mdi-cash-register" color="success" />
                          <span>Cash drawer</span>
                        </div>
                        <v-row dense>
                          <v-col cols="12" sm="6">
                            <v-text-field
                              v-model.number="branch.cash_drawer.opening_balance"
                              label="Opening balance"
                              type="number"
                              min="0"
                              variant="outlined"
                              density="compact"
                            />
                          </v-col>
                          <v-col cols="12" sm="6">
                            <v-text-field
                              v-model.number="branch.cash_drawer.closing_balance"
                              label="Closing balance"
                              type="number"
                              min="0"
                              variant="outlined"
                              density="compact"
                            />
                          </v-col>
                          <v-col cols="12">
                            <v-switch
                              v-model="branch.cash_drawer.is_open"
                              color="success"
                              label="Drawer is open"
                              inset
                              density="compact"
                              hide-details
                            />
                          </v-col>
                          <v-col cols="12" sm="6">
                            <v-text-field
                              v-model="branch.cash_drawer.name"
                              label="Drawer name"
                              variant="outlined"
                              density="compact"
                            />
                          </v-col>
                          <v-col cols="12" sm="6">
                            <v-text-field
                              v-model="branch.cash_drawer.uuid"
                              label="Drawer UUID"
                              variant="outlined"
                              density="compact"
                            />
                          </v-col>
                          <v-col cols="12">
                            <v-text-field
                              v-model="branch.cash_drawer.printer_endpoint"
                              label="Kick endpoint"
                              placeholder="usb://drawer-1"
                              variant="outlined"
                              density="compact"
                            />
                          </v-col>
                          <v-col cols="12">
                            <v-switch
                              v-model="branch.cash_drawer.is_active"
                              color="primary"
                              label="Drawer device active"
                              inset
                              density="compact"
                              hide-details
                            />
                          </v-col>
                        </v-row>
                      </div>
                    </v-col>

                    <v-col cols="12" lg="6">
                      <div class="setting-panel">
                        <div class="panel-heading">
                          <v-icon icon="mdi-printer" color="primary" />
                          <span>Receipt printer</span>
                        </div>
                        <v-row dense>
                          <v-col cols="12" sm="6">
                            <v-text-field
                              v-model="branch.receipt_printer.name"
                              label="Printer name"
                              variant="outlined"
                              density="compact"
                            />
                          </v-col>
                          <v-col cols="12" sm="6">
                            <v-text-field
                              v-model="branch.receipt_printer.uuid"
                              label="Printer UUID"
                              variant="outlined"
                              density="compact"
                            />
                          </v-col>
                          <v-col cols="12" sm="6">
                            <v-select
                              v-model="branch.receipt_printer.printer_profile"
                              :items="printerProfiles"
                              label="Printer profile"
                              variant="outlined"
                              density="compact"
                            />
                          </v-col>
                          <v-col cols="12" sm="6">
                            <v-select
                              v-model.number="branch.receipt_printer.printer_paper_width_mm"
                              :items="paperWidths"
                              label="Paper width"
                              suffix="mm"
                              variant="outlined"
                              density="compact"
                            />
                          </v-col>
                          <v-col cols="12">
                            <v-text-field
                              v-model="branch.receipt_printer.printer_endpoint"
                              label="Printer endpoint"
                              placeholder="tcp://192.168.1.50:9100"
                              variant="outlined"
                              density="compact"
                            />
                          </v-col>
                          <v-col cols="12">
                            <v-switch
                              v-model="branch.receipt_printer.is_active"
                              color="primary"
                              label="Printer active"
                              inset
                              density="compact"
                              hide-details
                            />
                          </v-col>
                        </v-row>
                      </div>
                    </v-col>
                  </v-row>

                  <div class="branch-actions">
                    <v-btn
                      color="primary"
                      prepend-icon="mdi-content-save"
                      :loading="savingBranchId === branch.id"
                      @click="saveBranch(branch)"
                    >
                      Save branch settings
                    </v-btn>
                  </div>
                </section>
              </div>
            </v-card-text>
          </v-card>
        </v-col>

        <v-col cols="12" xl="3">
          <v-card color="card" elevation="2" class="settings-card rounded-lg mb-4">
            <v-card-title class="settings-title">Environment</v-card-title>
            <v-card-text>
              <div class="meta-row">
                <span>API Base URL</span>
                <strong>{{ apiBaseUrl }}</strong>
              </div>
              <div class="meta-row">
                <span>Logged in user</span>
                <strong>{{ auth.user?.name || 'Unknown user' }}</strong>
              </div>
              <div class="meta-row">
                <span>Restaurant ID</span>
                <strong>{{ auth.user?.restaurant_id ?? 'N/A' }}</strong>
              </div>
              <div class="meta-row">
                <span>Branch ID</span>
                <strong>{{ auth.user?.branch_id ?? 'N/A' }}</strong>
              </div>
            </v-card-text>
          </v-card>

          <v-card color="card" elevation="2" class="settings-card rounded-lg">
            <v-card-title class="settings-title">Receipt Output</v-card-title>
            <v-card-text>
              <div class="receipt-sample">
                <div class="sample-logo">J</div>
                <div class="sample-name">Janova Restaurant</div>
                <div class="sample-line"></div>
                <div class="sample-row"><span>Subtotal</span><strong>340.00</strong></div>
                <div class="sample-row"><span>Tax</span><strong>47.60</strong></div>
                <div class="sample-row total"><span>Total</span><strong>387.60</strong></div>
                <div class="sample-footer">Janova Suite</div>
              </div>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>
    </v-container>
  </OwnerLayout>
</template>

<script setup>
import axios from 'axios'
import { computed, onMounted, ref } from 'vue'
import OwnerLayout from '@/layouts/OwnerLayout.vue'
import { API_BASE_URL } from '../lib/api'
import { useAuthStore } from '../store/auth'

const auth = useAuthStore()
const apiBaseUrl = API_BASE_URL
const branchSettings = ref([])
const loadingBranchSettings = ref(false)
const savingBranchId = ref(null)
const settingsError = ref('')
const settingsSaved = ref('')

const printerProfiles = [
  { title: 'Epson thermal / ESC-POS', value: 'epson-thermal' },
  { title: 'Network ESC/POS', value: 'escpos-network' },
  { title: 'Browser print', value: 'browser-print' },
]
const paperWidths = [58, 80]

const scopeLabel = computed(() => {
  if (auth.user?.role === 'admin') return 'Platform admins can configure every Janova branch.'
  if (auth.user?.branch_id) return 'This account can configure its assigned branch.'
  return 'Owners can configure the branches attached to their restaurant.'
})

function authHeaders() {
  return {
    Authorization: `Bearer ${auth.token || localStorage.getItem('token')}`,
  }
}

function normalizeBranch(branch) {
  return {
    ...branch,
    cash_drawer: {
      opening_balance: 0,
      closing_balance: null,
      is_open: true,
      name: '',
      uuid: '',
      printer_endpoint: '',
      is_active: true,
      ...(branch.cash_drawer || {}),
    },
    receipt_printer: {
      name: '',
      uuid: '',
      printer_profile: 'epson-thermal',
      printer_paper_width_mm: 80,
      printer_endpoint: '',
      is_active: true,
      ...(branch.receipt_printer || {}),
    },
  }
}

function errorMessage(error) {
  const errors = error.response?.data?.errors
  if (errors) {
    const first = Object.values(errors).flat()[0]
    if (first) return first
  }

  return error.response?.data?.message || 'Could not save settings.'
}

function cleanString(value) {
  return value === '' ? null : value
}

async function fetchBranchSettings() {
  loadingBranchSettings.value = true
  settingsError.value = ''

  try {
    const { data } = await axios.get(`${API_BASE_URL}/branch-operation-settings`, {
      headers: authHeaders(),
    })
    branchSettings.value = (data.data || []).map(normalizeBranch)
  } catch (error) {
    settingsError.value = errorMessage(error)
  } finally {
    loadingBranchSettings.value = false
  }
}

async function saveBranch(branch) {
  savingBranchId.value = branch.id
  settingsError.value = ''
  settingsSaved.value = ''

  const payload = {
    cash_drawer: {
      opening_balance: Number(branch.cash_drawer.opening_balance || 0),
      closing_balance: branch.cash_drawer.closing_balance === '' || branch.cash_drawer.closing_balance === null
        ? null
        : Number(branch.cash_drawer.closing_balance),
      is_open: Boolean(branch.cash_drawer.is_open),
      name: cleanString(branch.cash_drawer.name),
      uuid: cleanString(branch.cash_drawer.uuid),
      printer_endpoint: cleanString(branch.cash_drawer.printer_endpoint),
      is_active: Boolean(branch.cash_drawer.is_active),
    },
    receipt_printer: {
      name: cleanString(branch.receipt_printer.name),
      uuid: cleanString(branch.receipt_printer.uuid),
      printer_profile: cleanString(branch.receipt_printer.printer_profile),
      printer_paper_width_mm: Number(branch.receipt_printer.printer_paper_width_mm || 80),
      printer_endpoint: cleanString(branch.receipt_printer.printer_endpoint),
      is_active: Boolean(branch.receipt_printer.is_active),
    },
  }

  try {
    const { data } = await axios.put(`${API_BASE_URL}/branch-operation-settings/${branch.id}`, payload, {
      headers: authHeaders(),
    })
    const updated = normalizeBranch(data.data)
    branchSettings.value = branchSettings.value.map(item => item.id === updated.id ? updated : item)
    settingsSaved.value = `${updated.name} settings saved.`
  } catch (error) {
    settingsError.value = errorMessage(error)
  } finally {
    savingBranchId.value = null
  }
}

onMounted(fetchBranchSettings)
</script>

<style scoped>
.settings-page {
  color: #eef3ee;
}

.settings-header {
  align-items: flex-start;
  display: flex;
  gap: 1rem;
  justify-content: space-between;
}

.settings-header h1 {
  font-size: clamp(1.7rem, 3vw, 2.35rem);
  font-weight: 800;
  letter-spacing: 0;
  line-height: 1.1;
  margin: 0;
}

.settings-header p {
  color: #9fb2a8;
  margin: 0.45rem 0 0;
}

.header-actions {
  align-items: center;
  display: flex;
  flex-wrap: wrap;
  gap: 0.75rem;
  justify-content: flex-end;
}

.eyebrow,
.settings-title {
  color: #2a9d8f;
  font-weight: 800;
}

.eyebrow {
  font-size: 0.78rem;
  letter-spacing: 0.08em;
  text-transform: uppercase;
}

.settings-card {
  border: 1px solid rgba(255, 255, 255, 0.07);
}

.branch-grid {
  display: grid;
  gap: 1rem;
}

.branch-panel {
  background: rgba(15, 23, 42, 0.42);
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 8px;
  padding: 1rem;
}

.branch-panel__header {
  align-items: flex-start;
  display: flex;
  gap: 1rem;
  justify-content: space-between;
  margin-bottom: 1rem;
}

.branch-name {
  color: #f8fafc;
  font-size: 1.05rem;
  font-weight: 800;
}

.branch-meta {
  color: #9fb2a8;
  font-size: 0.9rem;
  margin-top: 0.2rem;
}

.setting-panel {
  background: rgba(255, 255, 255, 0.035);
  border: 1px solid rgba(255, 255, 255, 0.07);
  border-radius: 8px;
  height: 100%;
  padding: 1rem;
}

.panel-heading {
  align-items: center;
  color: #f8fafc;
  display: flex;
  font-weight: 800;
  gap: 0.55rem;
  margin-bottom: 0.9rem;
}

.branch-actions {
  display: flex;
  justify-content: flex-end;
  margin-top: 1rem;
}

.empty-state {
  border: 1px dashed rgba(255, 255, 255, 0.16);
  border-radius: 8px;
  color: #9fb2a8;
  padding: 2rem;
  text-align: center;
}

.meta-row {
  border-bottom: 1px solid rgba(255, 255, 255, 0.08);
  display: grid;
  gap: 0.35rem;
  padding: 0.85rem 0;
}

.meta-row:first-child {
  padding-top: 0;
}

.meta-row:last-child {
  border-bottom: 0;
  padding-bottom: 0;
}

.meta-row span {
  color: #9fb2a8;
  font-size: 0.78rem;
  text-transform: uppercase;
}

.meta-row strong {
  color: #eef3ee;
  font-size: 0.92rem;
  overflow-wrap: anywhere;
}

.receipt-sample {
  background: #f9fafb;
  border-radius: 8px;
  color: #111827;
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", monospace;
  margin-inline: auto;
  max-width: 280px;
  padding: 1.1rem;
  text-align: center;
}

.sample-logo {
  align-items: center;
  border: 2px solid #111827;
  border-radius: 50%;
  display: inline-flex;
  font-weight: 900;
  height: 42px;
  justify-content: center;
  width: 42px;
}

.sample-name {
  font-weight: 900;
  margin-top: 0.5rem;
}

.sample-line {
  border-top: 1px dashed #6b7280;
  margin: 0.9rem 0;
}

.sample-row {
  display: flex;
  justify-content: space-between;
  margin: 0.35rem 0;
}

.sample-row.total {
  border-top: 1px solid #111827;
  font-weight: 900;
  margin-top: 0.75rem;
  padding-top: 0.6rem;
}

.sample-footer {
  border-top: 1px dashed #6b7280;
  font-weight: 900;
  margin-top: 1rem;
  padding-top: 0.8rem;
}

@media (max-width: 720px) {
  .settings-header,
  .branch-panel__header {
    display: grid;
  }

  .header-actions,
  .branch-actions {
    justify-content: stretch;
  }

  .header-actions > *,
  .branch-actions > * {
    width: 100%;
  }
}
</style>
