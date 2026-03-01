<script setup>
const API_URL = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000'
const APP_DOMAIN = import.meta.env.VITE_APP_DOMAIN || '.localhost'

import { ref, computed, onMounted, watch } from 'vue'
import { CheckCircleIcon, XCircleIcon, CalendarDaysIcon, ViewfinderCircleIcon, DocumentTextIcon } from '@heroicons/vue/24/outline'
import api from '../axios'

const schools = ref([])
const plans = ref([])
const isLoading = ref(true)
const error = ref('')

const fetchAllData = async () => {
  try {
    isLoading.value = true
    const [schoolsRes, plansRes] = await Promise.all([
      api.get('/api/schools'),
      api.get('/api/plans')
    ])
    schools.value = schoolsRes.data
    plans.value = plansRes.data
  } catch (err) {
    error.value = 'تعذّر تحميل بيانات الاشتراكات.'
  } finally {
    isLoading.value = false
  }
}

const PER_PAGE = 30
const currentPage = ref(1)

const totalPages = computed(() => Math.max(1, Math.ceil(schools.value.length / PER_PAGE)))
const paginatedSchools = computed(() => {
  const start = (currentPage.value - 1) * PER_PAGE
  return schools.value.slice(start, start + PER_PAGE)
})
const showingFrom = computed(() => schools.value.length === 0 ? 0 : (currentPage.value - 1) * PER_PAGE + 1)
const showingTo = computed(() => Math.min(currentPage.value * PER_PAGE, schools.value.length))

const getPlanName = (planId) => {
  const plan = plans.value.find(p => p.id === planId)
  return plan ? plan.name : 'غير محدد'
}

// Calculate remaining days
const getRemainingDays = (endDateStr) => {
  if (!endDateStr) return null
  const end = new Date(endDateStr)
  const now = new Date()
  const diffTime = end - now
  return Math.ceil(diffTime / (1000 * 60 * 60 * 24))
}

const getStatusDetails = (school) => {
  if (school.status === 'suspended') {
    return { label: 'معلقة إدارياً', colorClass: 'text-slate-500 bg-slate-100 border-slate-200', icon: XCircleIcon }
  }
  
  const days = getRemainingDays(school.subscription_end_date)
  if (days === null) {
    return { label: 'غير محدد', colorClass: 'text-slate-500 bg-slate-100 border-slate-200', icon: ViewfinderCircleIcon }
  }
  
  if (days < 0) {
    return { label: 'منتهي', colorClass: 'text-[#e64040] bg-[#e64040]/10 border-[#e64040]/20', icon: XCircleIcon }
  } else if (days <= 7) {
    return { label: 'ينتهي قريباً', colorClass: 'text-amber-500 bg-amber-50 border-amber-200', icon: CalendarDaysIcon }
  } else {
    return { label: 'فعال', colorClass: 'text-[#157f7f] bg-[#157f7f]/10 border-[#157f7f]/20', icon: CheckCircleIcon }
  }
}

// Modal State for Renewing
const showRenewModal = ref(false)
const renewingSchool = ref(null)
const manualDate = ref('')
const amount = ref(0)
const selectedPlanId = ref(null)
const paymentStatus = ref('paid')
const isSubmitting = ref(false)

const openRenewModal = (school) => {
  renewingSchool.value = { ...school }
  amount.value = 0
  selectedPlanId.value = school.plan_id
  paymentStatus.value = 'paid'
  manualDate.value = school.subscription_end_date ? school.subscription_end_date.split('T')[0] : new Date().toISOString().split('T')[0]
  showRenewModal.value = true
}

const closeRenewModal = () => {
  showRenewModal.value = false
  renewingSchool.value = null
  amount.value = 0
}

const calculateNewEndDate = computed(() => {
  return manualDate.value
})

