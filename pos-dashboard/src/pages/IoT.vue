<template>
  <OwnerLayout>
    <v-container class="py-6">
      <v-row>
        <v-col cols="12" md="4">
          <v-card class="rounded-xl mb-6" elevation="3">
            <v-card-title class="d-flex align-center">
              <span class="text-h6 font-weight-bold">Devices</span>
              <v-spacer />
              <v-btn icon color="primary" :loading="loading.devices" @click="fetchDevices">
                <v-icon>mdi-refresh</v-icon>
              </v-btn>
            </v-card-title>
            <v-card-text>
              <v-alert v-if="errors.devices" type="error" variant="tonal" class="mb-4">
                {{ errors.devices }}
              </v-alert>
              <v-form class="mb-4" :disabled="loading.devices" @submit.prevent="createDevice">
                <v-row dense>
                  <v-col cols="12">
                    <v-text-field v-model="forms.device.name" label="Device Name" dense required />
                  </v-col>
                  <v-col cols="12">
                    <v-text-field v-model="forms.device.device_key" label="Device Key" dense required />
                  </v-col>
                  <v-col cols="12">
                    <v-text-field v-model="forms.device.device_type" label="Type" dense />
                  </v-col>
                  <v-col cols="12">
                    <v-select
                      v-model="forms.device.status"
                      :items="deviceStatuses"
                      label="Status"
                      dense
                    />
                  </v-col>
                  <v-col cols="12">
                    <v-textarea
                      v-model="forms.device.location"
                      label="Location Details"
                      rows="2"
                      density="compact"
                      hint="Example: Line 3, Assembly"
                    />
                  </v-col>
                  <v-col cols="12">
                    <v-btn type="submit" color="primary" class="rounded-pill" :loading="loading.devices">
                      <v-icon start>mdi-access-point</v-icon>
                      Register Device
                    </v-btn>
                  </v-col>
                </v-row>
              </v-form>
              <v-data-table
                :headers="deviceHeaders"
                :items="devices"
                :loading="loading.devices"
                density="comfortable"
                class="rounded-lg"
              >
                <template #item.status="{ item }">
                  <v-chip size="small" :color="deviceColor(item.status)" variant="flat">
                    {{ item.status }}
                  </v-chip>
                </template>
                <template #item.location="{ item }">
                  {{ formatLocation(item.location) }}
                </template>
              </v-data-table>
            </v-card-text>
          </v-card>
        </v-col>

        <v-col cols="12" md="8">
          <v-card class="rounded-xl mb-6" elevation="3">
            <v-card-title class="d-flex align-center">
              <span class="text-h6 font-weight-bold">Sensors</span>
              <v-spacer />
              <v-btn icon color="primary" :loading="loading.sensors" @click="fetchSensors">
                <v-icon>mdi-refresh</v-icon>
              </v-btn>
            </v-card-title>
            <v-card-text>
              <v-alert v-if="errors.sensors" type="error" variant="tonal" class="mb-4">
                {{ errors.sensors }}
              </v-alert>
              <v-form class="mb-4" :disabled="loading.sensors" @submit.prevent="createSensor">
                <v-row dense>
                  <v-col cols="12" md="4">
                    <v-select
                      v-model="forms.sensor.device_id"
                      :items="devices"
                      item-title="name"
                      item-value="id"
                      label="Device"
                      dense
                      required
                    />
                  </v-col>
                  <v-col cols="12" md="4">
                    <v-text-field v-model="forms.sensor.tag" label="Tag" dense required />
                  </v-col>
                  <v-col cols="12" md="4">
                    <v-text-field v-model="forms.sensor.name" label="Sensor Name" dense required />
                  </v-col>
                  <v-col cols="6">
                    <v-text-field v-model="forms.sensor.unit" label="Unit" dense />
                  </v-col>
                  <v-col cols="6">
                    <v-select
                      v-model="forms.sensor.data_type"
                      :items="dataTypes"
                      label="Data Type"
                      dense
                    />
                  </v-col>
                  <v-col cols="6">
                    <v-text-field
                      v-model="forms.sensor.threshold_min"
                      label="Min Threshold"
                      type="number"
                      dense
                    />
                  </v-col>
                  <v-col cols="6">
                    <v-text-field
                      v-model="forms.sensor.threshold_max"
                      label="Max Threshold"
                      type="number"
                      dense
                    />
                  </v-col>
                  <v-col cols="12">
                    <v-btn type="submit" color="primary" class="rounded-pill" :loading="loading.sensors">
                      <v-icon start>mdi-chart-line-variant</v-icon>
                      Add Sensor
                    </v-btn>
                  </v-col>
                </v-row>
              </v-form>

              <v-data-table
                :headers="sensorHeaders"
                :items="sensors"
                :loading="loading.sensors"
                density="comfortable"
                class="rounded-lg"
              >
                <template #item.device="{ item }">
                  {{ deviceLookup[item.device_id] ?? '—' }}
                </template>
                <template #item.thresholds="{ item }">
                  <span v-if="item.threshold_min !== null || item.threshold_max !== null">
                    {{ item.threshold_min ?? '—' }} to {{ item.threshold_max ?? '—' }}
                  </span>
                  <span v-else>—</span>
                </template>
              </v-data-table>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>

      <v-row>
        <v-col cols="12">
          <v-card class="rounded-xl" elevation="3">
            <v-card-title class="d-flex align-center">
              <span class="text-h6 font-weight-bold">Latest Readings</span>
              <v-spacer />
              <v-btn icon color="primary" :loading="loading.readings" @click="fetchReadings">
                <v-icon>mdi-refresh</v-icon>
              </v-btn>
            </v-card-title>
            <v-card-text>
              <v-alert v-if="errors.readings" type="error" variant="tonal" class="mb-4">
                {{ errors.readings }}
              </v-alert>
              <v-form class="mb-4" :disabled="loading.readings" @submit.prevent="createReading">
                <v-row dense>
                  <v-col cols="12" md="4">
                    <v-select
                      v-model="forms.reading.sensor_id"
                      :items="sensors"
                      item-title="name"
                      item-value="id"
                      label="Sensor"
                      dense
                      required
                    />
                  </v-col>
                  <v-col cols="12" md="4">
                    <v-text-field
                      v-model="forms.reading.recorded_at"
                      type="datetime-local"
                      label="Recorded At"
                      dense
                      required
                    />
                  </v-col>
                  <v-col cols="12" md="4">
                    <v-text-field
                      v-model="forms.reading.value"
                      label="Value"
                      type="number"
                      dense
                    />
                  </v-col>
                  <v-col cols="12" md="4">
                    <v-select
                      v-model="forms.reading.quality"
                      :items="readingQualities"
                      label="Quality"
                      dense
                    />
                  </v-col>
                  <v-col cols="12">
                    <v-btn type="submit" color="primary" class="rounded-pill" :loading="loading.readings">
                      <v-icon start>mdi-transit-connection</v-icon>
                      Log Reading
                    </v-btn>
                  </v-col>
                </v-row>
              </v-form>
              <v-data-table
                :headers="readingHeaders"
                :items="readings"
                :loading="loading.readings"
                density="comfortable"
                class="rounded-lg"
              >
                <template #item.sensor="{ item }">
                  {{ sensorLookup[item.sensor_id] ?? '—' }}
                </template>
                <template #item.recorded_at="{ item }">
                  {{ formatDate(item.recorded_at) }}
                </template>
              </v-data-table>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>
    </v-container>
  </OwnerLayout>
