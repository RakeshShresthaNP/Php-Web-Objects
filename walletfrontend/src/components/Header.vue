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
                <i v-if="useArrow" @click="router.go(-1)" class="active:scale-95 duration-300 fa fa-arrow-left text-lg sm:text-xl flex-shrink-0"></i>
                <div v-if="title" class="min-w-0 flex-1">
                    <h1 class="font-semibold text-base sm:text-xl truncate">{{ title }}</h1>
                </div>
            </section>
        </template>
        <template v-slot:end>
            <div class="flex items-center gap-4 text-lg text-gray-300">
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

import { ref, computed } from 'vue'
import HeaderBar from '@/components/HeaderBar.vue'
import Sidebar from '@/components/Sidebar.vue'
import { useRouter } from 'vue-router'
import { useNotifications } from '@/stores/notifications'
import { useUser } from '@/stores/user'
import { useI18n } from 'vue-i18n'

const props = defineProps({
    title: {
        type: String,
        default: ''
    },
    sub: {
        type: String,
        default: 'sub'
    },
    useArrow: {
        type: Boolean,
        default: true
    }
})
const router = useRouter()
const { t } = useI18n()
const notifications = useNotifications()
const user = useUser()

const showNotificationDropdown = ref(false)
const showProfileDropdown = ref(false)
const isMenuOpen = ref(false)
const amountNotif = computed(() => notifications.lists || [])
const fullname = computed(() => user.fullname)

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
