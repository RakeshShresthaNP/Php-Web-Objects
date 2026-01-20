import { defineStore } from 'pinia'

interface SessionState {
  login: boolean
}

export const useSession = defineStore('session', {
	state(): SessionState {
		return {
			login: false
		}
	},
	actions: {
		hasLogin(): void {
			this.login = true
		},
		hasLogout(): void {
			this.login = false
		}
	}
})

