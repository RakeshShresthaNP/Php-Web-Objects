<style scoped>

.title {
	@apply text-lg font-medium mb-4;
}

.list-wrapper {
	@apply w-full mb-5 flex items-center gap-3;
}

.bell-wrapper {
	@apply bg-gray-700 py-2 px-3 rounded;
}

</style>

<template>
	<main class="text-gray-300">
		<Header :title="$t('notifications.title')" :sub="$t('notifications.subtitle')" />
		<!-- Overlay to close dropdown -->
		<div 
			v-if="showProfileDropdown" 
			@click="showProfileDropdown = false"
			class="fixed inset-0 z-40"
		></div>
		<section class="mt-20 text-gray-300">
			<section>
				<h1 class="title">{{ $t('notifications.today') }}</h1>
				<template v-for="card in 3" :key="card">
					<div class="list-wrapper">
						<span class="bell-wrapper">
							<i class="fa fa-bell"></i>
						</span>
						<span class="text-sm">
							<p class="font-semibold text-green-400">{{ $t('notifications.exchangeSuccess') }}</p>
							<p class="text-xs">{{ $t('notifications.exchangeSuccessDesc') }}</p>
						</span>
					</div>
				</template>
			</section>
			<section class="mt-8">
				<h1 class="title">{{ $t('notifications.yesterday') }}</h1>
				<template v-for="card in 8" :key="card">
					<div class="list-wrapper">
						<span class="bell-wrapper">
							<i class="fa fa-bell"></i>
						</span>
						<span class="text-sm">
							<p class="font-semibold text-green-400">{{ $t('notifications.depositSuccess') }}</p>
							<p class="text-xs">{{ $t('notifications.depositSuccessDesc') }}</p>
						</span>
					</div>
				</template>

			</section>
		</section>
	</main>
</template>

<script setup>

import { computed, ref } from 'vue'
import Header from '@/components/Header.vue'
import { useUser } from '@/stores/user'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'

const emits = defineEmits(['back'])
const router = useRouter()
const { t } = useI18n()
const user = useUser()
const showProfileDropdown = ref(false)
const fullname = computed(() => user.fullname)

const navigateToProfile = () => {
	showProfileDropdown.value = false
	setTimeout(() => {
		router.push({ name: 'Profile' })
	}, 300)
}

const handleLogout = () => {
	showProfileDropdown.value = false
	// Add logout logic here if needed
	setTimeout(() => {
		router.push({ name: 'Login' })
	}, 300)
}
</script>
