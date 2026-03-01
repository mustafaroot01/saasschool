import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '../stores/auth'
const router = createRouter({
    history: createWebHistory(),
    routes: [
        {
            path: '/login',
            name: 'login',
            component: () => import('../views/Login.vue')
        },
        {
            path: '/',
            component: () => import('../views/DashboardLayout.vue'),
            meta: { requiresAuth: true }, // We will hook this up later
            children: [
                {
                    path: '',
                    name: 'home',
                    component: () => import('../views/Home.vue')
                },
                {
                    path: 'schools',
                    name: 'schools',
                    component: () => import('../views/Schools.vue')
                },
                {
                    path: 'schools/:id',
                    name: 'school-details',
                    component: () => import('../views/SchoolDetails.vue')
                },
                {
                    path: 'plans',
                    name: 'plans',
                    component: () => import('../views/Plans.vue')
                },
                {
                    path: 'subscriptions',
                    name: 'subscriptions',
                    component: () => import('../views/Subscriptions.vue')
                },
                {
                    path: 'settings',
                    name: 'settings',
                    component: () => import('../views/Settings.vue')
                },
                {
                    path: 'audit-logs',
                    name: 'audit-logs',
                    component: () => import('../views/AuditLogs.vue')
                },
                {
                    path: 'error-logs',
                    name: 'error-logs',
                    component: () => import('../views/ErrorLogs.vue')
                },
                {
                    path: 'announcements',
                    name: 'announcements',
                    component: () => import('../views/Announcements.vue')
                },
                {
                    path: 'support-tickets',
                    name: 'support-tickets',
                    component: () => import('../views/SupportTickets.vue')
                },
                {
                    path: 'backups',
                    name: 'global-backups',
                    component: () => import('../views/GlobalBackups.vue')
                },
                {
                    path: 'billing-history',
                    name: 'billing-history',
                    component: () => import('../views/SubscriptionHistory.vue')
                }
            ]
        }
    ]
})

router.beforeEach((to, from, next) => {
    const authStore = useAuthStore()
    if (to.meta.requiresAuth && !authStore.isAuthenticated) {
        next('/login')
    } else if (to.name === 'login' && authStore.isAuthenticated) {
        next('/')
    } else {
        next()
    }
})

export default router
