<style scoped>

.input-wrapper {
    @apply w-full h-16 flex items-center mb-3 rounded-lg overflow-hidden gap-2;
}

input {
    @apply text-gray-300 active:ring-1 focus:ring-1 ring-green-500 duration-300 rounded-lg w-full bg-secondary h-full px-3 focus:ring-2 active:ring-2 ring-secondary;
}

</style>

<template>
    <main>
        <section class="mt-16 text-gray-300 text-center w-10/12 mx-auto">
            <h1 class="font-semibold text-lg">{{ $t('app.name') }}</h1>
            <p class="text-sm">{{ $t('app.tagline') }}</p>
        </section>
            
        <section class="mt-10">
            <form @submit.prevent="login" action="" class="w-full max-w-md mx-auto md:max-w-lg">
                <ValidatedInput
                    v-model="formData.username"
                    name="username"
                    type="text"
                    :placeholder="$t('auth.username')"
                    :error="getFieldError('username')"
                    @blur="handleFieldBlur('username')"
                />
                <ValidatedInput
                    v-model="formData.password"
                    name="password"
                    type="password"
                    :placeholder="$t('auth.password')"
                    :error="getFieldError('password')"
                    :show-password-toggle="true"
                    @blur="handleFieldBlur('password')"
                />
                <section>
                    <button 
                        type="submit"
                        :disabled="isLoad || isSubmitting"
                        class="w-full bg-green-500 font-semibold text-gray-800 py-3 rounded-lg mt-6 disabled:opacity-50 disabled:cursor-not-allowed hover:bg-green-600 transition-colors"
                    >
                        <LoadAction :isLoad="isLoad" :isSuccess="isSuccess" :isFail="isFail" :action="$t('auth.login')" />
                    </button>
                </section>
            </form>
        </section>

        <section class="mt-16 text-gray-300 text-center w-10/12 mx-auto">
                <span>
                    <i class="fas fa-lightbulb text-sm"></i>
                </span>
                <p class="text-sm">
                    {{ $t('auth.noAccount') }}
                </p>
                
                <span>
                    <i class="fas fa-lightbulb text-sm"></i>
                </span>
                <p class="text-sm">
                    {{ $t('auth.forgotPassword') }} <a href="" class="text-blue-500">{{ $t('auth.here') }}</a>
                </p>
        </section>
    </main>
</template>

<script setup>

import { ref } from 'vue'
import { useUser } from '@/stores/user'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import LoadAction from '@/components/LoadAction.vue'
import ValidatedInput from '@/components/ValidatedInput.vue'
import { useFormValidation } from '@/composables/useFormValidation'
import { useToastNotification } from '@/composables/useToast'

const { t } = useI18n()
const user = useUser()
const router = useRouter()
const toast = useToastNotification()

const {
    formData,
    errors,
    isSubmitting,
    validate,
    validateField,
    setFieldValue,
    getFieldError,
    reset
} = useFormValidation('login', {
    username: '',
    password: ''
})

const isLoad = ref(false)
const isSuccess = ref(false)
const isFail = ref(false)

const login = async () => {
    // Validate form first
    const validation = await validate()
    if (!validation.isValid) {
        toast.error(t('auth.formIncomplete'))
        return
    }

    isLoad.value = true
    isFail.value = false
    isSuccess.value = false

    setTimeout(() => {
        if (user.login(formData.value)) {
            isLoad.value = false
            isSuccess.value = true
            toast.success(t('auth.loginSuccess'))
            setTimeout(() => {
                router.push({ name: 'Home' })
            }, 500)
        } else {
            isLoad.value = false
            isFail.value = true
            toast.error(t('auth.loginError'))
        }
    }, 300)
}

const handleFieldBlur = (fieldName) => {
    validateField(fieldName)
}
</script>
