<script setup>
import { ref, computed, onMounted } from 'vue'
import { ClipboardDocumentListIcon, ExclamationTriangleIcon, ListBulletIcon, XMarkIcon } from '@heroicons/vue/24/outline'
import api from '../axios'

const selectedLog = ref(null)
const showDetailsModal = ref(false)

const openDetails = (log) => {
  selectedLog.value = log
  showDetailsModal.value = true
}

const formatValue = (value) => {
   if (value === null || value === undefined) return 'فارغ'
   if (typeof value === 'object') return JSON.stringify(value, null, 2)
   return value
}

const logs = ref([])
const isLoading = ref(true)
const error = ref(null)

const currentPage = ref(1)
const totalPages = ref(1)
const paginationData = ref({})

const filters = ref({
  user_id: '',
  action: '',
  date_from: '',
  date_to: ''
})

const fetchLogs = async (page = 1) => {
  try {
    isLoading.value = true
    error.value = null
    
    const params = new URLSearchParams({ page })
    if (filters.value.user_id) params.append('user_id', filters.value.user_id)
    if (filters.value.action) params.append('action', filters.value.action)
    if (filters.value.date_from) params.append('date_from', filters.value.date_from)
    if (filters.value.date_to) params.append('date_to', filters.value.date_to)

    const response = await api.get(`/api/audit-logs?${params.toString()}`)
    logs.value = response.data.data
    currentPage.value = response.data.current_page
    totalPages.value = response.data.last_page
    paginationData.value = response.data
  } catch (err) {
    error.value = 'تعذّر جلب سجل النشاطات'
  } finally {
    isLoading.value = false
  }
}

const formatDate = (dateString) => {
  const date = new Date(dateString)
  return new Intl.DateTimeFormat('ar-EG', {
    dateStyle: 'short',
    timeStyle: 'medium'
  }).format(date)
}

