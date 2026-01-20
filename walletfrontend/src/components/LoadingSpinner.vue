<template>
  <div v-if="show" class="loading-overlay" :class="{ 'fixed': fullscreen }">
    <div class="loading-spinner">
      <div class="spinner"></div>
      <p v-if="message" class="mt-4 text-gray-300 text-sm">{{ message }}</p>
    </div>
  </div>
</template>

<script setup>
defineProps({
  show: {
    type: Boolean,
    default: false
  },
  message: {
    type: String,
    default: 'Memuat...'
  },
  fullscreen: {
    type: Boolean,
    default: false
  }
})
</script>

<style scoped>
.loading-overlay {
  @apply flex items-center justify-center bg-black bg-opacity-50 z-50;
}

.loading-overlay.fixed {
  @apply fixed inset-0;
}

.loading-spinner {
  @apply flex flex-col items-center justify-center;
}

.spinner {
  @apply border-4 border-gray-300 border-t-green-500 rounded-full;
  width: 50px;
  height: 50px;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}
</style>

