<template>
	<HeaderBar>
        <template v-slot:start>
            <section class="flex items-center gap-3">
                <button 
                    @click="toggleMenu"
                    class="text-gray-300 hover:text-white active:scale-95 duration-300"
                >
                    <i class="fa fa-bars text-xl"></i>
                </button>
                <i v-if="useArrow" @click="router.go(-1)" class="active:scale-95 duration-300 fa fa-arrow-left"></i>
                <div>
                    <h1 class="font-semibold text-xl">{{ title }}</h1>
                    <p class="text-sm">{{ sub }}</p>
                </div>
            </section>
        </template>
    </HeaderBar>
    <Sidebar :isOpen="isMenuOpen" @close="closeMenu" />
</template>

<script setup>

import { ref } from 'vue'
import HeaderBar from '@/components/HeaderBar.vue'
import Sidebar from '@/components/Sidebar.vue'
import { useRouter } from 'vue-router'

const props = defineProps({
    title: {
        type: String,
        default: 'Title'
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

const isMenuOpen = ref(false)

const toggleMenu = () => {
	isMenuOpen.value = !isMenuOpen.value
}

const closeMenu = () => {
	isMenuOpen.value = false
}

</script>
