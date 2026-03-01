<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '../axios'
import { PlusIcon, TrashIcon, CreditCardIcon, UserGroupIcon, ServerIcon } from '@heroicons/vue/24/outline'

const plans = ref([])
const isLoading = ref(true)
const isSubmitting = ref(false)
const showModal = ref(false)
const showDeleteConfirm = ref(false)
const planToDelete = ref(null)
const error = ref(null)

const isEditing = ref(false)
const planToEdit = ref(null)
const form = ref({
  name: '',
  max_students: 500,
  max_teachers: 30,
  max_admins: 5,
  storage_limit_mb: 2048,
  notifications_limit: 10000,
  price: 0,
  duration_months: 12,
  is_active: true
})

const fetchPlans = async () => {
  try {
    isLoading.value = true
    const response = await api.get('/api/plans') // In a real app, pass ?limit=30
    plans.value = response.data
  } catch (err) {
    error.value = 'الرجاء التحقق من الاتصال، لم يتم تحميل خطط الاشتراك.'
  } finally {
    isLoading.value = false
  }
}

const PER_PAGE = 30
const currentPage = ref(1)

const totalPages = computed(() => Math.max(1, Math.ceil(plans.value.length / PER_PAGE)))
const paginatedPlans = computed(() => {
  const start = (currentPage.value - 1) * PER_PAGE
  return plans.value.slice(start, start + PER_PAGE)
})
const showingFrom = computed(() => plans.value.length === 0 ? 0 : (currentPage.value - 1) * PER_PAGE + 1)
const showingTo = computed(() => Math.min(currentPage.value * PER_PAGE, plans.value.length))

const editPlan = (plan) => {
  isEditing.value = true
  planToEdit.value = plan.id
  form.value = { ...plan }
  showModal.value = true
}

const savePlan = async () => {
  try {
    isSubmitting.value = true
    error.value = null
    
    if (isEditing.value) {
      await api.put(`/api/plans/${planToEdit.value}`, form.value)
    } else {
      await api.post('/api/plans', form.value)
    }
    
    showModal.value = false
    isEditing.value = false
    planToEdit.value = null
    form.value = { name: '', max_students: 500, max_teachers: 30, max_admins: 5, storage_limit_mb: 2048, notifications_limit: 10000, price: 0, duration_months: 12, is_active: true }
    await fetchPlans()
  } catch (err) {
    if (err.response?.data?.errors) {
        error.value = Object.values(err.response.data.errors).flat().join(' ')
    } else {
        error.value = 'حدث خطأ أثناء حفظ الخطة، يرجى مرجعة البيانات.'
    }
  } finally {
    isSubmitting.value = false
  }
}

const closeModal = () => {
  showModal.value = false
  isEditing.value = false
  planToEdit.value = null
  form.value = { name: '', max_students: 500, max_teachers: 30, max_admins: 5, storage_limit_mb: 2048, notifications_limit: 10000, price: 0, duration_months: 12, is_active: true }
}

const openCreateModal = () => {
  isEditing.value = false
  planToEdit.value = null
  form.value = { name: '', max_students: 500, max_teachers: 30, max_admins: 5, storage_limit_mb: 2048, notifications_limit: 10000, price: 0, duration_months: 12, is_active: true }
  showModal.value = true
}

const confirmDelete = (id) => {
  planToDelete.value = id
  showDeleteConfirm.value = true
}

const deletePlan = async () => {
  if (!planToDelete.value) return
  try {
    await api.delete(`/api/plans/${planToDelete.value}`)
    showDeleteConfirm.value = false
    planToDelete.value = null
    await fetchPlans()
  } catch (err) {
    alert('حدث خطأ أثناء الحذف')
  }
}

const toggleStatus = async (plan) => {
  try {
    await api.put(`/api/plans/${plan.id}`, { is_active: !plan.is_active })
    plan.is_active = !plan.is_active
  } catch (err) {
    alert('تعذّر تغيير حالة الباقة')
  }
}

onMounted(() => {
  fetchPlans()
})
</script>

