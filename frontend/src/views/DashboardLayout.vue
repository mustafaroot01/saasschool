<script setup>
import { useAuthStore } from '../stores/auth'
import { useRouter } from 'vue-router'
import {
  HomeIcon,
  AcademicCapIcon,
  CreditCardIcon,
  CogIcon,
  ArrowRightOnRectangleIcon,
  Bars3Icon,
  BellIcon,
  DocumentTextIcon,
  ClipboardDocumentListIcon,
  BugAntIcon,
  SpeakerWaveIcon,
  TicketIcon,
  CloudArrowDownIcon,
  BanknotesIcon
} from '@heroicons/vue/24/outline'
import { ref } from 'vue'

const authStore = useAuthStore()
const router = useRouter()
const isMobileMenuOpen = ref(false)

const navigation = [
  { name: 'الرئيسية', href: '/', icon: HomeIcon },
  { name: 'المدارس', href: '/schools', icon: AcademicCapIcon },
  { name: 'باقات الاشتراك', href: '/plans', icon: CreditCardIcon },
  { name: 'الاشتراكات والتجديد', href: '/subscriptions', icon: DocumentTextIcon },
  { name: 'سجل الاشتراكات', href: '/billing-history', icon: BanknotesIcon },
  { name: 'النسخ الاحتياطية', href: '/backups', icon: CloudArrowDownIcon },
  { name: 'الإعلانات العامة', href: '/announcements', icon: SpeakerWaveIcon },
  { name: 'تذاكر الدعم', href: '/support-tickets', icon: TicketIcon },
  { name: 'سجل النشاطات', href: '/audit-logs', icon: ClipboardDocumentListIcon },
  { name: 'أخطاء النظام', href: '/error-logs', icon: BugAntIcon },
  { name: 'الإعدادات', href: '/settings', icon: CogIcon },
]

const handleLogout = async () => {
    await authStore.logout()
    router.push('/login')
}
</script>

