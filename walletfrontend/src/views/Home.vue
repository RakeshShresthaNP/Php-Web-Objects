<style scoped>

.title {
	@apply font-semibold text-success text-lg;
}

</style>

<template>
	<main class="pt-20">
		<Header  :useArrow="false" :useTitle="false"  />
		<BalanceCard />
		<section class="mt-12"> 
			<h1 class="title">{{ $t('home.services') }}</h1>
			<Services />
		</section>
		<section class="mt-12">
			<h1 class="title">{{ $t('home.todayPromo') }}</h1>
			<Promo />
		</section>
		<section class="mt-6">
			<h1 class="title">{{ $t('home.depositHistory') }}</h1>
			<DepositHistory />
		</section>
	</main>
</template>	

<script setup>

import Header from '@/components/Header.vue'
import BalanceCard from '@/components/BalanceCard.vue'
import Services from '@/components/Services.vue'
import Promo from '@/components/Promo.vue'
import DepositHistory from '@/components/DepositHistory.vue'

</script>
