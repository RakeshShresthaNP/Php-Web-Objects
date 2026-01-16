<template>
	<!-- Overlay -->
	<div 
		v-if="isOpen" 
		@click="closeMenu"
		class="fixed inset-0 bg-black bg-opacity-50 z-40 transition-opacity duration-300"
	></div>
	
	<!-- Sidebar -->
	<aside 
		:class="[
			'fixed top-0 left-0 h-full w-64 bg-secondary z-50 transform transition-transform duration-300 ease-in-out',
			isOpen ? 'translate-x-0' : '-translate-x-full'
		]"
	>
		<div class="flex flex-col h-full">
			<!-- Header -->
			<div class="bg-primary p-5 flex items-center justify-between">
				<div class="flex items-center gap-3">
					<img src="/avatar.jpg" class="rounded-full" width="40" />
					<div class="text-gray-300">
						<p class="font-semibold text-sm">{{ fullname }}</p>
						<small class="text-xs text-gray-400">{{ $t('app.name') }}</small>
					</div>
				</div>
				<button 
					@click="closeMenu"
					class="text-gray-300 hover:text-white active:scale-95 duration-300"
				>
					<i class="fa fa-times text-xl"></i>
				</button>
			</div>
			
			<!-- Menu Items -->
			<nav class="flex-1 overflow-y-auto py-4">
				<div 
					v-for="item in menuItems" 
					:key="item.id"
					@click="navigate(item)"
					:class="[
						'flex items-center gap-4 px-5 py-4 mx-2 mb-1 rounded-lg cursor-pointer transition-colors duration-200',
						isActive(item.name) ? 'bg-green-600 text-gray-200' : 'text-gray-300 hover:bg-gray-700'
					]"
				>
					<i :class="[item.icon, 'text-xl']"></i>
					<span class="font-medium">{{ item.label }}</span>
					<span v-if="item.name === 'Notifications' && notificationCount > 0" class="ml-auto bg-red-500 text-white text-xs px-2 py-1 rounded-full">
						{{ notificationCount }}
					</span>
					<span v-else-if="item.badge" class="ml-auto bg-red-500 text-white text-xs px-2 py-1 rounded-full">
						{{ item.badge }}
					</span>
				</div>
			</nav>
			
			<!-- Footer -->
			<div class="border-t border-gray-700 p-5">
				<button 
					@click="handleLogout"
					class="w-full flex items-center gap-4 px-5 py-3 text-red-400 hover:bg-red-900 hover:bg-opacity-20 rounded-lg transition-colors duration-200"
				>
					<i class="fa fa-sign-out-alt text-xl"></i>
					<span class="font-medium">{{ $t('profile.settings.logout') }}</span>
				</button>
			</div>
		</div>
	</aside>
</template>

<script setup>
import { computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useUser } from '@/stores/user'
import { useNotifications } from '@/stores/notifications'

const props = defineProps({
	isOpen: {
		type: Boolean,
		default: false
	}
})

const emit = defineEmits(['close'])

const router = useRouter()
const route = useRoute()
const { t } = useI18n()
const user = useUser()
const notifications = useNotifications()

const fullname = computed(() => user.fullname)
const notificationCount = computed(() => notifications.lists?.length || 0)

const menuItems = computed(() => [
	{
		id: 1,
		name: 'Home',
		to: 'Home',
		icon: 'fa fa-home',
		label: 'Home'
	},
	{
		id: 2,
		name: 'Marketplace',
		to: 'Marketplace',
		icon: 'fa fa-shopping-cart',
		label: t('marketplace.title')
	},
	{
		id: 3,
		name: 'Profile',
		to: 'Profile',
		icon: 'fa fa-user',
		label: t('profile.title')
	},
	{
		id: 4,
		name: 'Deposit',
		to: 'Deposit',
		icon: 'fa fa-plus-circle',
		label: t('services.deposit')
	},
	{
		id: 5,
		name: 'Transfer',
		to: 'Transfer',
		icon: 'fa fa-exchange-alt',
		label: t('services.transfer')
	},
	{
		id: 6,
		name: 'WithDraw',
		to: 'WithDraw',
		icon: 'fa fa-money-bill-wave',
		label: t('services.withdraw')
	},
	{
		id: 7,
		name: 'ChangePoint',
		to: 'ChangePoint',
		icon: 'fa fa-gift',
		label: t('services.exchangePoints')
	},
	{
		id: 8,
		name: 'Carts',
		to: 'Carts',
		icon: 'fa fa-shopping-bag',
		label: t('cart.title')
	}
])

const isActive = (routeName) => {
	return route.name === routeName
}

const navigate = (item) => {
	closeMenu()
	setTimeout(() => {
		router.push({ name: item.to })
	}, 300)
}

const closeMenu = () => {
	emit('close')
}

const handleLogout = () => {
	closeMenu()
	// Add logout logic here
	// For now, just navigate to login
	setTimeout(() => {
		router.push({ name: 'Login' })
	}, 300)
}
</script>

<style scoped>
/* Custom scrollbar for menu */
nav::-webkit-scrollbar {
	width: 4px;
}

nav::-webkit-scrollbar-track {
	background: transparent;
}

nav::-webkit-scrollbar-thumb {
	background: rgba(255, 255, 255, 0.2);
	border-radius: 2px;
}

nav::-webkit-scrollbar-thumb:hover {
	background: rgba(255, 255, 255, 0.3);
}
</style>
