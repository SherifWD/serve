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
            {{ branchCountLabel }}
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

              <template v-else-if="branchSettings.length">
                <div class="target-panel mb-4">
                  <div class="panel-heading">
                    <v-icon icon="mdi-tune-variant" color="primary" />
                    <span>Control target</span>
                  </div>
                  <v-row dense>
                    <v-col v-if="showRestaurantSelector" cols="12" md="6">
                      <v-select
                        v-model="selectedRestaurantKey"
                        :items="restaurantOptions"
                        item-title="title"
                        item-value="value"
                        label="Restaurant"
                        variant="outlined"
                        density="comfortable"
                      />
                    </v-col>
                    <v-col cols="12" :md="showRestaurantSelector ? 6 : 12">
                      <v-select
                        v-model="selectedBranchId"
                        :items="branchOptions"
                        item-title="title"
                        item-value="value"
                        label="Branch"
                        variant="outlined"
                        density="comfortable"
                      />
                    </v-col>
                  </v-row>
                  <div v-if="selectedBranch" class="target-summary">
                    Editing {{ selectedBranch.restaurant?.name || 'Restaurant' }} / {{ selectedBranch.name }}
                    <span v-if="selectedBranch.location">/ {{ selectedBranch.location }}</span>
                  </div>
                </div>

                <div v-if="selectedBranch" class="discovery-panel mb-4">
                  <div class="panel-heading">
                    <v-icon icon="mdi-radar" color="primary" />
                    <span>Device discovery</span>
                  </div>
                  <v-row dense align="center">
                    <v-col cols="12" md="3">
                      <v-select
                        v-model="discoveryMode"
                        :items="discoveryModes"
                        item-title="title"
                        item-value="value"
                        label="Scan mode"
                        variant="outlined"
                        density="comfortable"
                      />
                    </v-col>
                    <v-col cols="12" md="4">
                      <v-text-field
                        v-model="discoveryNetworkTarget"
                        label="Network target"
                        placeholder="192.168.1.50 or 192.168.1.0/26"
                        :disabled="discoveryMode === 'local'"
                        variant="outlined"
                        density="comfortable"
                      />
                    </v-col>
                    <v-col cols="12" md="3">
                      <v-text-field
                        v-model="discoveryPorts"
                        label="Ports"
                        placeholder="9100,515,631"
                        :disabled="discoveryMode === 'local'"
                        variant="outlined"
                        density="comfortable"
                      />
                    </v-col>
                    <v-col cols="12" md="2" class="discovery-action-col">
                      <v-btn
                        color="primary"
                        prepend-icon="mdi-magnify-scan"
                        :loading="discoveringDevices"
                        :disabled="!canRunDiscovery"
                        block
                        @click="discoverDevices"
                      >
                        Search
                      </v-btn>
                    </v-col>
                  </v-row>

                  <v-alert
                    v-if="discoveryNotice"
                    type="info"
                    variant="tonal"
                    density="compact"
                    class="mt-2 mb-0"
                  >
                    {{ discoveryNotice }}
                  </v-alert>

                  <div v-if="discoveredDevices.length" class="discovery-results">
                    <article
                      v-for="device in discoveredDevices"
                      :key="device.uuid"
                      class="discovery-result"
                    >
                      <div>
                        <div class="device-name">{{ device.name }}</div>
                        <div class="device-meta">{{ deviceCaption(device) }}</div>
                      </div>
                      <div class="result-actions">
                        <v-btn
                          size="small"
                          variant="tonal"
                          color="primary"
                          prepend-icon="mdi-printer"
                          @click="applyDiscoveredDevice(device, 'printer')"
                        >
                          Use printer
                        </v-btn>
                        <v-btn
                          size="small"
                          variant="tonal"
                          color="success"
                          prepend-icon="mdi-cash-register"
                          :disabled="!device.capabilities?.cash_drawer"
                          @click="applyDiscoveredDevice(device, 'drawer')"
                        >
                          Use drawer
                        </v-btn>
                      </div>
                    </article>
                  </div>
                </div>

                <div v-if="selectedBranch" class="branch-grid">
                  <section
                    v-for="branch in visibleBranches"
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
                      <div class="branch-chip-stack">
                        <v-chip color="primary" variant="tonal" size="small">
                          {{ branch.operation_profile.label }}
                        </v-chip>
                        <v-chip
                          :color="branch.cash_drawer.is_open ? 'success' : 'warning'"
                          variant="tonal"
                          size="small"
                        >
                          {{ branch.cash_drawer.is_open ? 'Drawer open' : 'Drawer closed' }}
                        </v-chip>
                      </div>
                    </div>

                    <div class="setting-panel mb-4">
                      <div class="panel-heading">
                        <v-icon icon="mdi-store-settings-outline" color="primary" />
                        <span>Business mode and workflow</span>
                      </div>
                      <v-row dense>
                        <v-col cols="12" md="4">
                          <v-select
                            v-model="branch.operation_profile.mode"
                            :items="operationModes"
                            item-title="title"
                            item-value="value"
                            label="Operation preset"
                            variant="outlined"
                            density="compact"
                            @update:model-value="applyOperationPreset(branch)"
                          />
                        </v-col>
                        <v-col cols="12" md="4">
                          <v-text-field
                            v-model="branch.operation_profile.label"
                            label="Staff-facing label"
                            variant="outlined"
                            density="compact"
                          />
                        </v-col>
                        <v-col cols="12" md="4">
                          <v-text-field
                            v-model.number="branch.operation_profile.features.station_count"
                            label="KDS stations"
                            type="number"
                            min="1"
                            max="8"
                            variant="outlined"
                            density="compact"
                            :disabled="!branch.operation_profile.features.multi_kds_stations"
                          />
                        </v-col>
                      </v-row>
                      <div class="feature-grid">
                        <v-switch
                          v-for="feature in operationFeatureOptions"
                          :key="feature.key"
                          v-model="branch.operation_profile.features[feature.key]"
                          :label="feature.label"
                          :hint="feature.hint"
                          color="primary"
                          density="compact"
                          persistent-hint
                          inset
                        />
                      </div>
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

                <div v-else class="empty-state">
                  Select a branch to edit.
                </div>
              </template>
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
import { computed, onMounted, ref, watch } from 'vue'
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
const selectedRestaurantKey = ref(null)
const selectedBranchId = ref(null)
const discoveringDevices = ref(false)
const discoveredDevices = ref([])
const discoveryMode = ref('all')
const discoveryNetworkTarget = ref('')
const discoveryPorts = ref('9100,515,631')
const discoveryNotice = ref('')