// Watch for plan changes to update manualDate automatically
watch(selectedPlanId, (newPlanId) => {
  if (newPlanId && renewingSchool.value) {
    const plan = plans.value.find(p => p.id === newPlanId)
    if (plan) {
      const currentEnd = renewingSchool.value.subscription_end_date ? new Date(renewingSchool.value.subscription_end_date) : new Date()
      const baseDate = currentEnd < new Date() ? new Date() : currentEnd
      const newEnd = new Date(baseDate)
      newEnd.setMonth(newEnd.getMonth() + (plan.duration_months || 12))
      manualDate.value = newEnd.toISOString().split('T')[0]
      amount.value = plan.price // Auto-fill the amount
    }
  } else {
    amount.value = 0 // Reset if no plan selected
  }
})

const submitRenewal = async () => {
  if (!renewingSchool.value) return
  isSubmitting.value = true
  
  try {
    const newDate = manualDate.value
    // Sending the updated data to backend
    await api.post(`/api/subscriptions/${renewingSchool.value.id}/renew`, {
      subscription_end_date: newDate,
      amount: amount.value,
      plan_id: selectedPlanId.value,
      payment_status: paymentStatus.value
    })
    
    // Update locally
    const index = schools.value.findIndex(s => s.id === renewingSchool.value.id)
    if (index !== -1) {
      schools.value[index].subscription_end_date = newDate
      schools.value[index].plan_id = selectedPlanId.value
    }
    
    closeRenewModal()
  } catch (err) {
    alert('حدث خطأ أثناء التجديد')
  } finally {
    isSubmitting.value = false
  }
}

onMounted(() => {
  fetchAllData()
})
</script>

