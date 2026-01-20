<template>
  <div class="language-switcher">
    <select 
      :value="currentLocale" 
      @change="changeLanguage"
      class="bg-secondary text-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-500 focus:outline-none"
    >
      <option value="en">English</option>
      <option value="id">Bahasa Indonesia</option>
    </select>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'

const { locale } = useI18n()

const currentLocale = computed(() => locale.value)

const changeLanguage = (event) => {
  const newLocale = event.target.value
  locale.value = newLocale
  localStorage.setItem('locale', newLocale)
}
</script>

<style scoped>
.language-switcher {
  display: inline-block;
}
</style>