const printerProfiles = [
  { title: 'Epson thermal / ESC-POS', value: 'epson-thermal' },
  { title: 'Network ESC/POS', value: 'escpos-network' },
  { title: 'System printer', value: 'system-printer' },
  { title: 'IPP printer', value: 'ipp-printer' },
  { title: 'LPD printer', value: 'lpd-printer' },
  { title: 'Browser print', value: 'browser-print' },
]
const paperWidths = [58, 80]
const discoveryModes = [
  { title: 'Local + network', value: 'all' },
  { title: 'Local only', value: 'local' },
  { title: 'Network only', value: 'network' },
]
const operationPresets = {
  drinks_only_cafe: {
    label: 'Drinks-only cafe',
    features: {
      uses_tables: false,
      cashier_first: true,
      kds_enabled: false,
      waiter_table_ownership: false,
      show_waiter_names: false,
      table_transfer: false,
      split_bills: false,
      multi_kds_stations: false,
      station_count: 1,
      customer_ordering: true,
    },
  },
  cafe_with_barista: {
    label: 'Cafe with barista',
    features: {
      uses_tables: false,
      cashier_first: true,
      kds_enabled: true,
      waiter_table_ownership: false,
      show_waiter_names: false,
      table_transfer: false,
      split_bills: true,
      multi_kds_stations: false,
      station_count: 1,
      customer_ordering: true,
    },
  },
  small_restaurant: {
    label: 'Small restaurant',
    features: {
      uses_tables: true,
      cashier_first: false,
      kds_enabled: true,
      waiter_table_ownership: true,
      show_waiter_names: false,
      table_transfer: true,
      split_bills: true,
      multi_kds_stations: false,
      station_count: 1,
      customer_ordering: true,
    },
  },
  big_restaurant: {
    label: 'Big restaurant',
    features: {
      uses_tables: true,
      cashier_first: false,
      kds_enabled: true,
      waiter_table_ownership: true,
      show_waiter_names: true,
      table_transfer: true,
      split_bills: true,
      multi_kds_stations: true,
      station_count: 3,
      customer_ordering: true,
    },
  },
  custom: {
    label: 'Custom operation',
    features: {
      uses_tables: true,
      cashier_first: false,
      kds_enabled: true,
      waiter_table_ownership: true,
      show_waiter_names: true,
      table_transfer: true,
      split_bills: true,
      multi_kds_stations: false,
      station_count: 1,
      customer_ordering: true,
    },
  },
}
const operationModes = [
  { title: 'Drinks-only cafe / cashier first', value: 'drinks_only_cafe' },
  { title: 'Cafe with barista KDS', value: 'cafe_with_barista' },
  { title: 'Small restaurant', value: 'small_restaurant' },
  { title: 'Big restaurant', value: 'big_restaurant' },
  { title: 'Custom', value: 'custom' },
]
const operationFeatureOptions = [
  { key: 'uses_tables', label: 'Use tables', hint: 'Show waiter table workflow.' },
  { key: 'cashier_first', label: 'Cashier-first ordering', hint: 'Counter order starts at cashier.' },
  { key: 'kds_enabled', label: 'Use kitchen display', hint: 'Send items to KDS/barista.' },
  { key: 'waiter_table_ownership', label: 'Waiter owns opened tables', hint: 'Enable My tables and unassigned filters.' },
  { key: 'show_waiter_names', label: 'Show waiter names', hint: 'Useful for large floors.' },
  { key: 'table_transfer', label: 'Allow table moves', hint: 'Move active orders between tables.' },
  { key: 'split_bills', label: 'Allow split bills', hint: 'Enable item/tender split workflows.' },
  { key: 'multi_kds_stations', label: 'Multiple KDS stations', hint: 'Plan routing by bar/kitchen stations.' },
  { key: 'customer_ordering', label: 'Customer ordering', hint: 'Allow pickup/QR style customer orders.' },
]