</template>

<script setup>
import axios from 'axios'
import { computed, onMounted, reactive, ref } from 'vue'
import OwnerLayout from '../layouts/OwnerLayout.vue'

const API_BASE = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api'

const devices = ref([])
const sensors = ref([])
const readings = ref([])

const loading = reactive({
  devices: false,
  sensors: false,
  readings: false
})

const errors = reactive({
  devices: null,
  sensors: null,
  readings: null
})

const forms = reactive({
  device: {
    name: '',
    device_key: '',
    device_type: '',
    status: 'active',
    location: ''
  },
  sensor: {
    device_id: null,
    tag: '',
    name: '',
    unit: '',
    data_type: 'float',
    threshold_min: '',
    threshold_max: ''
  },
  reading: {
    sensor_id: null,
    recorded_at: '',
    value: '',
    quality: 'good'
  }
})

const deviceHeaders = [
  { title: 'Name', key: 'name' },
  { title: 'Key', key: 'device_key' },
  { title: 'Type', key: 'device_type' },
  { title: 'Status', key: 'status' },
  { title: 'Location', key: 'location' }
]

const sensorHeaders = [
  { title: 'Tag', key: 'tag' },
  { title: 'Name', key: 'name' },
  { title: 'Device', key: 'device' },
  { title: 'Unit', key: 'unit' },
  { title: 'Type', key: 'data_type' },
  { title: 'Thresholds', key: 'thresholds' }
]

const readingHeaders = [
  { title: 'Sensor', key: 'sensor' },
  { title: 'Recorded At', key: 'recorded_at' },
  { title: 'Value', key: 'value' },
  { title: 'Quality', key: 'quality' }
]

const deviceStatuses = ['inactive', 'active', 'maintenance', 'offline']
const dataTypes = ['float', 'integer', 'boolean', 'string']
const readingQualities = ['good', 'warning', 'alarm']

