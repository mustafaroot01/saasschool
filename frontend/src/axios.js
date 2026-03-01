import axios from 'axios'

// We don't import the store directly here at the top level to avoid Pinia active instance warnings.
// Instead we inject it dynamically in the interceptor.

const api = axios.create({
    baseURL: '',
    withCredentials: true,
    xsrfCookieName: 'XSRF-TOKEN',
    xsrfHeaderName: 'X-XSRF-TOKEN',
    headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json'
    }
})

api.interceptors.request.use(config => {
    const token = localStorage.getItem('saas_admin_token')
    if (token) {
        config.headers.Authorization = `Bearer ${token}`
    }
    return config
})

export default api
