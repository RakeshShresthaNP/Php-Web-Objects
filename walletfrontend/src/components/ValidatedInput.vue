<template>
  <div class="relative w-full mb-3">
    <div class="input-wrapper">
      <input
        :id="name"
        :type="inputType"
        :value="modelValue"
        :placeholder="placeholder"
        :disabled="disabled"
        :class="[
          'text-gray-300 active:ring-1 focus:ring-1 ring-green-500 duration-300 rounded-lg w-full bg-secondary h-full pl-3 pr-12 focus:ring-2 active:ring-2 ring-secondary outline-none',
          hasError ? 'ring-red-500 focus:ring-red-500' : ''
        ]"
        @input="handleInput"
        @blur="handleBlur"
      />
      
      <button
        v-if="showPasswordToggle && type === 'password'"
        type="button"
        @click="togglePassword"
        class="absolute right-2 top-1/2 -translate-y-1/2 bg-green-500 hover:bg-green-600 w-10 h-10 rounded-lg flex items-center justify-center transition-colors z-10"
      >
        <i :class="showPassword ? 'fa-eye' : 'fa-eye-slash'" class="fa text-lg text-black"></i>
      </button>
    </div>

    <small v-if="error" class="mt-1 block text-red-600 font-medium text-sm">
      {{ error }}
    </small>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'

const props = defineProps({
  modelValue: {
    type: [String, Number],
    default: ''
  },
  name: {
    type: String,
    required: true
  },
  type: {
    type: String,
    default: 'text'
  },
  placeholder: {
    type: String,
    default: ''
  },
  error: {
    type: String,
    default: ''
  },
  disabled: {
    type: Boolean,
    default: false
  },
  showPasswordToggle: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['update:modelValue', 'blur', 'validate'])

const showPassword = ref(false)
const inputType = computed(() => {
  if (props.type === 'password' && showPassword.value) {
    return 'text'
  }
  return props.type
})

const hasError = computed(() => !!props.error)

const handleInput = (event) => {
  emit('update:modelValue', event.target.value)
}

const handleBlur = () => {
  emit('blur')
  emit('validate', props.name)
}

const togglePassword = () => {
  showPassword.value = !showPassword.value
}
</script>

<style scoped>
.input-wrapper {
  @apply w-full h-16 flex items-center mb-3 rounded-lg overflow-hidden gap-2;
}
</style>

