import { defineStore } from 'pinia'
import api from '../axios'

export const useAuthStore = defineStore('auth', {
    state: () => ({
        user: null,
        token: localStorage.getItem('saas_admin_token') || null,
    }),
    getters: {
        isAuthenticated: (state) => !!state.token,
    },
    actions: {
        async login(email, password) {
            try {
                await api.get('/sanctum/csrf-cookie', { withCredentials: true })
                const response = await api.post('/login', {
                    email,
                    password
                }, { withCredentials: true })

                this.token = response.data.token || 'simulated-token-if-cookie-only'
                localStorage.setItem('saas_admin_token', this.token)

                await this.fetchUser()
            } catch (error) {
                throw error
            }
        },

        async fetchUser() {
            if (!this.token) return

            try {
                const response = await api.get('/api/user')
                this.user = response.data
            } catch (error) {
                this.logout()
            }
        },

        logout() {
            this.user = null
            this.token = null
            localStorage.removeItem('saas_admin_token')
        }
    }
})