<template>
  <div class="h-screen flex bg-[#fcf9f4] font-sans text-slate-900" dir="rtl">
    
    <!-- Mobile sidebar -->
    <div v-show="isMobileMenuOpen" class="fixed inset-0 z-50 flex md:hidden" role="dialog" aria-modal="true">
      <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm transition-opacity" aria-hidden="true" @click="isMobileMenuOpen = false"></div>
      
      <div class="relative flex-1 flex flex-col max-w-xs w-full bg-white shadow-2xl transition-transform duration-300">
        <div class="absolute top-0 left-0 -ml-12 pt-2">
          <button type="button" class="ml-1 h-10 w-10 flex items-center justify-center rounded-full focus:outline-none focus:ring-2 focus:ring-white" @click="isMobileMenuOpen = false">
            <span class="sr-only">إغلاق القائمة</span>
            <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
        
        <div class="flex items-center justify-center py-8 border-b border-[#f0e8df]">
          <h1 class="text-3xl font-extrabold text-[#157f7f] tracking-widest" style="font-family: 'Times New Roman', serif; font-style: italic;">SAAS</h1>
        </div>

        <nav class="mt-5 flex-1 px-4 space-y-2 overflow-y-auto custom-scrollbar">
          <router-link v-for="item in navigation" :key="item.name" :to="item.href"
            class="group flex items-center px-4 py-3.5 rounded-2xl text-base font-bold transition-all duration-300"
            :class="[$route.path === item.href ? 'text-white bg-[#157f7f] shadow-lg shadow-[#157f7f]/20' : 'text-slate-500 hover:bg-[#fcf9f4] hover:text-[#157f7f]']">
            <component :is="item.icon" class="ml-4 h-6 w-6" aria-hidden="true" />
            <span>{{ item.name }}</span>
          </router-link>
        </nav>

        <div class="p-4 border-t border-[#f0e8df]">
          <button @click="handleLogout" class="w-full flex items-center px-4 py-3 text-base font-bold rounded-2xl text-[#e64040] hover:bg-[#e64040]/5 transition-colors">
            <ArrowRightOnRectangleIcon class="ml-4 h-6 w-6" />
            تسجيل الخروج
          </button>
        </div>
      </div>
    </div>

    <!-- Desktop sidebar (Sidebar on the right for RTL) -->
    <aside class="hidden md:flex md:flex-col md:w-64 md:fixed md:inset-y-0 md:right-0 bg-white border-l border-[#f0e8df] z-30 shadow-sm">
      <div class="flex flex-col flex-grow pt-6 pb-4 overflow-y-auto">
        <div class="flex items-center justify-center px-6 mb-8">
          <div class="flex flex-col items-center">
            <h1 class="text-3xl font-extrabold text-[#157f7f] tracking-widest leading-none" style="font-family: 'Times New Roman', serif; font-style: italic;">SAAS</h1>
            <span class="text-[9px] font-black text-slate-400 uppercase tracking-[0.3em] mt-1.5">Super Admin</span>
          </div>
        </div>

        <nav class="flex-1 px-3 space-y-1 mt-2">
          <router-link v-for="item in navigation" :key="item.name" :to="item.href"
            class="group flex items-center px-4 py-2.5 rounded-xl text-sm font-bold transition-all duration-300"
            :class="[$route.path === item.href ? 'text-white bg-[#157f7f] shadow-md shadow-[#157f7f]/20' : 'text-slate-500 hover:bg-[#fcf9f4] hover:text-[#157f7f]']">
            <component :is="item.icon" class="ml-3 h-5 w-5" aria-hidden="true" />
            <span>{{ item.name }}</span>
          </router-link>
        </nav>

        <div class="px-3 mt-auto">
          <button @click="handleLogout" class="w-full group flex items-center px-4 py-3 text-sm font-bold rounded-xl text-slate-500 hover:bg-[#e64040]/5 hover:text-[#e64040] transition-all duration-300 border border-transparent hover:border-[#e64040]/10">
            <ArrowRightOnRectangleIcon class="ml-3 h-5 w-5 opacity-60 group-hover:opacity-100" />
            تسجيل الخروج
          </button>
        </div>
      </div>
    </aside>

    <!-- Main content area -->
    <div class="flex flex-col flex-1 md:pr-64">
      <!-- Desktop Header -->
      <header class="bg-white/80 backdrop-blur-md border-b border-[#f0e8df] h-16 sticky top-0 z-20 flex items-center px-6">
        <div class="flex-1 flex items-center justify-between">
          <div class="flex items-center">
            <button type="button" class="md:hidden -ml-2 h-10 w-10 flex items-center justify-center rounded-lg text-slate-500" @click="isMobileMenuOpen = true">
              <Bars3Icon class="h-5 w-5" />
            </button>
            <h2 class="text-lg font-black text-slate-800 tracking-tight">
              {{ navigation.find(n => n.href === $route.path)?.name || 'لوحة التحكم' }}
            </h2>
          </div>

          <div class="flex items-center gap-4">
            <button class="relative bg-[#fcf9f4] p-2 rounded-lg border border-[#f0e8df] text-slate-400 hover:text-[#157f7f] hover:bg-[#f3ece3] transition-all group">
              <span class="absolute top-1.5 left-1.5 flex h-1.5 w-1.5">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-[#e64040] opacity-75"></span>
                <span class="relative inline-flex rounded-full h-1.5 w-1.5 bg-[#e64040]"></span>
              </span>
              <BellIcon class="h-5 w-5 group-hover:animate-wiggle" />
            </button>
            
            <div class="flex items-center gap-3 pr-4 border-r border-[#f0e8df]">
              <div class="text-left leading-tight hidden sm:block">
                <p class="text-xs font-bold text-slate-800">{{ authStore.user?.name || 'Super Admin' }}</p>
                <p class="text-[10px] font-bold text-slate-400 mt-0.5">{{ authStore.user?.email || 'admin@saas.com' }}</p>
              </div>
              <div class="h-8 w-8 rounded-lg bg-[#157f7f] flex items-center justify-center text-white font-black text-[10px] shadow-sm border border-[#157f7f]/10 shadow-[#157f7f]/20">
                AD
              </div>
            </div>
          </div>
        </div>
      </header>

      <main class="flex-1 overflow-y-auto custom-scrollbar">
        <div class="max-w-7xl mx-auto py-8 px-6 lg:px-10">
          <router-view v-slot="{ Component }">
            <transition name="page-fade" mode="out-in">
              <component :is="Component" />
            </transition>
          </router-view>
        </div>
      </main>
    </div>
  </div>
</template>

<style>
.page-fade-enter-active,
.page-fade-leave-active {
  transition: opacity 0.2s ease, transform 0.2s ease;
}
.page-fade-enter-from,
.page-fade-leave-to {
  opacity: 0;
  transform: translateY(10px);
}

.custom-scrollbar::-webkit-scrollbar {
  width: 5px;
}
.custom-scrollbar::-webkit-scrollbar-track {
  background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background-color: #e8ded1;
  border-radius: 10px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
  background-color: #157f7f;
}

@keyframes wiggle {
  0%, 100% { transform: rotate(-3deg); }
  50% { transform: rotate(3deg); }
}
.animate-wiggle {
  animation: wiggle 0.3s ease-in-out infinite;
}
</style>
