<style scoped>

.wrapper {
	@apply fixed bottom-0 left-0 right-0 z-50;
}

.bottom-bar {
	@apply bg-secondary flex justify-between text-secondary;
	@apply px-3 py-3 sm:px-4 sm:py-4 lg:p-5;
	gap: 0.5rem;
}

</style>

<template>
	<main v-if="isMainNav" class="wrapper">
		<section class="bottom-bar">
			<template v-for="nav in navigations" :key="nav.id">
				<span
					@click="menuAction(nav)"
					:class="routeName === nav.name ? 'text-green-200' : 'text-gray-500 scale-95'"
					class="flex flex-col items-center active:scale-95 duration-300">
					<i :class="nav.icon" class="text-2xl"></i>
					<i v-if="routeName === nav.name" class="text-green-200 text-xxs fa fa-circle"></i>
				</span>
			</template>
		</section>
	</main>
</template>

<script setup>

import { ref, computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'

const currentNav = ref(1)
const router = useRouter()
const route = useRoute()

const routeName = computed(() => route.name)

const mainNavigations = [
		'Home', 'Marketplace', 'Profile'
]

const isMainNav = computed(() => {
	if ( mainNavigations.includes(routeName.value) ) return true
	else return false
})

const navigations = [
	{
		name: 'Home',
		id: 1,
		to: 'Home',
		icon: 'fa fa-home'
	},
	{
		name: 'Marketplace',
		id: 2,
		to: 'Marketplace',
		icon: 'fa fa-shopping-cart'
	},
	{
		name: 'Profile',
		id: 3,
		to: 'Profile',
		icon: 'fa fa-user'
	}
]

const menuAction = nav => {
	router.push({ name: nav.to })
}

</script>
