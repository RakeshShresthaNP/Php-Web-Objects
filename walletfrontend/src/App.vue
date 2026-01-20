<template>
    <div class="min-h-screen bg-primary">
        <main class="app-container">
            <router-view @back="backNavigate"></router-view>
        </main>
    </div>
</template>

<script setup>

import { useRoute, useRouter } from 'vue-router'
import BottomBar from '@/components/BottomBar.vue'
import { computed } from 'vue'

const route = useRoute()
const router = useRouter()
const routeName = computed(() => route.name)

const backNavigate = () => {
	setTimeout(() => {
		router.go(-1)
	}, 300)
}

</script>

<style scoped>
.app-container {
    /* 1. Width: 100% by default, but capped for readability on large screens */
    @apply w-full max-w-screen-xl mx-auto;

    /* 2. Height: Ensure it fills the viewport and accounts for BottomBar */
    @apply min-h-screen pt-5 pb-24;
    
    /* 3. Responsive Padding: Prevents content from touching screen edges */
    @apply px-4 sm:px-6 md:px-10;

    /* 4. Smooth transitions for responsive changes */
    @apply transition-all duration-300;
}
</style>