const scopeLabel = computed(() => {
  if (auth.user?.role === 'admin') return 'Platform admins can configure every Janova branch.'
  if (auth.user?.branch_id) return 'This account can configure its assigned branch.'
  return 'Owners can configure the branches attached to their restaurant.'
})

const restaurantOptions = computed(() => {
  const restaurants = new Map()

  branchSettings.value.forEach(branch => {
    const value = restaurantKey(branch)
    if (!restaurants.has(value)) {
      restaurants.set(value, {
        title: branch.restaurant?.name || 'Unassigned restaurant',
        value,
      })
    }
  })

  return Array.from(restaurants.values()).sort((a, b) => a.title.localeCompare(b.title))
})

const showRestaurantSelector = computed(() => (
  auth.user?.role === 'admin' || restaurantOptions.value.length > 1
))

const filteredBranches = computed(() => {
  if (!selectedRestaurantKey.value) return branchSettings.value

  return branchSettings.value.filter(branch => restaurantKey(branch) === selectedRestaurantKey.value)
})

const branchOptions = computed(() => filteredBranches.value.map(branch => ({
  title: branch.location ? `${branch.name} / ${branch.location}` : branch.name,
  value: branch.id,
})))

const selectedBranch = computed(() => branchSettings.value.find(branch => (
  Number(branch.id) === Number(selectedBranchId.value)
)) || null)

const visibleBranches = computed(() => selectedBranch.value ? [selectedBranch.value] : [])

const canRunDiscovery = computed(() => (
  Boolean(selectedBranch.value)
  && !discoveringDevices.value
  && (discoveryMode.value !== 'network' || String(discoveryNetworkTarget.value || '').trim().length > 0)
))

const branchCountLabel = computed(() => {
  if (!branchSettings.value.length) return '0 branches'
  if (!selectedBranch.value) return `${branchSettings.value.length} branches`

  return `1 of ${branchSettings.value.length} branches`
})

function authHeaders() {
  return {
    Authorization: `Bearer ${auth.token || localStorage.getItem('token')}`,
  }
}

function restaurantKey(branch) {
  return branch.restaurant?.id !== undefined && branch.restaurant?.id !== null
    ? String(branch.restaurant.id)
    : 'unassigned'
}