<template>
  <div class="space-y-6" dir="rtl">
    
    <!-- Top info section -->
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
      <div>
        <h1 class="text-2xl font-black text-slate-800 tracking-tight">باقات الاشتراك والتسعير</h1>
        <p class="mt-1 text-sm text-slate-500 font-bold leading-relaxed max-w-xl">تحديد القيود البرمجية والموارد المخصصة لكل مستوى اشتراك.</p>
      </div>
      <button @click="openCreateModal" type="button" class="inline-flex items-center justify-center px-5 py-3 bg-[#157f7f] text-white rounded-xl font-black text-sm shadow-md shadow-[#157f7f]/20 hover:bg-[#116666] transition-all">
        <PlusIcon class="w-4 h-4 ml-2" />
        إضافة باقة
      </button>
    </div>

    <!-- Error state -->
    <div v-if="error" class="bg-[#e64040]/5 border border-[#e64040]/10 text-[#e64040] p-4 rounded-2xl flex items-center gap-3 animate-shake">
        <div class="h-8 w-8 flex-shrink-0 bg-white rounded-lg flex items-center justify-center shadow-sm">
           <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
        </div>
        <span class="font-bold text-xs">{{ error }}</span>
    </div>

    <!-- Loading state -->
    <div v-if="isLoading" class="flex justify-center items-center py-20 bg-white rounded-3xl border border-[#f0e8df] shadow-sm">
      <div class="flex flex-col items-center gap-3">
        <div class="animate-spin rounded-full h-8 w-8 border-3 border-[#fcf9f4] border-t-[#157f7f]"></div>
        <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">تحميل...</span>
      </div>
    </div>

    <!-- Data Grid/Table -->
    <div v-else class="bg-white rounded-[24px] border border-[#f0e8df] shadow-sm overflow-hidden mb-8">
      <div class="overflow-x-auto custom-scrollbar">
        <table class="w-full text-right border-collapse">
          <thead>
            <tr class="bg-[#fafaf9] border-b border-[#f0e8df]">
              <th class="px-6 py-4 text-[11px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap">اسم الباقة</th>
              <th class="px-4 py-4 text-[11px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap">حد الطلاب</th>
              <th class="px-4 py-4 text-[11px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap">حد المعلمين</th>
              <th class="px-4 py-4 text-[11px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap">السعر (IQD)</th>
              <th class="px-4 py-4 text-[11px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap">المدة</th>
              <th class="px-4 py-4 text-[11px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap text-center">الحالة</th>
              <th class="px-6 py-4 text-[11px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap text-left">العمليات</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-[#f5f0e9]">
            <tr v-for="plan in paginatedPlans" :key="plan.id" class="group hover:bg-[#fcf9f4]/40 transition-colors">
              <td class="px-6 py-5 align-middle">
                <span class="text-xs font-black text-[#157f7f] uppercase tracking-tight">{{ plan.name }}</span>
              </td>
              <td class="px-4 py-5 align-middle">
                <span class="text-xs font-bold text-slate-600">{{ plan.max_students }} طالب</span>
              </td>
              <td class="px-4 py-5 align-middle">
                <span class="text-xs font-bold text-slate-600">{{ plan.max_teachers }} معلم</span>
              </td>
              <td class="px-4 py-5 align-middle">
                <span class="text-xs font-black text-[#157f7f]">{{ new Intl.NumberFormat('en-US').format(plan.price) }}</span>
              </td>
              <td class="px-4 py-5 align-middle">
                <span class="text-xs font-bold text-slate-500">{{ plan.duration_months }} شهر</span>
              </td>
              <td class="px-4 py-5 align-middle text-center">
                <div class="flex justify-center">
                  <span @click="toggleStatus(plan)" :class="plan.is_active ? 'bg-[#157f7f]/10 text-[#157f7f] border-[#157f7f]/20 cursor-pointer hover:bg-[#157f7f]/20' : 'bg-slate-100 text-slate-500 border-slate-200 cursor-pointer hover:bg-slate-200'" 
                        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full border text-[10px] font-black uppercase tracking-tighter transition-all">
                    <span :class="plan.is_active ? 'bg-[#157f7f]' : 'bg-slate-400'" class="h-1.5 w-1.5 rounded-full shadow-sm animate-pulse"></span>
                    {{ plan.is_active ? 'نشط' : 'متوقف' }}
                  </span>
                </div>
              </td>
              <td class="px-6 py-5 align-middle text-left">
                <div class="flex items-center justify-end gap-3 opacity-60 group-hover:opacity-100 transition-opacity">
                  <button @click="editPlan(plan)" class="p-1.5 text-slate-400 hover:text-[#157f7f] hover:bg-[#157f7f]/5 rounded-lg transition-all">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg>
                  </button>
                  <button @click="confirmDelete(plan.id)" class="p-1.5 text-slate-400 hover:text-[#e64040] hover:bg-[#e64040]/5 rounded-lg transition-all">
                    <TrashIcon class="h-4 w-4" />
                  </button>
                </div>
              </td>
            </tr>
            <tr v-if="plans.length === 0">
              <td colspan="5" class="px-6 py-12 text-center text-[11px] font-black text-slate-300 uppercase tracking-widest">
                لا توجد باقات اشتراك مضافة حالياً.
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Table Pagination (Dynamic) -->
      <div class="px-8 py-5 bg-[#fafaf9]/50 border-t border-[#f0e8df] flex items-center justify-between">
        <div class="flex items-center gap-2">
          <button @click="currentPage > 1 && currentPage--" :disabled="currentPage === 1"
                  class="h-8 w-8 flex items-center justify-center rounded-xl border border-[#e8ded1] bg-white text-slate-400 hover:text-[#157f7f] hover:border-[#157f7f] transition-all shadow-sm disabled:opacity-30 disabled:cursor-not-allowed">
            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
          </button>
          <div class="flex items-center gap-1.5">
            <button v-for="page in totalPages" :key="page" @click="currentPage = page"
                    :class="currentPage === page ? 'bg-[#157f7f] text-white shadow-md shadow-[#157f7f]/20' : 'border border-[#e8ded1] bg-white text-slate-500 hover:bg-slate-50 shadow-sm'"
                    class="h-8 w-8 flex items-center justify-center rounded-xl text-xs font-black transition-all">
              {{ page }}
            </button>
          </div>
          <button @click="currentPage < totalPages && currentPage++" :disabled="currentPage === totalPages"
                  class="h-8 w-8 flex items-center justify-center rounded-xl border border-[#e8ded1] bg-white text-slate-400 hover:text-[#157f7f] hover:border-[#157f7f] transition-all shadow-sm disabled:opacity-30 disabled:cursor-not-allowed">
            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
          </button>
        </div>
        <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">
          إظهار {{ showingFrom }} إلى {{ showingTo }} من أصل {{ plans.length }} باقة
        </span>
      </div>
    </div>

    <!-- Create Modal -->
    <div v-if="showModal" class="fixed inset-0 z-[100] overflow-y-auto flex items-center justify-center p-4">
      <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm" @click="closeModal"></div>
      
      <div class="relative bg-white w-full max-w-md rounded-[32px] shadow-2xl border border-[#f0e8df] overflow-hidden animate-zoomIn">
        <div class="p-8">
          <div class="flex items-center justify-between mb-6">
            <h3 class="text-xl font-black text-slate-800 tracking-tight">{{ isEditing ? 'تعديل باقة' : 'إضافة باقة جديدة' }}</h3>
            <button @click="closeModal" class="h-8 w-8 flex items-center justify-center rounded-xl bg-[#fcf9f4] text-slate-400 hover:text-slate-600 transition-colors">
              <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
          </div>

          <form @submit.prevent="savePlan" class="space-y-4">
            <div class="space-y-1">
              <label class="block text-[11px] font-black text-slate-400 mr-1 uppercase">اسم الباقة</label>
              <input type="text" v-model="form.name" required class="block w-full px-4 py-2.5 bg-[#fcf9f4] border border-[#e8ded1] rounded-xl text-sm outline-none focus:border-[#157f7f]" />
            </div>
            
            <div class="grid grid-cols-2 gap-4">
              <div class="space-y-1">
                <label class="block text-[11px] font-black text-slate-400 mr-1 uppercase">أقصى عدد للطلاب</label>
                <input type="number" v-model="form.max_students" required class="block w-full px-4 py-2.5 bg-[#fcf9f4] border border-[#e8ded1] rounded-xl text-sm outline-none focus:border-[#157f7f]" />
              </div>
              <div class="space-y-1">
                <label class="block text-[11px] font-black text-slate-400 mr-1 uppercase">أقصى عدد للمعلمين</label>
                <input type="number" v-model="form.max_teachers" required class="block w-full px-4 py-2.5 bg-[#fcf9f4] border border-[#e8ded1] rounded-xl text-sm outline-none focus:border-[#157f7f]" />
              </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div class="space-y-1">
                <label class="block text-[11px] font-black text-slate-400 mr-1 uppercase">السعر (IQD)</label>
                <input type="number" v-model="form.price" required class="block w-full px-4 py-2.5 bg-[#fcf9f4] border border-[#e8ded1] rounded-xl text-sm outline-none focus:border-[#157f7f]" />
              </div>
              <div class="space-y-1">
                <label class="block text-[11px] font-black text-slate-400 mr-1 uppercase">المدة (أشهر)</label>
                <input type="number" v-model="form.duration_months" required class="block w-full px-4 py-2.5 bg-[#fcf9f4] border border-[#e8ded1] rounded-xl text-sm outline-none focus:border-[#157f7f]" />
              </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div class="space-y-1">
                <label class="block text-[11px] font-black text-slate-400 mr-1 uppercase">سعة التخزين (MB)</label>
                <input type="number" v-model="form.storage_limit_mb" required class="block w-full px-4 py-2.5 bg-[#fcf9f4] border border-[#e8ded1] rounded-xl text-sm outline-none focus:border-[#157f7f]" />
              </div>
              <div class="space-y-1">
                <label class="block text-[11px] font-black text-slate-400 mr-1 uppercase">عدد الإداريين</label>
                <input type="number" v-model="form.max_admins" required class="block w-full px-4 py-2.5 bg-[#fcf9f4] border border-[#e8ded1] rounded-xl text-sm outline-none focus:border-[#157f7f]" />
              </div>
            </div>

            <div class="space-y-1">
                <label class="block text-[11px] font-black text-slate-400 mr-1 uppercase">حد الإشعارات</label>
                <input type="number" v-model="form.notifications_limit" required class="block w-full px-4 py-2.5 bg-[#fcf9f4] border border-[#e8ded1] rounded-xl text-sm outline-none focus:border-[#157f7f]" />
            </div>

            <div class="flex items-center gap-2 py-2">
                <input type="checkbox" v-model="form.is_active" id="is_active" class="rounded text-[#157f7f] focus:ring-[#157f7f]" />
                <label for="is_active" class="text-xs font-bold text-slate-600">تنشيط الباقة</label>
            </div>

            <div class="pt-4 flex gap-3">
              <button type="submit" :disabled="isSubmitting" class="flex-1 py-3 px-6 bg-[#157f7f] text-white rounded-xl font-black text-sm transition-all disabled:opacity-50">
                {{ isEditing ? 'تحديث الباقة' : 'حفظ الباقة' }}
              </button>
              <button @click="closeModal" type="button" class="px-6 py-3 bg-white border border-[#e8ded1] text-slate-600 rounded-xl text-sm">إلغاء</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Custom Delete Confirmation -->
    <div v-if="showDeleteConfirm" class="fixed inset-0 z-[110] flex items-center justify-center p-4">
      <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" @click="showDeleteConfirm = false"></div>
      <div class="relative bg-white w-full max-w-sm rounded-[32px] shadow-2xl p-8 text-center animate-zoomIn">
        <div class="h-16 w-16 bg-[#e64040]/5 rounded-full flex items-center justify-center mx-auto mb-4">
          <TrashIcon class="h-8 w-8 text-[#e64040]" />
        </div>
        <h3 class="text-xl font-black text-slate-800 mb-2">حذف الباقة؟</h3>
        <p class="text-xs text-slate-500 font-bold mb-8">قد يؤثر هذا على المدارس المشتركين حالياً.</p>
        <div class="flex flex-col gap-2">
          <button @click="deletePlan" class="w-full py-3 bg-[#e64040] text-white rounded-xl font-black text-sm">تأكيد الحذف</button>
          <button @click="showDeleteConfirm = false" class="w-full py-3 bg-[#fcf9f4] text-slate-600 rounded-xl font-bold text-sm">إلغاء</button>
        </div>
      </div>
    </div>
  </div>
</template>
