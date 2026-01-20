import { defineStore } from 'pinia'

interface Trend {
  title: string
  percent: number
  prefix: string
  value: number
}

interface BalanceState {
  current: number
  points: number
  trend: Trend
}

export const useBalance = defineStore('balance', {
	state(): BalanceState {
		return {
			current: 1456889,
			points: 1200,
			trend: {
				title: 'up',
				percent: 23.4,
				prefix: '%',
				value: 45000
			}
		}
	}
})

