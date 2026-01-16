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
	<HeaderBar>
		<template v-slot:start>
			<section class="text-gray-300 flex gap-3 items-center">
				<i @click="emits('back')" class="fa fa-arrow-left text-lg"></i>
				<div>
					<h1 class="text-xl font-semibold">{{ $t('notifications.title') }}</h1>
					<p class="text-sm">{{ $t('notifications.subtitle') }}</p>
				</div>
			</section>
		</template>
	</HeaderBar>
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
</template>

<script setup>

import HeaderBar from '@/components/HeaderBar.vue'

const emits = defineEmits(['back'])
</script>