const formatAction = (action) => {
  // Adds aesthetic badges to actions
  if (action.includes('حذف')) return { text: action, class: 'bg-red-50 text-red-600 border-red-200' }
  if (action.includes('تعديل') || action.includes('تعيين') || action.includes('تجديد')) return { text: action, class: 'bg-amber-50 text-amber-600 border-amber-200' }
  if (action.includes('دخول')) return { text: action, class: 'bg-blue-50 text-blue-600 border-blue-200' }
  return { text: action, class: 'bg-[#157f7f]/10 text-[#157f7f] border-[#157f7f]/20' }
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
        <h1 class="text-3xl font-black text-slate-800 tracking-tight">سجل النشاطات</h1>
        <p class="text-sm font-bold text-slate-400 mt-2">مراقبة كافة عمليات الإدارة والإجراءات الحساسة في النظام.</p>
      </div>
      <div class="p-3 bg-white rounded-xl shadow-sm border border-slate-100 flex items-center gap-3">
        <ClipboardDocumentListIcon class="h-6 w-6 text-[#157f7f]" />
        <span class="text-xs font-black text-slate-600">إجمالي الحركات: <span class="text-[#157f7f]">{{ paginationData.total || 0 }}</span></span>
      </div>
    </div>

    <!-- Filters -->
    <div class="bg-white p-4 rounded-2xl shadow-sm border border-slate-100 mb-6 flex flex-wrap gap-4 items-end">
      <div class="flex-1 min-w-[150px]">
        <label class="block text-xs font-bold text-slate-500 mb-1">نوع الإجراء</label>
        <select v-model="filters.action" class="w-full text-sm border-slate-200 rounded-xl focus:border-[#157f7f] focus:ring-[#157f7f]">
          <option value="">الكل</option>
          <option value="تسجيل دخول">تسجيل دخول</option>
          <option value="إنشاء مدرسة">إنشاء مدرسة</option>
          <option value="تعديل مدرسة">تعديل المدرسة</option>
          <option value="حذف مدرسة">حذف مدرسة</option>
          <option value="تحديث اشتراك">تحديث اشتراك</option>
        </select>
      </div>
      
      <div class="flex-1 min-w-[150px]">
         <label class="block text-xs font-bold text-slate-500 mb-1">من تاريخ</label>
         <input type="date" v-model="filters.date_from" class="w-full text-sm border-slate-200 rounded-xl focus:border-[#157f7f] focus:ring-[#157f7f]" />
      </div>

      <div class="flex-1 min-w-[150px]">
         <label class="block text-xs font-bold text-slate-500 mb-1">إلى تاريخ</label>
         <input type="date" v-model="filters.date_to" class="w-full text-sm border-slate-200 rounded-xl focus:border-[#157f7f] focus:ring-[#157f7f]" />
      </div>

      <div class="flex-none">
        <button @click="fetchLogs(1)" class="bg-[#157f7f] text-white px-6 py-2 rounded-xl text-sm font-bold shadow-sm hover:bg-[#126b6b] transition-colors h-10">
          تطبيق الفرز
        </button>
      </div>
       <div class="flex-none">
        <button @click="() => { filters = { user_id: '', action: '', date_from: '', date_to: '' }; fetchLogs(1); }" class="bg-slate-100 text-slate-600 px-4 py-2 rounded-xl text-sm font-bold hover:bg-slate-200 transition-colors h-10">
          مسح
        </button>
      </div>
    </div>

    <!-- Error State -->
    <div v-if="error" class="bg-[#e64040]/10 text-[#e64040] p-4 rounded-2xl mb-6 text-sm font-bold border border-[#e64040]/20 flex items-center gap-3">
      <ExclamationTriangleIcon class="h-5 w-5" />
      {{ error }}
    </div>

    <!-- Data Grid -->
    <div class="bg-white rounded-[24px] border border-[#f0e8df] shadow-sm overflow-hidden mb-8">
      <div v-if="isLoading" class="flex justify-center items-center py-20">
        <div class="animate-spin rounded-full h-8 w-8 border-3 border-[#fcf9f4] border-t-[#157f7f]"></div>
      </div>

      <div v-else-if="logs.length === 0" class="flex flex-col items-center justify-center py-24 text-slate-400">
        <ClipboardDocumentListIcon class="h-16 w-16 opacity-20 mb-4" />
        <h3 class="text-lg font-black text-slate-600 mb-1">لا توجد سجلات بعد</h3>
        <p class="text-sm font-bold">لم يتم تسجيل أي عمليات إدارية حتى الآن.</p>
      </div>

      <div v-else class="overflow-x-auto custom-scrollbar">
        <table class="w-full text-right border-collapse">
          <thead>
            <tr class="bg-[#fafaf9] border-b border-[#f0e8df]">
              <th class="px-6 py-4 text-[11px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap">التاريخ والوقت</th>
              <th class="px-4 py-4 text-[11px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap">المستخدم</th>
              <th class="px-4 py-4 text-[11px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap text-center">الإجراء</th>
              <th class="px-4 py-4 text-[11px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap text-center">IP & Browser</th>
              <th class="px-4 py-4 text-[11px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap text-center">التفاصيل</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-[#f5f0e9]">
            <tr v-for="log in logs" :key="log.id" class="group hover:bg-[#fcf9f4]/40 transition-colors">
              
              <td class="px-6 py-4 align-middle">
                <span class="text-xs font-black text-slate-700 font-mono" dir="ltr">{{ formatDate(log.created_at) }}</span>
              </td>
              
              <td class="px-4 py-4 align-middle">
                <div class="flex items-center gap-2">
                  <div class="h-8 w-8 rounded-full bg-slate-100 flex items-center justify-center text-[#157f7f] font-black text-xs shadow-sm border border-slate-200">
                    {{ log.user ? log.user.name.charAt(0) : 'S' }}
                  </div>
                  <div class="flex flex-col">
                    <span class="text-xs font-black text-slate-800">{{ log.user ? log.user.name : 'System / Guest' }}</span>
                    <span class="text-[9px] text-slate-400 font-bold font-mono">{{ log.user ? log.user.email : '-' }}</span>
                  </div>
                </div>
              </td>
              
              <td class="px-4 py-4 align-middle text-center">
                <span :class="formatAction(log.action).class" class="inline-flex px-3 py-1.5 rounded-lg border text-[10px] font-black tracking-tight whitespace-nowrap shadow-sm">
                  {{ formatAction(log.action).text }}
                </span>
              </td>

              <td class="px-4 py-4 align-middle text-center">
                <div class="flex flex-col gap-1 items-center">
                   <span class="text-[10px] font-black text-[#157f7f] bg-[#157f7f]/5 px-2 py-0.5 rounded border border-[#157f7f]/10 font-mono tracking-tighter">{{ log.ip_address || 'Unknown' }}</span>
                   <span class="text-[9px] font-bold text-slate-400 truncate max-w-[120px] mix-blend-multiply opacity-50 block" :title="log.user_agent">{{ log.user_agent || '-' }}</span>
                </div>
              </td>
              
              <td class="px-4 py-4 align-middle text-center">
                <button v-if="log.old_values || log.new_values" @click="openDetails(log)" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white border border-slate-200 text-slate-600 rounded-lg text-xs font-bold hover:bg-slate-50 hover:text-[#157f7f] transition-colors shadow-sm focus:outline-none focus:ring-2 focus:ring-[#157f7f]/20">
                  <ListBulletIcon class="h-4 w-4" />
                  عرض
                </button>
                <span v-else class="text-[10px] text-slate-400 font-bold">-</span>
              </td>
              
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div v-if="totalPages > 1 && !isLoading" class="px-6 py-4 border-t border-[#f0e8df] flex items-center justify-between bg-[#fafaf9]/50">
        <span class="text-[11px] font-bold text-slate-500">
          الصفحة <span class="text-slate-800 font-black font-mono mx-1">{{ currentPage }}</span> من <span class="text-slate-800 font-black font-mono mx-1">{{ totalPages }}</span>
        </span>
        <div class="flex items-center gap-1.5">
          <button @click="fetchLogs(currentPage - 1)" :disabled="currentPage === 1" class="h-8 w-8 flex items-center justify-center rounded-lg border border-[#e8ded1] text-slate-400 hover:text-[#157f7f] hover:bg-white disabled:opacity-30 disabled:hover:bg-transparent transition-all">
            <svg class="h-4 w-4 transform rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
          </button>
          <button @click="fetchLogs(currentPage + 1)" :disabled="currentPage === totalPages" class="h-8 w-8 flex items-center justify-center rounded-lg border border-[#e8ded1] text-slate-400 hover:text-[#157f7f] hover:bg-white disabled:opacity-30 disabled:hover:bg-transparent transition-all">
            <svg class="h-4 w-4 transform rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
          </button>
        </div>
      </div>
    </div>

    <!-- Details Modal -->
    <div v-if="showDetailsModal && selectedLog" class="fixed inset-0 z-[100] overflow-y-auto flex items-center justify-center p-4">
      <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" @click="showDetailsModal = false"></div>
      
      <div class="relative bg-white rounded-[24px] shadow-2xl w-full max-w-4xl max-h-[90vh] flex flex-col transform transition-all border border-[#f0e8df] overflow-hidden animate-fade-in-up">
        
        <!-- Modal Header -->
        <div class="px-6 py-5 border-b border-[#f0e8df] bg-[#fafaf9] flex items-center justify-between sticky top-0 z-10">
          <div class="flex items-center gap-3">
            <div class="h-10 w-10 bg-white border border-[#e8ded1] rounded-xl flex items-center justify-center shadow-sm">
              <ClipboardDocumentListIcon class="h-5 w-5 text-[#157f7f]" />
            </div>
            <div>
              <h3 class="text-lg font-black text-slate-800 tracking-tight">تفاصيل الحركة</h3>
              <p class="text-xs font-bold text-slate-500 mt-0.5">{{ selectedLog.description }}</p>
            </div>
          </div>
          <button @click="showDetailsModal = false" class="p-2 text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded-xl transition-colors">
             <XMarkIcon class="h-6 w-6" />
          </button>
        </div>

        <!-- Modal Body -->
        <div class="p-6 overflow-y-auto custom-scrollbar flex-1 space-y-6 bg-slate-50/50">
          
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Old Values -->
            <div class="bg-white rounded-2xl border border-red-100 shadow-sm overflow-hidden flex flex-col">
              <div class="px-4 py-3 bg-red-50/50 border-b border-red-100 flex items-center gap-2">
                <div class="w-2 h-2 rounded-full bg-red-400"></div>
                <h4 class="text-sm font-black text-red-800">القيم القديمة <span class="text-xs font-normal opacity-70">(قبل التعديل)</span></h4>
              </div>
              <div class="p-4 flex-1 overflow-x-auto text-left" dir="ltr">
                <pre v-if="selectedLog.old_values && Object.keys(selectedLog.old_values).length > 0" class="text-xs font-mono text-slate-700 whitespace-pre-wrap">{{ formatValue(selectedLog.old_values) }}</pre>
                <div v-else class="text-xs text-slate-400 text-center py-10 font-bold" dir="rtl">لا توجد بيانات قديمة (أو كان إنشاء جديد)</div>
              </div>
            </div>

            <!-- New Values -->
            <div class="bg-white rounded-2xl border border-[#157f7f]/10 shadow-sm overflow-hidden flex flex-col">
              <div class="px-4 py-3 bg-[#157f7f]/5 border-b border-[#157f7f]/10 flex items-center gap-2">
                <div class="w-2 h-2 rounded-full bg-[#157f7f]"></div>
                <h4 class="text-sm font-black text-[#157f7f]">القيم الجديدة <span class="text-xs font-normal opacity-70">(بعد التعديل)</span></h4>
              </div>
              <div class="p-4 flex-1 overflow-x-auto text-left" dir="ltr">
                <pre v-if="selectedLog.new_values && Object.keys(selectedLog.new_values).length > 0" class="text-xs font-mono text-slate-700 whitespace-pre-wrap">{{ formatValue(selectedLog.new_values) }}</pre>
                <div v-else class="text-xs text-slate-400 text-center py-10 font-bold" dir="rtl">تم حذف العنصر</div>
              </div>
            </div>
          </div>
          
        </div>
        
        <!-- Modal Footer -->
        <div class="p-5 bg-white border-t border-[#f0e8df] flex justify-end">
          <button @click="showDetailsModal = false" class="px-6 py-2.5 bg-slate-100 text-slate-700 font-bold text-sm rounded-xl hover:bg-slate-200 transition-colors">
            إغلاق
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
