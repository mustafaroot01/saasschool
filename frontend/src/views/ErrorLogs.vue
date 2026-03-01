<script setup>
import { ref, onMounted } from 'vue'
import { BugAntIcon, ExclamationTriangleIcon, BoltIcon } from '@heroicons/vue/24/outline'
import api from '../axios'

const logs = ref('')
const isLoading = ref(true)
const error = ref(null)

const fetchLogs = async () => {
  try {
    isLoading.value = true
    error.value = null
    const response = await api.get('/api/error-logs')
    logs.value = response.data.logs
  } catch (err) {
    error.value = 'تعذّر جلب سجلات الأخطاء. قد لا يوجد ملف log متاح.'
  } finally {
    isLoading.value = false
  }
}

onMounted(() => {
  fetchLogs()
})
</script>

<template>
  <div class="h-full flex flex-col pt-2 animate-fade-in pb-10" dir="rtl">
    
    <!-- Header -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-8">
      <div>
        <h1 class="text-3xl font-black text-slate-800 tracking-tight flex items-center gap-3">
            سجل أخطاء النظام 
            <span class="bg-[#e64040]/10 text-[#e64040] text-xs px-3 py-1 rounded-full border border-[#e64040]/20 font-black">Beta (Developer Mode)</span>
        </h1>
        <p class="text-sm font-bold text-slate-400 mt-2">عرض آخر الأخطاء البرمجية (Exceptions) التي حدثت في الخوادم الخلفية.</p>
      </div>
      <button @click="fetchLogs" class="p-3 bg-white hover:bg-slate-50 rounded-xl shadow-sm border border-slate-200 flex items-center gap-3 transition-colors group">
        <BoltIcon class="h-5 w-5 text-slate-400 group-hover:text-[#157f7f]" />
        <span class="text-xs font-black text-slate-600">تحديث السجل</span>
      </button>
    </div>

    <!-- Error State -->
    <div v-if="error" class="bg-[#e64040]/10 text-[#e64040] p-4 rounded-2xl mb-6 text-sm font-bold border border-[#e64040]/20 flex items-center gap-3">
      <ExclamationTriangleIcon class="h-5 w-5" />
      {{ error }}
    </div>

    <!-- Log Viewer -->
    <div class="bg-[#1e1e1e] rounded-[24px] border border-slate-800 shadow-2xl overflow-hidden flex-1 min-h-[500px] flex flex-col relative">
      <div class="bg-[#2d2d2d] border-b border-[#3d3d3d] p-4 flex items-center gap-3">
        <div class="flex gap-1.5">
            <div class="h-3 w-3 rounded-full bg-[#ff5f56]"></div>
            <div class="h-3 w-3 rounded-full bg-[#ffbd2e]"></div>
            <div class="h-3 w-3 rounded-full bg-[#27c93f]"></div>
        </div>
        <span class="text-xs font-mono text-slate-400 ml-4 font-bold border-r border-[#444] pr-4">storage/logs/laravel.log</span>
        <span class="text-[10px] text-slate-500 font-bold bg-[#1e1e1e] px-2 py-0.5 rounded ml-auto">آخر 500 سطر</span>
      </div>

      <div v-if="isLoading" class="flex-1 flex justify-center items-center">
        <div class="animate-spin rounded-full h-8 w-8 border-3 border-[#2d2d2d] border-t-[#27c93f]"></div>
      </div>
      
      <div v-else-if="!logs" class="flex-1 flex flex-col items-center justify-center text-[#555]">
         <BugAntIcon class="h-16 w-16 mb-4 opacity-50" />
         <p class="font-mono text-sm font-bold">ملف السجل فارغ حالياً</p>
      </div>

      <pre v-else class="flex-1 p-6 text-[11px] font-mono leading-relaxed text-[#a9b7c6] overflow-auto whitespace-pre-wrap word-break" dir="ltr">{{ logs }}</pre>
    </div>

  </div>
</template>

<style scoped>
.word-break {
    word-break: break-all;
}
</style>
