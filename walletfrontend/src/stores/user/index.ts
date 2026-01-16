import { defineStore } from 'pinia'
import { i18n } from '@/i18n'

interface Highlight {
  title: string
  value: number
  prefix: string
}

interface UserState {
  fullname: string
  password: string
  username: string
  highlights: Highlight[]
}

interface LoginPayload {
  username: string
  password: string
}

export const useUser = defineStore('user', {
	state(): UserState {
		return {
			fullname: 'John Doe',
			password: 'user1234',
			username: 'user',
			highlights: [
				{
					title: '',
					value: 192,
					prefix: 'Kg'
				},
				{
					title: '',
					value: 123,
					prefix: 'point'
				},
				{
					title: '',
					value: 74,
					prefix: 'produk'
				}
			]
		}
	},
	getters: {
		translatedHighlights(): Highlight[] {
			return [
				{
					title: i18n.global.t('profile.highlights.wasteDeposited'),
					value: 192,
					prefix: 'Kg'
				},
				{
					title: i18n.global.t('profile.highlights.pointsEarned'),
					value: 123,
					prefix: 'point'
				},
				{
					title: i18n.global.t('profile.highlights.productsBought'),
					value: 74,
					prefix: 'produk'
				}
			]
		}
	},
	actions: {
		login(payload: LoginPayload): boolean {
			const { username, password } = payload
			if (username === this.username && password === this.password) return true
			else return false
		}
	}
})

