import { createRouter, createWebHistory } from 'vue-router'
import BasicConfigPage from '../pages/BasicConfigPage.vue'
import ChannelUrlsPage from '../pages/ChannelUrlsPage.vue'
import GroupConfigPage from '../pages/GroupConfigPage.vue'
import MainConfigPage from '../pages/MainConfigPage.vue'

const routes = [
    {
        path: '/',
        name: 'main',
        component: MainConfigPage
    },
    {
        path: '/basic',
        name: 'basic',
        component: BasicConfigPage
    },
    {
        path: '/urls',
        name: 'urls',
        component: ChannelUrlsPage
    },
    {
        path: '/group',
        name: 'group',
        component: GroupConfigPage
    }
]

const router = createRouter({
    history: createWebHistory(),
    routes
})

// 全局前置守卫
router.beforeEach((to, from, next) => {
    // 确保路由存在
    if (!routes.find(route => route.path === to.path)) {
        next('/')
        return
    }
    next()
})

export default router 