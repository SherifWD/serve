<template>
  <v-card class="my-4" elevation="3">
    <v-card-title>Sales Overview</v-card-title>
    <v-card-text>
      <apexchart type="line" height="250" :options="options" :series="series"></apexchart>
    </v-card-text>
  </v-card>
</template>

<script setup>
import { ref } from "vue"
const options = ref({
  chart: { id: 'sales', toolbar: { show: false } },
  xaxis: { categories: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] },
  colors: ['#f97233'],
  dataLabels: { enabled: false },
  stroke: { curve: 'smooth' },
  grid: { borderColor: '#E0E0E0' },
})
const series = ref([
  {
    name: 'Sales',
    data: [1500, 1800, 1200, 2200, 2000, 2500, 2300]
  }
])
</script>
