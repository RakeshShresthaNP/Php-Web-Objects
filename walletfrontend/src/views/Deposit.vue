<style scoped>

.input-wrapper {
	@apply flex flex-col h-20 text-gray-300;
}

input[type=text], select {
	@apply bg-secondary border border-gray-700 text-gray-300 rounded-lg h-full mt-2 text-sm px-2 py-3;
}

</style>
<template>
	<main>
		<Header :title="$t('deposit.title')" :sub="$t('deposit.subtitle')" />
		<section class="text-gray-300 mt-20">
			<h1 class="text-lg font-medium">{{ $t('deposit.form') }}</h1>
			<p class="text-xs">
				<i class="fas fa-lightbulb mr-1"></i>
				{{ $t('deposit.formHint') }}
			</p>
		</section>
		<form class="mt-8 w-full max-w-md md:max-w-lg mx-auto flex flex-wrap justify-between gap-4">
			<div class="input-wrapper w-full sm:w-5/12">
				<label>{{ $t('deposit.wasteKg') }}</label>
				<input type="text" :placeholder="$t('deposit.wasteWeight')" />
			</div>
			<div class="input-wrapper w-full sm:w-5/12">
				<label>{{ $t('deposit.method') }}</label>
				<select>
					<option>{{ $t('deposit.depositMethod') }}</option>
					<option>{{ $t('deposit.selfDelivery') }}</option>
					<option>{{ $t('deposit.pickupService') }}</option>
				</select>
			</div>
			<div class="input-wrapper mt-4 sm:mt-8 mb-3 w-full">
				<label>{{ $t('deposit.pickupLocation') }}</label>
				<select>
					<option>{{ $t('deposit.wasteBankUnit') }}</option>
					<option>{{ $t('deposit.unitHandilBakti') }}</option>
					<option>{{ $t('deposit.unitAlalak') }}</option>
				</select>
			</div>
			<p class="text-xs text-gray-300 w-full">
				<i class="fas fa-lightbulb mr-1"></i>
				{{ $t('deposit.deliveryHint') }}
			</p>
		</form>
		<section class="mt-12">
			<h1 class="font-medium text-gray-300 text-lg">{{ $t('deposit.wasteCategory') }}</h1>
			<TrashCategory />
			<section class="w-full mt-10">
				<button class="bg-green-600 text-gray-300 font-semibold px-3 py-2 w-full rounded-full">{{ $t('deposit.depositButton') }}</button>
			</section>
		</section>
	</main>
</template>
<script setup>

import Header from '@/components/Header.vue'
import TrashCategory from '@/components/TrashCategory.vue'

</script>