function ensureSelection() {
  if (!branchSettings.value.length) {
    selectedRestaurantKey.value = null
    selectedBranchId.value = null
    return
  }

  if (!restaurantOptions.value.some(option => option.value === selectedRestaurantKey.value)) {
    selectedRestaurantKey.value = restaurantOptions.value[0]?.value ?? null
  }

  const hasSelectedBranch = filteredBranches.value.some(branch => (
    Number(branch.id) === Number(selectedBranchId.value)
  ))

  if (!hasSelectedBranch) {
    selectedBranchId.value = filteredBranches.value[0]?.id ?? null
  }
}

function normalizeBranch(branch) {
  return {
    ...branch,
    operation_profile: normalizeOperationProfile(branch.operation_profile, branch.restaurant?.kind),
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

function defaultOperationMode(kind) {
  return kind === 'cafe' ? 'cafe_with_barista' : 'small_restaurant'
}

function normalizeOperationProfile(profile = {}, kind = 'restaurant') {
  const mode = operationPresets[profile.mode] ? profile.mode : defaultOperationMode(kind)
  const preset = operationPresets[mode]

  return {
    mode,
    label: profile.label || preset.label,
    features: {
      ...preset.features,
      ...(profile.features || {}),
      station_count: Math.min(8, Math.max(1, Number(profile.features?.station_count || preset.features.station_count || 1))),
    },
  }
}

function applyOperationPreset(branch) {
  const mode = branch.operation_profile?.mode || defaultOperationMode(branch.restaurant?.kind)
  const preset = operationPresets[mode] || operationPresets.custom
  branch.operation_profile = {
    mode,
    label: preset.label,
    features: { ...preset.features },
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
  return value === undefined || value === null || value === '' ? null : value
}

function discoveryPortList() {
  const ports = String(discoveryPorts.value || '')
    .split(/[,\s]+/)
    .map(value => Number(value.trim()))
    .filter(port => Number.isInteger(port) && port >= 1 && port <= 65535)

  const uniquePorts = [...new Set(ports)].slice(0, 5)

  return uniquePorts.length ? uniquePorts : [9100, 515, 631]
}

function clearDiscoveryResults() {
  discoveredDevices.value = []
  discoveryNotice.value = ''
}

function deviceCaption(device) {
  return [
    device.printer_endpoint,
    device.printer_profile,
    device.source,
  ].filter(Boolean).join(' / ')
}

async function discoverDevices() {
  if (!selectedBranch.value) return

  discoveringDevices.value = true
  settingsError.value = ''
  settingsSaved.value = ''
  discoveryNotice.value = ''
  discoveredDevices.value = []

  const payload = {
    mode: discoveryMode.value,
    network_target: discoveryMode.value === 'local'
      ? null
      : cleanString(String(discoveryNetworkTarget.value || '').trim()),
    ports: discoveryPortList(),
  }

  try {
    const { data } = await axios.post(
      `${API_BASE_URL}/branch-operation-settings/${selectedBranch.value.id}/discover-devices`,
      payload,
      { headers: authHeaders() },
    )

    discoveredDevices.value = data.data || []
    if (discoveredDevices.value.length) {
      discoveryNotice.value = `Found ${discoveredDevices.value.length} device${discoveredDevices.value.length === 1 ? '' : 's'}.`
    } else if (data.meta?.network_target_required) {
      discoveryNotice.value = 'Local scan completed. Add a private LAN target to include network devices.'
    } else {
      discoveryNotice.value = 'No matching devices found.'
    }
  } catch (error) {
    settingsError.value = errorMessage(error)
  } finally {
    discoveringDevices.value = false
  }
}

function applyDiscoveredDevice(device, target) {
  const branch = selectedBranch.value
  if (!branch) return

  if (target === 'printer') {
    branch.receipt_printer = {
      ...branch.receipt_printer,
      name: device.name || branch.receipt_printer.name,
      uuid: device.uuid || branch.receipt_printer.uuid,
      printer_profile: device.printer_profile || branch.receipt_printer.printer_profile,
      printer_paper_width_mm: device.printer_paper_width_mm || branch.receipt_printer.printer_paper_width_mm || 80,
      printer_endpoint: cleanString(device.printer_endpoint) || branch.receipt_printer.printer_endpoint,
      is_active: true,
    }
  } else {
    branch.cash_drawer = {
      ...branch.cash_drawer,
      name: device.name ? `${device.name} drawer` : branch.cash_drawer.name,
      uuid: device.uuid ? `${device.uuid}-drawer` : branch.cash_drawer.uuid,
      printer_endpoint: cleanString(device.printer_endpoint) || branch.cash_drawer.printer_endpoint,
      is_active: true,
    }
  }

  settingsSaved.value = `${target === 'printer' ? 'Printer' : 'Drawer'} details filled for ${branch.name}. Save branch settings to apply.`
}

async function fetchBranchSettings() {
  loadingBranchSettings.value = true
  settingsError.value = ''

  try {
    const { data } = await axios.get(`${API_BASE_URL}/branch-operation-settings`, {
      headers: authHeaders(),
    })
    branchSettings.value = (data.data || []).map(normalizeBranch)
    ensureSelection()
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
    operation_profile: {
      mode: branch.operation_profile.mode,
      label: cleanString(branch.operation_profile.label),
      features: {
        uses_tables: Boolean(branch.operation_profile.features.uses_tables),
        cashier_first: Boolean(branch.operation_profile.features.cashier_first),
        kds_enabled: Boolean(branch.operation_profile.features.kds_enabled),
        waiter_table_ownership: Boolean(branch.operation_profile.features.waiter_table_ownership),
        show_waiter_names: Boolean(branch.operation_profile.features.show_waiter_names),
        table_transfer: Boolean(branch.operation_profile.features.table_transfer),
        split_bills: Boolean(branch.operation_profile.features.split_bills),
        multi_kds_stations: Boolean(branch.operation_profile.features.multi_kds_stations),
        station_count: Math.min(8, Math.max(1, Number(branch.operation_profile.features.station_count || 1))),
        customer_ordering: Boolean(branch.operation_profile.features.customer_ordering),
      },
    },
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
    ensureSelection()
    settingsSaved.value = `${updated.name} settings saved.`
  } catch (error) {
    settingsError.value = errorMessage(error)
  } finally {
    savingBranchId.value = null
  }
}

watch(selectedRestaurantKey, () => {
  const selectedStillVisible = filteredBranches.value.some(branch => (
    Number(branch.id) === Number(selectedBranchId.value)
  ))

  if (!selectedStillVisible) {
    selectedBranchId.value = filteredBranches.value[0]?.id ?? null
  }
})

watch(selectedBranchId, clearDiscoveryResults)

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

.target-panel {
  background: rgba(42, 157, 143, 0.08);
  border: 1px solid rgba(42, 157, 143, 0.22);
  border-radius: 8px;
  padding: 1rem;
}

.target-summary {
  color: #b7c4bd;
  font-size: 0.9rem;
  margin-top: 0.35rem;
}

.discovery-panel {
  background: rgba(59, 130, 246, 0.08);
  border: 1px solid rgba(147, 197, 253, 0.2);
  border-radius: 8px;
  padding: 1rem;
}

.discovery-action-col {
  align-self: flex-start;
}

.discovery-results {
  display: grid;
  gap: 0.7rem;
  margin-top: 0.8rem;
}

.discovery-result {
  align-items: center;
  background: rgba(255, 255, 255, 0.04);
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 8px;
  display: flex;
  gap: 1rem;
  justify-content: space-between;
  padding: 0.8rem;
}

.device-name {
  color: #f8fafc;
  font-weight: 800;
}

.device-meta {
  color: #9fb2a8;
  font-size: 0.82rem;
  margin-top: 0.18rem;
  overflow-wrap: anywhere;
}

.result-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  justify-content: flex-end;
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

.branch-chip-stack {
  align-items: flex-end;
  display: flex;
  flex-direction: column;
  gap: 0.45rem;
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

.feature-grid {
  display: grid;
  gap: 0.25rem 1rem;
  grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
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
  .branch-panel__header,
  .discovery-result {
    display: grid;
  }

  .header-actions,
  .branch-actions,
  .result-actions {
    justify-content: stretch;
  }

  .header-actions > *,
  .branch-actions > *,
  .result-actions > * {
    width: 100%;
  }
}
</style>
