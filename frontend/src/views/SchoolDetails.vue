<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { 
  ArrowRightIcon, 
  CheckCircleIcon, 
  XCircleIcon, 
  TrashIcon, 
  KeyIcon, 
  ArrowPathIcon,
  CreditCardIcon,
  PlayCircleIcon,
  DocumentTextIcon,
  CloudArrowDownIcon,
  CircleStackIcon,
  SwatchIcon,
  CalendarDaysIcon
} from '@heroicons/vue/24/outline'
import api from '../axios'

const route = useRoute()
const router = useRouter()
const schoolId = route.params.id

const school = ref(null)
const plans = ref([])
const isLoading = ref(true)
const error = ref(null)
const isSubmitting = ref(false)
const storageData = ref(null)

const primaryColor = ref('#157f7f')
const secondaryColor = ref('#fafaf9')
const isSavingBranding = ref(false)

const showRenewModal = ref(false)
const manualDate = ref('')
const amount = ref(0)
const selectedPlanId = ref(null)
const paymentStatus = ref('paid')
const subscriptionHistory = ref([])
const newGeneratedPassword = ref(null)

const fetchSchoolData = async () => {
  try {
    isLoading.value = true
    const [schoolRes, plansRes, storageRes, historyRes] = await Promise.all([
      api.get(`/api/schools/${schoolId}`),
      api.get('/api/plans'),
      api.get(`/api/schools/${schoolId}/storage`).catch(() => null),
      api.get(`/api/schools/${schoolId}/subscription-history`).catch(() => ({ data: [] }))
    ])
    school.value = schoolRes.data
    plans.value = plansRes.data
    if (storageRes) storageData.value = storageRes.data
    if (historyRes) subscriptionHistory.value = historyRes.data
    
    // Initialize colors
    primaryColor.value = school.value.primary_color || '#157f7f'
    secondaryColor.value = school.value.secondary_color || '#fafaf9'
    
    // Initialize manual date
    manualDate.value = school.value.subscription_end_date ? school.value.subscription_end_date.split('T')[0] : new Date().toISOString().split('T')[0]
    selectedPlanId.value = school.value.plan_id
    paymentStatus.value = 'paid'
  } catch (err) {
    error.value = 'تعذّر تحميل تفاصيل المدرسة'
  } finally {
    isLoading.value = false
  }
}

const plan = computed(() => {
  if (!school.value || !plans.value.length) return null
  return plans.value.find(p => p.id === school.value.plan_id)
})

const getRemainingDays = (endDateStr) => {
  if (!endDateStr) return null
  const end = new Date(endDateStr)
  const now = new Date()
  const diffTime = end - now
  return Math.ceil(diffTime / (1000 * 60 * 60 * 24))
}

const toggleStatus = async () => {
  if (!school.value) return
  const newStatus = school.value.status === 'active' ? 'suspended' : 'active'
  try {
    isSubmitting.value = true
    await api.put(`/api/schools/${school.value.id}`, { status: newStatus })
    school.value.status = newStatus
  } catch {
    alert('تعذّر تغيير حالة المدرسة')
  } finally {
    isSubmitting.value = false
  }
}

const calculateNewEndDate = computed(() => {
  return manualDate.value
})

// Update manualDate when plan changes
watch(selectedPlanId, (newPlanId) => {
  if (newPlanId && school.value && plans.value.length > 0) {
    const plan = plans.value.find(p => p.id === newPlanId)
    if (plan) {
      const currentEnd = school.value.subscription_end_date ? new Date(school.value.subscription_end_date) : new Date()
      const baseDate = currentEnd < new Date() ? new Date() : currentEnd
      const newEnd = new Date(baseDate)
      newEnd.setMonth(newEnd.getMonth() + (plan.duration_months || 12))
      manualDate.value = newEnd.toISOString().split('T')[0]
    }
  }
})

