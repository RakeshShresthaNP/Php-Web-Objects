import type { Router } from 'vue-router'

export default (router: Router): void => {
    setTimeout(() => {
        router.go(-1)
    }, 300)
}

