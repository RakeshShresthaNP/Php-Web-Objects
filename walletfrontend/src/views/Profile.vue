<template>
	<main class="text-gray-300">
		<Header :useArrow="false" :title="$t('profile.title')" :sub="$t('profile.subtitle')" />
		<section class="mt-20">
			<div class="w-10/12 mx-auto flex flex-col items-center text-gray-300">
				<img class="rounded-full border-2 border-gray-500" width="150" src="/avatar.jpg" />
				<h1 class="font-semibold mt-4 text-xl">{{ fullname }}</h1>
				<p class="text-sm">{{ $t('profile.registeredSince', { date: '20/1/2022' }) }}</p>
				<div class="mt-4">
					<LanguageSwitcher />
				</div>
			</div>
		</section>
		<section class="mt-10 flex justify-between gap-2">
			<template v-for="(item, x) in highlights" :key="x">
				<span class="text-gray-300 text-center text-sm">
					<p>{{ item.title }}</p>
					<p class="font-semibold">{{ item.value }} {{ item.prefix }}</p>
				</span>
			</template>
		</section>
		<section class="mt-16 text-gray-300">
			<template v-for="(item, x) in settings" :key="x">
				<List class="active:scale-95 duration-300" @click="navigate(item.to)">
					<template v-slot:start>
						<span class="text-sm py-2">
							<p class="font-medium text-base">{{ item.name }}</p>
							<p class="text-gray-400">{{ item.description }}</p>
						</span>
					</template>
					<template v-slot:end>
						<i class="fa fa-arrow-right text-green-600 text-lg"></i>
					</template>
				</List>
			</template>
		</section>
	</main>
</template>

<script setup>

import List from '@/components/List.vue'
import Header from '@/components/Header.vue'
import LanguageSwitcher from '@/components/LanguageSwitcher.vue'
import { useUser } from '@/stores/user'
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()
const router = useRouter()
const user = useUser()
const fullname = computed(() => user.fullname)
const highlights = computed(() => user.translatedHighlights)

const settings = computed(() => [
  {
    name: t('profile.settings.username'),
    description: t('profile.settings.usernameDesc'),
    to: ''
  },
  {
    name: t('profile.settings.email'),
    description: t('profile.settings.emailDesc'),
    to: ''
  },
  {
    name: t('profile.settings.password'),
    description: t('profile.settings.passwordDesc'),
    to: ''
  },
  {
    name: t('profile.settings.logout'),
    description: t('profile.settings.logoutDesc'),
    to: 'Login'
  }
])

const navigate = path => {
	setTimeout(() => {
		router.push({ name: path })
	}, 300)
}

</script>
