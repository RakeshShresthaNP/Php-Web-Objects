<template>
	<main class="app">
		<router-view @back="backNavigate"></router-view>
	</main>
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
.app {
    /* Layout & Width */
    @apply w-full md:w-6/12 lg:w-full md:mx-auto;
    
    /* Height & Spacing */
    @apply min-h-screen py-5 pb-20;
    
    /* Responsive Padding (Edges) */
    @apply px-4 sm:px-6 lg:px-8;
    
    /* Aesthetics */
    @apply bg-primary;
	
	@apply sticky top-0;
}
</style>
