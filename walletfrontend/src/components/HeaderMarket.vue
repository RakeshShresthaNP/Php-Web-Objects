<template>
    <HeaderBar>
        <template v-slot:start>
            <section class="flex items-center gap-2 sm:gap-3 flex-1 min-w-0">
                <button 
                    @click="toggleMenu"
                    class="text-gray-300 hover:text-white active:scale-95 duration-300 flex-shrink-0"
                >
                    <i class="fa fa-bars text-lg sm:text-xl"></i>
                </button>
                <div class="min-w-0 flex-1">
                    <h1 class="font-semibold text-base sm:text-xl truncate">{{ $t('marketplace.title') }}</h1>
                    <p class="text-xs sm:text-sm truncate">{{ $t('marketplace.subtitle') }}</p>
                </div>
            </section>
        </template>
        <template v-slot:end>
            <div class="flex items-center gap-2 sm:gap-4 text-base sm:text-lg text-gray-300 flex-shrink-0">
                <!-- Notification Dropdown -->
                <div class="relative">
                    <span 
                        class="relative active:scale-95 duration-300 cursor-pointer" 
                        @click="showNotificationDropdown = !showNotificationDropdown"
                    >
                        <span
                            v-if="amountNotif.length > 0"
                            style="width:20px; height:20px" 
                            class="bg-red-500 text-gray-300 grid place-items-center absolute left-1 -top-3 text-xs rounded-full"
                        >{{ amountNotif.length }}</span>
                        <i class="fa fa-bell"></i>
                    </span>
                    <!-- Notification Dropdown Menu -->
                    <div 
                        v-if="showNotificationDropdown" 
                        class="absolute right-0 top-10 w-72 sm:w-80 max-w-[90vw] bg-secondary rounded-lg shadow-lg border border-gray-700 max-h-96 overflow-y-auto z-50"
                    >
                        <div class="p-4 border-b border-gray-700">
                            <h3 class="font-semibold text-gray-200">{{ $t('notifications.title') }}</h3>
                        </div>
                        <div v-if="amountNotif.length > 0" class="py-2">
                            <div
                                v-for="(notif, index) in amountNotif"
                                :key="index"
                                class="px-4 py-3 hover:bg-gray-700 transition-colors border-b border-gray-800"
                            >
                                <p class="text-sm text-gray-300">{{ notif.title }}</p>
                                <p class="text-xs text-gray-500 mt-1">{{ notif.timestamp }}</p>
                            </div>
                        </div>
                        <div v-else class="px-4 py-8 text-center text-gray-500 text-sm">
                            {{ $t('notifications.noNotifications') || 'No notifications' }}
                        </div>
                        <div v-if="amountNotif.length > 0" class="p-4 border-t border-gray-700">
                            <button 
                                @click="navigate('Notifications')"
                                class="w-full text-green-500 text-sm font-medium hover:text-green-400"
                            >
                                {{ $t('notifications.viewAll') || 'View All' }}
                            </button>
                        </div>
                    </div>
                </div>
                <!-- Cart Icon -->
                <section @click="router.push({ name: 'Carts' })" class="cursor-pointer">
                    <span class="relative">
                        <span 
                            v-if="carts.length > 0"
                            style="width:25px; height:25px" 
                            class="text-xxs bg-red-500 text-gray-300 grid place-items-center absolute left-2 -top-4 text-xs rounded-full"><p>{{ carts.length }}</p></span>
                        <i class="text-lg fa fa-shopping-cart"></i>
                    </span>
                </section>
                <!-- User Profile Dropdown -->
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
            </div>
        </template>
        <template class="w-full" v-slot:bottom>
            <section class="h-20 w-full flex justify-between items-center">
                <template v-for="item in category" :key="item.id">
                    <span :class="currentCategory === item.id ? 'bg-green-600 text-gray-200 font-medium px-3 py-1 rounded-full' : ''" 
                        @click="currentCategory = item.id"
                        class="active:95 duration-300">{{ item.name }}</span>
                 </template>
            </section>
        </template>
    </HeaderBar>
    <Sidebar :isOpen="isMenuOpen" @close="closeMenu" />
    <!-- Overlay to close dropdowns -->
    <div 
        v-if="showNotificationDropdown || showProfileDropdown" 
        @click="showNotificationDropdown = false; showProfileDropdown = false"
        class="fixed inset-0 z-40"
    ></div>
</template>

<script setup>

import { computed, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useHistory } from '@/stores/history'
import { useNotifications } from '@/stores/notifications'
import { useUser } from '@/stores/user'
import HeaderBar from '@/components/HeaderBar.vue'
import Sidebar from '@/components/Sidebar.vue'
import categoryData from '@/contents/category'

const { t } = useI18n()
const router = useRouter()
const history = useHistory()
const notifications = useNotifications()
const user = useUser()
const carts = computed(() => history.carts)
const amountNotif = computed(() => notifications.lists || [])
const fullname = computed(() => user.fullname)

const showNotificationDropdown = ref(false)
const showProfileDropdown = ref(false)
const isMenuOpen = ref(false)
const currentCategory = ref(1)

const toggleMenu = () => {
	isMenuOpen.value = !isMenuOpen.value
}

const closeMenu = () => {
	isMenuOpen.value = false
}

const navigate = path => {
	showNotificationDropdown.value = false
	setTimeout(() => {
		router.push({ name: path })
	}, 300)
}

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

const category = computed(() => categoryData.map(item => ({
  ...item,
  name: item.name === 'all' ? t('marketplace.allCategories') : t(`marketplace.${item.name}`)
})))

</script>

<style scoped>
/* Custom scrollbar for notification dropdown */
div[class*="overflow-y-auto"]::-webkit-scrollbar {
	width: 6px;
}

div[class*="overflow-y-auto"]::-webkit-scrollbar-track {
	background: transparent;
}

div[class*="overflow-y-auto"]::-webkit-scrollbar-thumb {
	background: rgba(255, 255, 255, 0.2);
	border-radius: 3px;
}

div[class*="overflow-y-auto"]::-webkit-scrollbar-thumb:hover {
	background: rgba(255, 255, 255, 0.3);
}
</style>