<template>
  <div class="h-full flex flex-col pt-2 animate-fade-in pb-10">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-8">
      <div>
        <h1 class="text-3xl font-black text-slate-800 tracking-tight">إدارة الاشتراكات</h1>
        <p class="text-sm font-bold text-slate-400 mt-2">متابعة وتجديد اشتراكات المدارس في النظام</p>
      </div>
    </div>

    <div v-if="error" class="bg-[#e64040]/10 text-[#e64040] p-4 rounded-2xl mb-6 text-sm font-bold border border-[#e64040]/20 flex items-center gap-3">
      <XCircleIcon class="h-5 w-5" />
      {{ error }}
    </div>

    <!-- Data Grid/Table -->
    <div class="bg-white rounded-[24px] border border-[#f0e8df] shadow-sm overflow-hidden mb-8">
      <div v-if="isLoading" class="flex flex-col items-center justify-center py-20">
        <div class="animate-spin rounded-full h-10 w-10 border-4 border-slate-200 border-t-[#157f7f]"></div>
        <p class="mt-4 text-sm font-bold text-slate-400">جاري تحميل الاشتراكات...</p>
      </div>

      <div v-else-if="schools.length === 0" class="flex flex-col items-center justify-center py-20 text-slate-400">
        <DocumentTextIcon class="h-16 w-16 opacity-20 mb-4" />
        <h3 class="text-lg font-black text-slate-600">لا توجد مدارس مسجلة</h3>
      </div>

      <div v-else class="overflow-x-auto custom-scrollbar">
        <table class="w-full text-right border-collapse">
          <thead>
            <tr class="bg-[#fafaf9] border-b border-[#f0e8df]">
              <th class="px-6 py-4 text-[11px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap">المدرسة</th>
              <th class="px-4 py-4 text-[11px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap">الباقة المشتركة</th>
              <th class="px-4 py-4 text-[11px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap text-center">تاريخ الانتهاء</th>
              <th class="px-4 py-4 text-[11px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap text-center">حالة الاشتراك</th>
              <th class="px-6 py-4 text-[11px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap text-left">التجديد</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-[#f5f0e9]">
            <tr v-for="school in paginatedSchools" :key="school.id" class="group hover:bg-[#fcf9f4]/40 transition-colors">
              
              <!-- School Cell -->
              <td class="px-6 py-5 align-middle">
                <div class="flex items-center gap-3">
                  <div class="h-10 w-10 rounded-xl bg-[#fcf9f4] border border-[#e8ded1] flex items-center justify-center overflow-hidden flex-shrink-0">
                    <img v-if="school.logo" :src="`${API_URL}/storage/${school.logo}`" class="h-full w-full object-cover" />
                    <span v-else class="text-xs font-black text-[#157f7f]">{{ school.name.charAt(0) }}</span>
                  </div>
                  <div class="flex flex-col gap-0.5">
                    <span class="text-xs font-black text-slate-800 truncate max-w-[150px]">{{ school.name }}</span>
                    <span class="text-[9px] text-[#157f7f] font-black font-mono tracking-tighter">{{ school.id.substring(0,8) }}</span>
                  </div>
                </div>
              </td>

              <!-- Plan Cell -->
              <td class="px-4 py-5 align-middle">
                <span class="text-[11px] font-bold text-slate-500 bg-slate-50 border border-slate-200 px-2.5 py-1 rounded-lg truncate block w-fit max-w-[120px]">
                  {{ getPlanName(school.plan_id) }}
                </span>
              </td>

              <!-- End Date Cell -->
              <td class="px-4 py-5 align-middle text-center">
                <span v-if="school.subscription_end_date" class="text-xs font-black text-slate-700 font-mono tracking-tighter">
                  {{ new Date(school.subscription_end_date).toLocaleDateString('en-GB') }}
                </span>
                <span v-else class="text-xs font-bold text-slate-400">غير محدد</span>
                
                <div v-if="school.subscription_end_date" class="mt-1">
                  <span v-if="getRemainingDays(school.subscription_end_date) >= 0" class="text-[10px] text-slate-400 font-bold">باقي {{ getRemainingDays(school.subscription_end_date) }} يوم</span>
                  <span v-else class="text-[10px] text-[#e64040] font-bold">منتهي منذ {{ Math.abs(getRemainingDays(school.subscription_end_date)) }} يوم</span>
                </div>
              </td>

              <!-- Status Cell -->
              <td class="px-4 py-5 align-middle text-center">
                <div class="flex justify-center">
                  <span :class="getStatusDetails(school).colorClass" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full border text-[10px] font-black uppercase tracking-tighter">
                    <component :is="getStatusDetails(school).icon" class="h-3.5 w-3.5" />
                    {{ getStatusDetails(school).label }}
                  </span>
                </div>
              </td>

              <!-- Actions Cell -->
              <td class="px-6 py-5 align-middle text-left">
                <div class="flex items-center justify-end gap-3 opacity-60 group-hover:opacity-100 transition-opacity">
                  <button @click="openRenewModal(school)" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-[#157f7f]/10 text-[#157f7f] hover:bg-[#157f7f] hover:text-white rounded-lg text-xs font-black transition-all">
                    تجديد المدة
                  </button>
                </div>
              </td>

            </tr>
          </tbody>
        </table>
      </div>
      
      <!-- Pagination -->
      <div v-if="!isLoading && schools.length > 0" class="px-6 py-4 border-t border-[#f0e8df] flex items-center justify-between bg-[#fafaf9]/50">
        <span class="text-[11px] font-bold text-slate-500">
          عرض <span class="text-slate-800 font-black">{{ showingFrom }}</span> إلى <span class="text-slate-800 font-black">{{ showingTo }}</span> من أصل <span class="text-slate-800 font-black">{{ schools.length }}</span>
        </span>
        <div class="flex items-center gap-1.5">
          <button @click="currentPage--" :disabled="currentPage === 1" class="h-8 w-8 flex items-center justify-center rounded-lg border border-[#e8ded1] text-slate-400 hover:text-[#157f7f] hover:bg-white disabled:opacity-30 disabled:hover:bg-transparent transition-all">
            <svg class="h-4 w-4 transform rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
          </button>
          <span class="text-xs font-black text-slate-600 px-2 font-mono">{{ currentPage }} / {{ totalPages }}</span>
          <button @click="currentPage++" :disabled="currentPage === totalPages" class="h-8 w-8 flex items-center justify-center rounded-lg border border-[#e8ded1] text-slate-400 hover:text-[#157f7f] hover:bg-white disabled:opacity-30 disabled:hover:bg-transparent transition-all">
            <svg class="h-4 w-4 transform rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
          </button>
        </div>
      </div>
    </div>
    
    <!-- Renew Modal -->
    <div v-if="showRenewModal" class="fixed inset-0 z-[100] overflow-y-auto flex items-center justify-center p-4">
      <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm" @click="closeRenewModal"></div>
      
      <div class="relative bg-white rounded-[24px] shadow-2xl w-full max-w-md transform transition-all border border-[#f0e8df] overflow-hidden">
        <div class="pt-6 px-6 pb-2 border-b border-[#f0e8df] bg-[#fafaf9]">
          <h2 class="text-xl font-black text-slate-800 tracking-tight flex items-center gap-2">
            تجديد الاشتراك
          </h2>
          <p class="text-xs font-bold text-slate-400 mt-1 mb-4">اختر مدة التجديد المناسبة لمدرسة <span class="text-[#157f7f]">{{ renewingSchool?.name }}</span></p>
        </div>

        <div class="p-6">
          <!-- Plan Selection -->
          <div class="mb-6">
            <label class="block text-xs font-black text-slate-700 uppercase tracking-widest mb-2">اختر باقة الاشتراك</label>
            <select v-model="selectedPlanId" class="w-full px-4 py-3 bg-[#fcf9f4] border border-[#e8ded1] rounded-xl text-sm font-bold outline-none focus:border-[#157f7f] transition-all appearance-none cursor-pointer">
              <option :value="null">بدون باقة (تحديد يدوي)</option>
              <option v-for="plan in plans" :key="plan.id" :value="plan.id">{{ plan.name }}</option>
            </select>
          </div>

          <div class="mb-6">
            <label class="block text-xs font-black text-slate-700 uppercase tracking-widest mb-2">تاريخ الانتهاء الجديد (تحديد يدوي)</label>
            <input type="date" v-model="manualDate" class="w-full px-4 py-3 bg-[#fcf9f4] border border-[#e8ded1] rounded-xl text-sm font-mono font-black outline-none focus:border-[#157f7f] transition-all" />
          </div>

          <div class="mb-6">
            <label class="block text-xs font-black text-slate-700 uppercase tracking-widest mb-2">المبلغ المطلوب (دينار عراقي)</label>
            <input type="number" v-model="amount" class="w-full px-4 py-3 bg-[#fcf9f4] border border-[#e8ded1] rounded-xl text-sm font-mono font-black outline-none focus:border-[#157f7f] transition-all" />
          </div>

          <!-- Payment Status -->
          <div class="mb-6">
              <label class="block text-xs font-black text-slate-700 uppercase tracking-widest mb-2">حالة الدفع</label>
              <div class="flex p-1 bg-slate-100 rounded-xl h-[46px]">
                  <button @click="paymentStatus = 'paid'" :class="paymentStatus === 'paid' ? 'bg-[#157f7f] text-white shadow-md' : 'text-slate-500'" class="flex-1 py-1 text-[9px] font-black uppercase rounded-lg transition-all">مدفوع</button>
                  <button @click="paymentStatus = 'unpaid'" :class="paymentStatus === 'unpaid' ? 'bg-[#e64040] text-white shadow-md' : 'text-slate-500'" class="flex-1 py-1 text-[9px] font-black uppercase rounded-lg transition-all">غير مدفوع</button>
              </div>
          </div>


        </div>

        <div class="p-5 bg-[#fafaf9] border-t border-[#f0e8df] flex justify-end gap-3 rounded-b-[24px]">
          <button @click="closeRenewModal" type="button" class="px-5 py-2.5 rounded-xl text-sm font-bold text-slate-500 hover:bg-white hover:shadow-sm border border-transparent hover:border-[#e8ded1] transition-all">
            إلغاء
          </button>
          <button @click="submitRenewal" :disabled="isSubmitting" class="px-5 py-2.5 rounded-xl text-sm font-black text-white bg-[#157f7f] hover:bg-[#0f6060] hover:shadow-lg hover:shadow-[#157f7f]/20 transition-all disabled:opacity-50 flex items-center gap-2">
            <svg v-if="isSubmitting" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
            {{ isSubmitting ? 'جاري التأكيد...' : 'تأكيد التجديد' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
