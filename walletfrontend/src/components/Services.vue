<template>
	 <main class="flex justify-between gap-3 mt-3">
		<template v-for="(service, x) in services" :key="x">
			<IconMenu :source="service" />
		</template>
	</main>
</template>

<script setup>

import IconMenu from '@/components/IconMenu.vue'
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

const services = computed(() => [
	{
		name: t('services.deposit'),
		to: 'Deposit'
	},
	{
		name: t('services.transfer'),
		to: 'Transfer'
	},
	{
		name: t('services.withdraw'),
		to: 'WithDraw'
	},
	{
		name: t('services.exchangePoints'),
		to: 'ChangePoint'
	}
])

</script>
