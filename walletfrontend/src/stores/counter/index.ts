import { defineStore } from 'pinia'

interface CounterState {
  count: number
}

export const useCount = defineStore('counter', {
	state: (): CounterState => {
		return {
			count: 0
		}
	},
	actions: {
		click(): void {
			this.count++
		},
		doubleClick(): void {
			this.count = this.count * 2
		}
	}
})

