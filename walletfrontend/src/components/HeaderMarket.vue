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
                <div>
                    <h1 class="font-semibold text-xl">{{ $t('marketplace.title') }}</h1>
                    <p class="text-sm">{{ $t('marketplace.subtitle') }}</p>
                </div>
            </section>
        </template>
        <template v-slot:end>
            <section @click="router.push({ name: 'Carts' })">
                <span class="relative">
                    <span style="width:25px; height:25px" 
                    class="text-xxs bg-red-500 text-gray-300 grid place-items-center absolute left-2 -top-4 text-xs rounded-full"><p>{{ carts.length }}</p></span>
                    <i class="text-lg fa fa-shopping-cart"></i>
                </span>
            </section>
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
</template>

<script setup>

import { computed, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useHistory } from '@/stores/history'
import HeaderBar from '@/components/HeaderBar.vue'
import Sidebar from '@/components/Sidebar.vue'
import categoryData from '@/contents/category'

const { t } = useI18n()
const router = useRouter()
const history = useHistory()
const carts = computed(() => history.carts)

const isMenuOpen = ref(false)
const currentCategory = ref(1)

const toggleMenu = () => {
	isMenuOpen.value = !isMenuOpen.value
}

const closeMenu = () => {
	isMenuOpen.value = false
}

const category = computed(() => categoryData.map(item => ({
  ...item,
  name: item.name === 'all' ? t('marketplace.allCategories') : t(`marketplace.${item.name}`)
})))

</script>
