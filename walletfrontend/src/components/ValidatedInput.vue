<template>
  <div class="input-wrapper">
    <input
      :id="name"
      :type="type"
      :value="modelValue"
      :placeholder="placeholder"
      :disabled="disabled"
      :class="[
        'text-gray-300 active:ring-1 focus:ring-1 ring-green-500 duration-300 rounded-lg w-full bg-secondary h-full px-3 focus:ring-2 active:ring-2 ring-secondary',
        hasError ? 'ring-red-500 focus:ring-red-500' : ''
      ]"
      @input="handleInput"
      @blur="handleBlur"
    />
    <span
      v-if="showPasswordToggle && type === 'password'"
      @click="togglePassword"
      class="bg-green-500 rounded-lg w-3/12 h-full grid place-items-center cursor-pointer hover:bg-green-600 transition-colors"
    >
      <i :class="showPassword ? 'fa-eye' : 'fa-eye-slash'" class="fa text-lg"></i>
    </span>
  </div>
  <small v-if="error" class="mt-1 block text-red-600 font-medium text-sm">
    {{ error }}
  </small>
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

