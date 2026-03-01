<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const router = useRouter()
const authStore = useAuthStore()

const email = ref('admin@saas.com')
const password = ref('password')
const isLoading = ref(false)
const errorMessage = ref('')

const handleLogin = async () => {
    isLoading.value = true
    errorMessage.value = ''

    try {
        await authStore.login(email.value, password.value)
        router.push('/')
    } catch (error) {
        if (error.response?.status === 422) {
            errorMessage.value = error.response.data.message || 'بيانات الدخول غير صحيحة.'
        } else {
            errorMessage.value = 'البيانات غير صحيحة أو هناك مشكلة في الاتصال.'
        }
    } finally {
        isLoading.value = false
    }
}
</script>

<template>
  <div class="min-h-screen flex items-center justify-center bg-[#fcf9f4] relative overflow-hidden font-sans" dir="rtl">
    
    <!-- Abstract Shape (Top Left) -->
    <div class="absolute -top-24 -left-24 w-96 h-96 bg-[#157f7f]/5 rounded-full blur-3xl"></div>
    <!-- Abstract Shape (Bottom Right) -->
    <div class="absolute -bottom-24 -right-24 w-96 h-96 bg-[#e64040]/5 rounded-full blur-3xl"></div>

    <div class="w-full max-w-sm p-4 relative z-10 animate-fadeUp">
      
      <div class="text-center mb-6">
        <h1 class="text-3xl font-extrabold text-[#157f7f] tracking-widest leading-none" style="font-family: 'Times New Roman', serif; font-style: italic;">SAAS</h1>
        <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.4em] mt-3">Super Admin Dashboard</p>
      </div>

      <div class="bg-white p-8 rounded-[32px] shadow-[0_20px_50px_-15px_rgba(0,0,0,0.05)] border border-[#f0e8df]">
        <div class="text-center mb-6">
          <h2 class="text-xl font-black text-slate-800 tracking-tight">الدخول للنظام</h2>
          <p class="text-slate-400 mt-1.5 text-xs font-bold">يرجى إدخال بيانات الاعتماد الخاصة بك</p>
        </div>

        <form @submit.prevent="handleLogin" class="space-y-4">
          <div v-if="errorMessage" class="bg-[#e64040]/5 border border-[#e64040]/10 text-[#e64040] p-3 rounded-xl text-[10px] text-center font-black animate-shake">
            {{ errorMessage }}
          </div>

          <div class="space-y-1.5">
            <label class="block text-[10px] font-black text-slate-500 mr-1 uppercase tracking-widest">البريد الإلكتروني</label>
            <input v-model="email" type="email" required
                class="block w-full px-4 py-3 bg-[#fcf9f4] border border-[#e8ded1] rounded-xl text-slate-800 placeholder-slate-400 focus:ring-2 focus:ring-[#157f7f]/10 focus:border-[#157f7f] transition-all font-bold outline-none text-sm"
                placeholder="admin@saas.com" dir="ltr" />
          </div>

          <div class="space-y-1.5">
            <label class="block text-[10px] font-black text-slate-500 mr-1 uppercase tracking-widest">كلمة المرور</label>
            <input v-model="password" type="password" required
                class="block w-full px-4 py-3 bg-[#fcf9f4] border border-[#e8ded1] rounded-xl text-slate-800 placeholder-slate-400 focus:ring-2 focus:ring-[#157f7f]/10 focus:border-[#157f7f] transition-all font-bold outline-none text-sm"
                placeholder="••••••••" dir="ltr" />
          </div>

          <div class="pt-2">
            <button type="submit" :disabled="isLoading"
              class="w-full flex justify-center py-3.5 px-6 rounded-xl shadow-lg shadow-[#157f7f]/20 text-sm font-black text-white bg-[#157f7f] hover:bg-[#116666] transition-all focus:outline-none disabled:opacity-50">
              <svg v-if="isLoading" class="animate-spin -ml-1 mr-3 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              <span v-if="!isLoading" class="tracking-wide">تسجيل الدخول الآمن</span>
              <span v-else class="tracking-wide">جاري التحقق...</span>
            </button>
          </div>
        </form>
      </div>
      
      <p class="mt-6 text-center text-[9px] font-black text-slate-400 uppercase tracking-[0.2em]">
        &copy; 2026 ORNAMENTAL SAAS SYSTEMS
      </p>
    </div>
  </div>
</template>

<style>
@keyframes fadeUp {
  from { opacity: 0; transform: translateY(20px); }
  to { opacity: 1; transform: translateY(0); }
}
.animate-fadeUp {
  animation: fadeUp 0.6s cubic-bezier(0.16, 1, 0.3, 1);
}

@keyframes shake {
  0%, 100% { transform: translateX(0); }
  25% { transform: translateX(-5px); }
  75% { transform: translateX(5px); }
}
.animate-shake {
  animation: shake 0.4s ease-in-out;
}
</style>