const submitRenewal = async () => {
  if (!school.value) return
  isSubmitting.value = true
  try {
    const newDate = manualDate.value
    await api.post(`/api/subscriptions/${school.value.id}/renew`, {
      subscription_end_date: newDate,
      amount: amount.value,
      plan_id: selectedPlanId.value,
      payment_status: paymentStatus.value
    })
    school.value.subscription_end_date = newDate
    school.value.plan_id = selectedPlanId.value
    showRenewModal.value = false
    amount.value = 0
    
    // Refresh history
    const historyRes = await api.get(`/api/schools/${schoolId}/subscription-history`)
    subscriptionHistory.value = historyRes.data
  } catch (err) {
    alert('حدث خطأ أثناء التجديد')
  } finally {
    isSubmitting.value = false
  }
}

const resetPassword = async () => {
  if (!confirm('سيتم إنشاء كلمة مرور جديدة لمدير هذه المدرسة وعرضها لك. هل أنت متأكد؟')) return
  isSubmitting.value = true
  try {
    const response = await api.post(`/api/schools/${school.value.id}/reset-password`)
    newGeneratedPassword.value = response.data.password
  } catch {
    alert('فشل في إعادة تعيين كلمة المرور. قد يكون هناك مشكلة في الاتصال.')
  } finally {
    isSubmitting.value = false
  }
}

const impersonate = async () => {
  try {
    const response = await api.post(`/api/schools/${school.value.id}/impersonate`)
    if(response.data.url) {
        window.open(response.data.url, '_blank')
    }
  } catch {
    alert('ميزة تسجيل الدخول كمدير غير متوفرة بعد أو حدث خطأ.')
  }
}

const saveBranding = async () => {
    isSavingBranding.value = true
    try {
        await api.put(`/api/schools/${school.value.id}`, {
            primary_color: primaryColor.value,
            secondary_color: secondaryColor.value
        })
        school.value.primary_color = primaryColor.value
        school.value.secondary_color = secondaryColor.value
        alert('تم حفظ إعدادات الهوية البصرية بنجاح')
    } catch {
        alert('حدث خطأ أثناء حفظ الإعدادات')
    } finally {
        isSavingBranding.value = false
    }
}

onMounted(() => {
  fetchSchoolData()
})
</script>