const deviceLookup = computed(() => {
  const map = {}
  for (const device of devices.value ?? []) {
    map[device.id] = device.name
  }
  return map
})

const sensorLookup = computed(() => {
  const map = {}
  for (const sensor of sensors.value ?? []) {
    map[sensor.id] = sensor.name
  }
  return map
})

function authHeaders() {
  const token = localStorage.getItem('token')
  return {
    Authorization: token ? `Bearer ${token}` : undefined
  }
}

function parseError(error) {
  if (error.response?.data?.message) return error.response.data.message
  if (error.response?.data?.errors) {
    return Object.values(error.response.data.errors).flat().join(', ')
  }
  return error.message || 'An unexpected error occurred.'
}

function deviceColor(status) {
  const colors = {
    inactive: 'grey',
    active: 'success',
    maintenance: 'warning',
    offline: 'error'
  }
  return colors[status] || 'primary'
}

function formatLocation(location) {
  if (!location) return '—'
  if (typeof location === 'string') return location
  return Object.values(location).filter(Boolean).join(', ') || '—'
}

function formatDate(value) {
  if (!value) return '—'
  return new Date(value).toLocaleString()
}

async function fetchDevices() {
  loading.devices = true
  errors.devices = null
  try {
    const { data } = await axios.get(`${API_BASE}/iot/devices`, { headers: authHeaders() })
    devices.value = data.data || data
  } catch (error) {
    errors.devices = parseError(error)
  } finally {
    loading.devices = false
  }
}

async function createDevice() {
  loading.devices = true
  errors.devices = null
  try {
    const payload = {
      name: forms.device.name,
      device_key: forms.device.device_key,
      device_type: forms.device.device_type,
      status: forms.device.status,
      location: forms.device.location ? { description: forms.device.location } : undefined
    }
    await axios.post(`${API_BASE}/iot/devices`, payload, { headers: authHeaders() })
    Object.assign(forms.device, {
      name: '',
      device_key: '',
      device_type: '',
      status: 'active',
      location: ''
    })
    await fetchDevices()
  } catch (error) {
    errors.devices = parseError(error)
  } finally {
    loading.devices = false
  }
}

async function fetchSensors() {
  loading.sensors = true
  errors.sensors = null
  try {
    const { data } = await axios.get(`${API_BASE}/iot/sensors`, { headers: authHeaders() })
    sensors.value = data.data || data
  } catch (error) {
    errors.sensors = parseError(error)
  } finally {
    loading.sensors = false
  }
}

async function createSensor() {
  if (!forms.sensor.device_id) {
    errors.sensors = 'Select a device before adding a sensor.'
    return
  }

  loading.sensors = true
  errors.sensors = null
  try {
    const payload = {
      ...forms.sensor,
      threshold_min: forms.sensor.threshold_min !== '' ? Number(forms.sensor.threshold_min) : undefined,
      threshold_max: forms.sensor.threshold_max !== '' ? Number(forms.sensor.threshold_max) : undefined
    }
    await axios.post(`${API_BASE}/iot/sensors`, payload, { headers: authHeaders() })
    Object.assign(forms.sensor, {
      device_id: null,
      tag: '',
      name: '',
      unit: '',
      data_type: 'float',
      threshold_min: '',
      threshold_max: ''
    })
    await fetchSensors()
  } catch (error) {
    errors.sensors = parseError(error)
  } finally {
    loading.sensors = false
  }
}

async function fetchReadings() {
  loading.readings = true
  errors.readings = null
  try {
    const { data } = await axios.get(`${API_BASE}/iot/readings`, { headers: authHeaders(), params: { per_page: 15 } })
    readings.value = data.data || data
  } catch (error) {
    errors.readings = parseError(error)
  } finally {
    loading.readings = false
  }
}

async function createReading() {
  if (!forms.reading.sensor_id) {
    errors.readings = 'Select a sensor before logging a reading.'
    return
  }

  loading.readings = true
  errors.readings = null
  try {
    const payload = {
      ...forms.reading,
      value: forms.reading.value !== '' ? Number(forms.reading.value) : undefined
    }
    await axios.post(`${API_BASE}/iot/readings`, payload, { headers: authHeaders() })
    Object.assign(forms.reading, {
      sensor_id: null,
      recorded_at: '',
      value: '',
      quality: 'good'
    })
    await fetchReadings()
  } catch (error) {
    errors.readings = parseError(error)
  } finally {
    loading.readings = false
  }
}

onMounted(async () => {
  await Promise.all([fetchDevices(), fetchSensors(), fetchReadings()])
})
</script>

<style scoped>
.rounded-xl {
  border-radius: 1.5rem !important;
}
</style>

