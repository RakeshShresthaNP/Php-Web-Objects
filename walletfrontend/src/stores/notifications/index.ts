import { defineStore } from 'pinia'
import { i18n } from '@/i18n'

interface Notification {
  title: string
  timestamp: string
}

interface NotificationsState {
  lists: Notification[]
}

export const useNotifications = defineStore('notifications', {
    state(): NotificationsState {
        return {
            lists: [
                {
                title: i18n.global.t('notifications.pointsAdded', { points: 50 }),
                timestamp: '20/03/2022'
                },
                {
                title: i18n.global.t('notifications.pointsAdded', { points: 50 }),
                timestamp: '20/03/2022'
                },
                {
                title: i18n.global.t('notifications.pointsAdded', { points: 50 }),
                timestamp: '20/03/2022'
                }
            ]
        }
    }
})