<template>
  <div class="h-full flex flex-col pt-2 animate-fade-in pb-10" dir="rtl">
    
    <!-- Top Action Bar -->
    <div class="flex items-center gap-4 mb-8">
      <router-link to="/schools" class="h-10 w-10 flex flex-shrink-0 items-center justify-center bg-white rounded-xl border border-[#f0e8df] text-slate-400 hover:text-[#157f7f] shadow-sm transition-all hover:scale-105 active:scale-95">
        <ArrowRightIcon class="h-5 w-5" />
      </router-link>
      <div>
        <h1 class="text-3xl font-black text-slate-800 tracking-tight flex items-center gap-3">
          تفاصيل المدرسة
          <span v-if="isLoading" class="animate-pulse bg-slate-200 h-6 w-32 rounded-xl"></span>
          <span v-else class="text-[#157f7f]">{{ school?.name }}</span>
        </h1>
        <p class="text-sm font-bold text-slate-400 mt-2">عرض وإدارة الحساب والاشتراك والصلاحيات</p>
      </div>
    </div>

    <div v-if="isLoading" class="flex justify-center items-center py-20 bg-white rounded-3xl border border-[#f0e8df] shadow-sm">
      <div class="animate-spin rounded-full h-8 w-8 border-3 border-[#fcf9f4] border-t-[#157f7f]"></div>
    </div>

    <div v-else-if="error" class="bg-[#e64040]/10 text-[#e64040] p-6 rounded-2xl font-bold border border-[#e64040]/20 flex items-center gap-3">
      <XCircleIcon class="h-6 w-6" />
      {{ error }}
    </div>

    <div v-else-if="school" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      
      <!-- Right Column: Info -->
      <div class="lg:col-span-2 flex flex-col gap-6">
        
        <!-- General Info Card -->
        <div class="bg-white rounded-[24px] border border-[#f0e8df] shadow-sm overflow-hidden p-6 relative">
          <div class="flex items-start gap-4">
             <div class="h-20 w-20 rounded-2xl bg-[#fcf9f4] border border-[#e8ded1] flex items-center justify-center overflow-hidden flex-shrink-0 shadow-sm p-1">
                <img v-if="school.logo" :src="`${import.meta.env.VITE_API_BASE_URL || '${import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000'}'}/storage/${school.logo}`" class="h-full w-full object-cover rounded-xl" />
                <span v-else class="text-2xl font-black text-[#157f7f]">{{ school.name.charAt(0) }}</span>
             </div>
             <div class="flex-1">
                <div class="flex items-center justify-between mb-1">
                   <h2 class="text-xl font-black text-slate-800">{{ school.name }}</h2>
                   <span :class="school.status === 'active' ? 'bg-[#157f7f]/10 text-[#157f7f]' : 'bg-slate-100 text-slate-500'" class="px-3 py-1 rounded-full text-xs font-black uppercase tracking-widest flex items-center gap-1.5">
                     <span :class="school.status === 'active' ? 'bg-[#157f7f]' : 'bg-slate-400'" class="h-1.5 w-1.5 rounded-full"></span>
                     {{ school.status === 'active' ? 'نشط' : 'متوقف' }}
                   </span>
                </div>
                <p class="text-xs font-mono text-slate-400 font-bold mb-4">{{ school.id }}</p>
                
                <div class="grid grid-cols-2 gap-4">
                   <div class="bg-slate-50 p-3 rounded-xl border border-slate-100">
                     <span class="block text-[10px] text-slate-400 font-black uppercase mb-1">قاعدة البيانات (DB)</span>
                     <span class="text-xs font-mono font-bold text-slate-700">{{ school.tenancy_db_name }}</span>
                   </div>
                   <div class="bg-slate-50 p-3 rounded-xl border border-slate-100">
                     <span class="block text-[10px] text-slate-400 font-black uppercase mb-1">تاريخ الإنشاء</span>
                     <span class="text-xs font-bold text-slate-700">{{ new Date(school.created_at).toLocaleDateString('ar-EG') }}</span>
                   </div>
                   <div class="bg-slate-50 p-3 rounded-xl border border-slate-100 col-span-2">
                     <span class="block text-[10px] text-slate-400 font-black uppercase mb-1">بيانات التواصل</span>
                     <div class="flex flex-col gap-1 mt-1">
                       <span v-if="school.contact_email" class="text-xs font-bold text-slate-700 break-all"><span class="opacity-50 mx-1">البريد:</span>{{ school.contact_email }}</span>
                       <span v-if="school.contact_phone" class="text-xs font-bold text-slate-700"><span class="opacity-50 mx-1">رقم الهاتف:</span>{{ school.contact_phone }}</span>
                       <span v-if="!school.contact_email && !school.contact_phone" class="text-[10px] text-slate-400 font-bold">لم يتم إدخال بيانات تواصل.</span>
                     </div>
                   </div>
                   <div class="bg-slate-50 p-3 rounded-xl border border-slate-100 col-span-2">
                     <span class="block text-[10px] text-slate-400 font-black uppercase mb-1">النطاقات (Domains)</span>
                     <div class="flex flex-wrap gap-2 mt-1">
                       <span v-for="domain in school.domains" :key="domain.id" class="px-2 py-1 bg-white border border-slate-200 rounded-lg text-xs font-mono font-bold text-[#157f7f]">
                         {{ domain.domain }}
                       </span>
                     </div>
                   </div>
                </div>
             </div>
          </div>
        </div>

        <!-- Subscription Info Card -->
        <div class="bg-white rounded-[24px] border border-[#f0e8df] shadow-sm overflow-hidden p-6 relative">
          <h3 class="text-lg font-black text-slate-800 mb-5 flex items-center gap-2">
            <CreditCardIcon class="h-5 w-5 text-[#157f7f]" />
            تفاصيل الباقة الحالية
          </h3>
          
          <div v-if="plan" class="space-y-5">
            <div class="flex items-center justify-between p-4 bg-[#fcf9f4] border border-[#e8ded1] rounded-2xl">
              <div>
                <span class="block text-[10px] text-slate-400 font-black uppercase mb-0.5">الباقة المشتركة</span>
                <span class="text-lg font-black text-[#157f7f]">{{ plan.name }}</span>
              </div>
              <div class="text-left">
                <span class="block text-[10px] text-slate-400 font-black uppercase mb-0.5">الأيام المتبقية</span>
                <span v-if="getRemainingDays(school.subscription_end_date) >= 0" class="text-lg font-black text-slate-800">{{ getRemainingDays(school.subscription_end_date) }} يوم</span>
                <span v-else class="text-lg font-black text-[#e64040]">منتهي الصلاحية</span>
              </div>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
              <div class="p-3 border border-slate-100 rounded-xl text-center">
                <span class="block text-xl font-black text-slate-700">{{ plan.max_students }}</span>
                <span class="text-[10px] font-bold text-slate-400 uppercase">الطلاب</span>
              </div>
              <div class="p-3 border border-slate-100 rounded-xl text-center">
                <span class="block text-xl font-black text-slate-700">{{ plan.max_teachers }}</span>
                <span class="text-[10px] font-bold text-slate-400 uppercase">المعلمين</span>
              </div>
              <div class="p-3 border border-slate-100 rounded-xl text-center">
                <span class="block text-xl font-black text-slate-700">{{ plan.storage_limit_mb ? (plan.storage_limit_mb / 1024).toFixed(0) : 0 }} <span class="text-xs">GB</span></span>
                <span class="text-[10px] font-bold text-slate-400 uppercase">مساحة التخزين</span>
              </div>
              <div class="p-3 border border-slate-100 rounded-xl text-center">
                 <span class="block text-[11px] font-black text-slate-700 mt-2">{{ school.subscription_end_date ? new Date(school.subscription_end_date).toLocaleDateString('en-GB') : 'غير محدد' }}</span>
                <span class="text-[10px] font-bold text-slate-400 uppercase">تاريخ الانتهاء</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Branding Card (White-labeling) -->
        <div class="bg-white rounded-[24px] border border-[#f0e8df] shadow-sm overflow-hidden p-6 relative">
          <div class="flex items-center justify-between mb-5">
              <h3 class="text-lg font-black text-slate-800 flex items-center gap-2">
                <SwatchIcon class="h-5 w-5 text-[#157f7f]" />
                تخصيص الهوية البصرية (Branding)
              </h3>
              <button @click="saveBranding" :disabled="isSavingBranding" class="px-4 py-2 bg-slate-800 text-white text-xs font-black rounded-xl hover:bg-slate-900 transition-colors disabled:opacity-50">
                  {{ isSavingBranding ? 'جاري الحفظ...' : 'حفظ التعديلات' }}
              </button>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div class="space-y-2">
                  <label class="block text-xs font-black text-slate-500 uppercase tracking-wider">اللون الأساسي (Primary Color)</label>
                  <div class="flex items-center gap-3">
                      <input type="color" v-model="primaryColor" class="h-12 w-20 rounded-xl border-none p-1 cursor-pointer bg-slate-50 shadow-inner" />
                      <input type="text" v-model="primaryColor" class="flex-1 bg-slate-50 border-slate-200 text-slate-700 text-sm font-mono font-bold rounded-xl p-3" />
                  </div>
                  <p class="text-[10px] text-slate-400 font-bold">يستخدم في الأزرار، القوائم، والعناصر النشطة.</p>
              </div>

              <div class="space-y-2">
                  <label class="block text-xs font-black text-slate-500 uppercase tracking-wider">اللون الثانوي (Secondary/BG)</label>
                  <div class="flex items-center gap-3">
                      <input type="color" v-model="secondaryColor" class="h-12 w-20 rounded-xl border-none p-1 cursor-pointer bg-slate-50 shadow-inner" />
                      <input type="text" v-model="secondaryColor" class="flex-1 bg-slate-50 border-slate-200 text-slate-700 text-sm font-mono font-bold rounded-xl p-3" />
                  </div>
                  <p class="text-[10px] text-slate-400 font-bold">يستخدم في الخلفيات واللمسات الجمالية.</p>
              </div>
          </div>

          <!-- Preview -->
          <div class="mt-8 p-6 rounded-2xl border border-dashed border-slate-200 bg-slate-50/30">
              <span class="block text-[10px] text-slate-400 font-black uppercase mb-4 text-center tracking-widest">معاينة مباشرة (Preview)</span>
              <div class="flex flex-col items-center gap-4">
                  <div :style="{ backgroundColor: secondaryColor }" class="w-full p-8 rounded-2xl border shadow-sm flex flex-col items-center gap-4 transition-colors duration-300">
                      <div :style="{ backgroundColor: primaryColor }" class="h-12 w-48 rounded-xl shadow-lg shadow-black/5 flex items-center justify-center text-white font-black text-sm tracking-tight transition-colors duration-300">
                          نظام مدرسة {{ school?.name }}
                      </div>
                      <div class="flex gap-2">
                          <div :style="{ color: primaryColor }" class="h-8 w-8 rounded-lg bg-white shadow-sm flex items-center justify-center transition-colors duration-300">
                             <SwatchIcon class="h-4 w-4" />
                          </div>
                          <div class="h-8 w-32 rounded-lg bg-white shadow-sm transition-colors duration-300"></div>
                      </div>
                  </div>
              </div>
          </div>
        </div>

      </div>

      <!-- Left Column: Actions & Stats -->
      <div class="flex flex-col gap-4">
        
        <!-- Storage Usage Widget -->
        <div v-if="storageData" class="bg-white rounded-[24px] border border-[#f0e8df] shadow-sm overflow-hidden p-6 relative">
           <h3 class="text-sm font-black text-slate-800 mb-4 flex items-center justify-between">
              استهلاك البيانات (DB)
              <span class="text-xs font-bold text-slate-400 bg-slate-50 px-2 py-1 rounded-lg border border-slate-100">{{ storageData.used_mb }} MB</span>
           </h3>
           <div class="relative w-full h-3 bg-slate-100 rounded-full overflow-hidden mb-2">
              <div class="absolute top-0 left-0 h-full rounded-full transition-all duration-1000"
                   :class="storageData.percentage > 90 ? 'bg-[#e64040]' : storageData.percentage > 70 ? 'bg-amber-500' : 'bg-[#157f7f]'"
                   :style="`width: ${storageData.percentage}%`"></div>
           </div>
           <div class="flex justify-between items-center mt-1">
              <span class="text-[10px] font-black text-slate-400">{{ storageData.percentage }}% مستخدم</span>
              <span class="text-[10px] font-black text-slate-500">{{ (storageData.limit_mb * 1024).toFixed(0) }} MB الحد الأقصى</span>
           </div>
        </div>



        <!-- Administrative Actions -->
        <div class="bg-white rounded-[24px] border border-[#f0e8df] shadow-sm overflow-hidden p-6">
          <h3 class="text-sm font-black text-slate-800 mb-4 uppercase tracking-widest">إجراءات إدارية</h3>
          
          <div class="space-y-3">
            <button @click="impersonate" class="w-full flex items-center justify-between p-4 bg-slate-50 hover:bg-white hover:shadow-md border border-slate-200 hover:border-[#157f7f]/30 rounded-2xl transition-all group">
              <div class="flex items-center gap-3">
                <div class="h-8 w-8 rounded-lg bg-[#157f7f]/10 flex items-center justify-center text-[#157f7f]">
                  <PlayCircleIcon class="h-5 w-5" />
                </div>
                <span class="text-sm font-black text-slate-700 group-hover:text-[#157f7f] transition-colors">دخول كمدير مدرسة</span>
              </div>
              <ArrowRightIcon class="h-4 w-4 text-slate-400 transform -rotate-45" />
            </button>

            <button @click="showRenewModal = true" class="w-full flex items-center justify-between p-4 bg-slate-50 hover:bg-white hover:shadow-md border border-slate-200 hover:border-amber-500/30 rounded-2xl transition-all group">
              <div class="flex items-center gap-3">
                <div class="h-8 w-8 rounded-lg bg-amber-50 flex items-center justify-center text-amber-500">
                  <ArrowPathIcon class="h-5 w-5" />
                </div>
                <span class="text-sm font-black text-slate-700 group-hover:text-amber-600 transition-colors">تجديد الاشتراك</span>
              </div>
            </button>

            <button @click="resetPassword" class="w-full flex items-center justify-between p-4 bg-slate-50 hover:bg-white hover:shadow-md border border-slate-200 hover:border-blue-500/30 rounded-2xl transition-all group">
              <div class="flex items-center gap-3">
                <div class="h-8 w-8 rounded-lg bg-blue-50 flex items-center justify-center text-blue-500">
                  <KeyIcon class="h-5 w-5" />
                </div>
                <span class="text-sm font-black text-slate-700 group-hover:text-blue-600 transition-colors">إعادة تعيين كلمة المرور</span>
              </div>
            </button>

            <button @click="toggleStatus" class="w-full flex items-center justify-between p-4 bg-slate-50 hover:bg-white hover:shadow-md border border-slate-200 hover:border-slate-400 rounded-2xl transition-all group">
              <div class="flex items-center gap-3">
                <div :class="school.status === 'active' ? 'bg-slate-200 text-slate-500' : 'bg-[#157f7f]/10 text-[#157f7f]'" class="h-8 w-8 rounded-lg flex items-center justify-center">
                  <component :is="school.status === 'active' ? XCircleIcon : CheckCircleIcon" class="h-5 w-5" />
                </div>
                <span class="text-sm font-black text-slate-700">{{ school.status === 'active' ? 'تعليق المدرسة مؤقتاً' : 'تفعيل المدرسة' }}</span>
              </div>
            </button>

            <!-- Display new password inline if generated -->
            <div v-if="newGeneratedPassword" class="mt-4 p-4 bg-green-50 border border-green-200 rounded-2xl animate-fade-in-up">
              <p class="text-[10px] font-black tracking-widest text-green-600 uppercase mb-1">كلمة المرور الجديدة</p>
              <div class="text-lg font-mono font-black text-slate-800 text-center py-2 select-all bg-white rounded-xl border border-green-100 shadow-sm">
                {{ newGeneratedPassword }}
              </div>
            </div>
            
          </div>
        </div>

        <!-- Subscription History Log -->
        <div class="bg-white rounded-[24px] border border-[#f0e8df] shadow-sm overflow-hidden p-6 mt-4">
          <h3 class="text-sm font-black text-slate-800 mb-4 uppercase tracking-widest flex items-center gap-2">
            <DocumentTextIcon class="h-4 w-4 text-[#157f7f]" />
            سجل تاريخ الاشتراكات
          </h3>
          
          <div v-if="subscriptionHistory.length === 0" class="py-10 text-center">
            <p class="text-[10px] font-bold text-slate-400">لا يوجد سجل اشتراكات حتى الآن لهذه المدرسة.</p>
          </div>
          
          <div v-else class="space-y-4">
            <div v-for="entry in subscriptionHistory" :key="entry.id" class="p-4 bg-slate-50 rounded-2xl border border-slate-100 flex items-center justify-between group hover:bg-white hover:shadow-md transition-all">
              <div class="flex items-center gap-4">
                <div class="h-8 w-8 rounded-lg bg-white border border-slate-200 flex items-center justify-center text-[#157f7f]">
                   <CalendarDaysIcon v-if="entry.id" class="h-4 w-4" />
                </div>
                <div>
                   <div class="flex items-center gap-2">
                     <span class="text-[10px] font-black text-slate-800 uppercase tracking-widest">تاريخ الانتهاء:</span>
                     <span class="text-xs font-mono font-black text-[#157f7f]">{{ entry.end_date ? new Date(entry.end_date).toLocaleDateString('en-GB') : 'N/A' }}</span>
                   </div>
                   <div class="text-[9px] font-bold text-slate-400 mt-1 uppercase tracking-tighter">
                     تم التجديد في: {{ new Date(entry.created_at).toLocaleString() }}
                   </div>
                </div>
              </div>
              <div class="flex flex-col items-end">
                  <span :class="entry.payment_status === 'paid' ? 'bg-[#157f7f]/10 text-[#157f7f] border-[#157f7f]/10' : 'bg-[#e64040]/10 text-[#e64040] border-[#e64040]/10'" class="px-2 py-0.5 rounded-full text-[8px] font-black uppercase tracking-widest border mb-1">
                    {{ entry.payment_status === 'paid' ? 'مدفوع' : 'غير مدفوع' }}
                  </span>
                  <span class="text-[9px] font-bold text-slate-500 bg-white px-2 py-0.5 rounded-lg border border-slate-200">
                    ID: {{ entry.id }}
                  </span>
                  <span class="text-[10px] font-black text-slate-800 uppercase tracking-widest mt-1">
                    {{ entry.amount > 0 ? new Intl.NumberFormat('en-US').format(entry.amount) + ' IQD' : 'تحديد يدوي' }}
                  </span>
              </div>
            </div>
          </div>
        </div>
      </div>
      
    </div>

    <!-- Renew Modal -->
    <div v-if="showRenewModal" class="fixed inset-0 z-[100] overflow-y-auto flex items-center justify-center p-4">
      <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm" @click="showRenewModal = false"></div>
      
      <div class="relative bg-white rounded-[24px] shadow-2xl w-full max-w-md transform transition-all border border-[#f0e8df] overflow-hidden">
        <div class="pt-6 px-6 pb-2 border-b border-[#f0e8df] bg-[#fafaf9]">
          <h2 class="text-xl font-black text-slate-800 tracking-tight flex items-center gap-2">تجديد الاشتراك</h2>
          <p class="text-xs font-bold text-slate-400 mt-1 mb-4">تمديد فترة السماح للمدرسة لتتمكن من استخدام النظام.</p>
        </div>

        <div class="p-6">
          <!-- Plan Selection -->
          <div class="mb-6">
            <label class="block text-xs font-black text-slate-700 uppercase tracking-widest mb-2">اختر باقة الاشتراك</label>
            <select v-model="selectedPlanId" class="w-full px-4 py-3 bg-[#fcf9f4] border border-[#e8ded1] rounded-xl text-sm font-bold outline-none focus:border-[#157f7f] transition-all appearance-none cursor-pointer">
              <option :value="null">بدون باقة (تحديد يدوي)</option>
              <option v-for="p in plans" :key="p.id" :value="p.id">{{ p.name }}</option>
            </select>
          </div>

          <div class="mb-6">
            <label class="block text-xs font-black text-slate-700 uppercase tracking-widest mb-2">تاريخ الانتهاء الجديد (تحديد يدوي)</label>
            <input type="date" v-model="manualDate" class="w-full px-4 py-3 bg-[#fcf9f4] border border-[#e8ded1] rounded-xl text-sm font-mono font-black outline-none focus:border-[#157f7f] transition-all" />
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
          <button @click="showRenewModal = false" type="button" class="px-5 py-2.5 rounded-xl text-sm font-bold text-slate-500 hover:bg-white border border-transparent hover:border-[#e8ded1] transition-all">إلغاء</button>
          <button @click="submitRenewal" :disabled="isSubmitting" class="px-5 py-2.5 rounded-xl text-sm font-black text-white bg-[#157f7f] hover:bg-[#0f6060] transition-all disabled:opacity-50">
            {{ isSubmitting ? 'جاري التأكيد...' : 'تأكيد التجديد' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
