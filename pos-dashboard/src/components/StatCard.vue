<template>
  <v-card class="mx-2 my-2" elevation="3" color="white">
    <v-card-title class="text-h6">
      <v-icon color="primary" class="mr-2">{{ icon }}</v-icon>
      {{ title }}
    </v-card-title>
    <v-card-text class="text-center text-h4 py-3">
      <span>{{ value }}</span>
    </v-card-text>
  </v-card>
</template>

<script setup>
defineProps(['title', 'value', 'icon'])
</script>
