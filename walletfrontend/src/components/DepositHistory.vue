<template>
	<main class="mt-3">
		<section v-if="depositHistory.length > 0">
			<section v-for="(item, x) in depositHistory" :key="x">
				<List>
					<template v-slot:start>
						<section class="flex items-center gap-3">
							<img :src="item.thumbnail" width="75" class="bg-secondary p-1 rounded-lg">
							<div>
								<h1 class="font-medium text-gray-300 text-lg">{{ item.title }}</h1>
								<small class="text-gray-400 font-medium">{{ item.description }}</small>
								<small v-if="item.date" class="text-gray-500 text-xs block mt-1">
									{{ formatRelativeTime(item.date) }}
								</small>
							</div>
						</section>
					</template>
					<template v-slot:end>
						<span class="mr-5">
							<i class="fa fa-arrow-right text-xl text-success"></i>
						</span>
					</template>
				</List>
			</section>
		</section>
		<section v-else class="text-center py-8">
			<i class="fas fa-inbox text-4xl text-gray-500 mb-3"></i>
			<p class="text-gray-400">{{ $t('history.noDepositHistory') }}</p>
		</section>
	</main>
</template>

<script setup>

import { computed } from 'vue'
import { useHistory } from '@/stores/history'
import List from '@/components/List.vue'
import { formatRelativeTime } from '@/utils/dateFormatter'

const history = useHistory()
const depositHistory = computed(() => history.deposit)

</script>
