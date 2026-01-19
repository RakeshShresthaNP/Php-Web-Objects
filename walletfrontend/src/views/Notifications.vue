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
	<div>
		<HeaderBar>
			<template v-slot:start>
				<section class="text-gray-300 flex gap-3 items-center">
					<i @click="emits('back')" class="fa fa-arrow-left text-lg cursor-pointer"></i>
					<div>
						<h1 class="text-xl font-semibold">{{ $t('notifications.title') }}</h1>
						<p class="text-sm">{{ $t('notifications.subtitle') }}</p>
					</div>
				</section>
			</template>
			<template v-slot:end>
				<div class="relative">
					<div 
						class="flex items-center gap-2 sm:gap-3 cursor-pointer" 
						@click="showProfileDropdown = !showProfileDropdown"
					>
						<span>
							<img src="/avatar.jpg" class="rounded-full w-10 h-10 sm:w-12 sm:h-12" />
						</span>
						<div class="text-gray-300 text-right hidden sm:block">
							<p class="font-semibold text-base sm:text-xl">{{ fullname }}</p>
							<small class="font-medium text-xs sm:text-sm">Hay, selamat pagi</small>
						</div>
					</div>
                    <!-- Profile Dropdown Menu -->
                    <div 
                        v-if="showProfileDropdown" 
                        class="absolute right-0 top-12 sm:top-16 w-48 sm:w-56 bg-secondary rounded-lg shadow-lg border border-gray-700 z-50"
                    >
						<div class="py-2">
							<button
								@click="navigateToProfile"
								class="w-full px-4 py-3 text-left text-gray-300 hover:bg-gray-700 transition-colors flex items-center gap-3"
							>
								<i class="fa fa-user text-lg"></i>
								<span class="font-medium">{{ $t('profile.title') }}</span>
							</button>
							<button
								@click="handleLogout"
								class="w-full px-4 py-3 text-left text-red-400 hover:bg-red-900 hover:bg-opacity-20 transition-colors flex items-center gap-3"
							>
								<i class="fa fa-sign-out-alt text-lg"></i>
								<span class="font-medium">{{ $t('profile.settings.logout') }}</span>
							</button>
						</div>
					</div>
				</div>
			</template>
		</HeaderBar>
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
	</div>
</template>

<script setup>

import { computed, ref } from 'vue'
import HeaderBar from '@/components/HeaderBar.vue'
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
