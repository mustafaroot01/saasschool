<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '../axios'
import { PlusIcon, TrashIcon, MagnifyingGlassIcon, AdjustmentsHorizontalIcon, EnvelopeIcon, PhoneIcon } from '@heroicons/vue/24/outline'

const schools = ref([])
const plans = ref([])
const isLoading = ref(true)
const isSubmitting = ref(false)
const showModal = ref(false)
const showDeleteConfirm = ref(false)
const schoolToDelete = ref(null)
const error = ref(null)

const newSchoolCredentials = ref(null)


const isEditing = ref(false)
const schoolToEdit = ref(null)
const logoPreview = ref(null)
const logoFile = ref(null)

const emptyForm = () => ({ id: '', name: '', plan_id: '', domain: '', contact_email: '', contact_phone: '' })
const form = ref(emptyForm())

// Search and Filter State
const searchQuery = ref('')
const filterStatus = ref('all') // 'all', 'active', 'suspended'

const fetchSchools = async () => {
  try {
    isLoading.value = true
    const [schoolsRes, plansRes] = await Promise.all([
        api.get('/api/schools'), // In a real app, we'd pass ?limit=30
        api.get('/api/plans')
    ])
    schools.value = schoolsRes.data
    plans.value = plansRes.data
  } catch (err) {
    error.value = 'الرجاء التحقق من الاتصال، لم يتم تحميل المدارس.'
  } finally {
    isLoading.value = false
  }
}

const PER_PAGE = 30
const currentPage = ref(1)

const totalPages = computed(() => Math.max(1, Math.ceil(filteredSchools.value.length / PER_PAGE)))

const filteredSchools = computed(() => {
  let result = schools.value
  
  // Apply Search
  if (searchQuery.value) {
    const q = searchQuery.value.toLowerCase()
    result = result.filter(s => 
      s.name.toLowerCase().includes(q) || 
      s.id.toLowerCase().includes(q) ||
      (s.domains && s.domains.some(d => d.domain.toLowerCase().includes(q)))
    )
  }
  
  // Apply Status Filter
  if (filterStatus.value !== 'all') {
    result = result.filter(s => s.status === filterStatus.value)
  }
  
  return result
})

const paginatedSchools = computed(() => {
  const start = (currentPage.value - 1) * PER_PAGE
  return filteredSchools.value.slice(start, start + PER_PAGE)
})
const showingFrom = computed(() => filteredSchools.value.length === 0 ? 0 : (currentPage.value - 1) * PER_PAGE + 1)
const showingTo = computed(() => Math.min(currentPage.value * PER_PAGE, filteredSchools.value.length))

const getPlanName = (planId) => {
  const plan = plans.value.find(p => p.id === planId)
  return plan ? plan.name : 'باقة غير معروفة'
}

const editSchool = (school) => {
  isEditing.value = true
  schoolToEdit.value = school.id
  form.value = {
    id: school.id,
    name: school.name,
    plan_id: school.plan_id,
    domain: school.domains?.[0]?.domain?.replace('.localhost', '') || '',
    contact_email: school.contact_email || '',
    contact_phone: school.contact_phone || ''
  }
  logoPreview.value = school.logo ? `http://localhost:8000/storage/${school.logo}` : null
  logoFile.value = null
  showModal.value = true
}

const onLogoChange = (e) => {
  const file = e.target.files[0]
  if (!file) return
  logoFile.value = file
  logoPreview.value = URL.createObjectURL(file)
}

const saveSchool = async () => {
  try {
    isSubmitting.value = true
    error.value = null

    const data = new FormData()
    data.append('name', form.value.name)
    if (form.value.plan_id) data.append('plan_id', form.value.plan_id)
    data.append('domain', (form.value.domain || '') + '.localhost')
    if (form.value.contact_email) data.append('contact_email', form.value.contact_email)
    if (form.value.contact_phone) data.append('contact_phone', form.value.contact_phone)
    if (logoFile.value) data.append('logo', logoFile.value)

    if (isEditing.value) {
      data.append('_method', 'PUT') // Laravel method spoofing for FormData
      await api.post(`/api/schools/${schoolToEdit.value}`, data, { headers: { 'Content-Type': 'multipart/form-data' } })
    } else {
      data.append('id', form.value.id)
      const response = await api.post('/api/schools', data, { headers: { 'Content-Type': 'multipart/form-data' } })
      if (response.data.admin_email && response.data.admin_password) {
        newSchoolCredentials.value = {
          email: response.data.admin_email,
          password: response.data.admin_password,
          domain: form.value.domain + '.localhost'
        }
      }
    }

    showModal.value = false
    isEditing.value = false
    schoolToEdit.value = null
    form.value = emptyForm()
    logoPreview.value = null
    logoFile.value = null
    await fetchSchools()
  } catch (err) {
    if (err.response?.data?.errors) {
        error.value = Object.values(err.response.data.errors).flat().join(' ')
    } else {
        error.value = err.response?.data?.message || 'حدث خطأ أثناء حفظ المدرسة'
    }
  } finally {
    isSubmitting.value = false
  }
}

const closeModal = () => {
  showModal.value = false
  isEditing.value = false
  schoolToEdit.value = null
  form.value = emptyForm()
  logoPreview.value = null
  logoFile.value = null
}

const openCreateModal = () => {
  isEditing.value = false
  schoolToEdit.value = null
  form.value = emptyForm()
  logoPreview.value = null
  logoFile.value = null
  showModal.value = true
}

const toggleStatus = async (school) => {
  const newStatus = school.status === 'active' ? 'suspended' : 'active'
  try {
    await api.put(`/api/schools/${school.id}`, { status: newStatus })
    school.status = newStatus // optimistic update
  } catch {
    error.value = 'تعذّر تغيير حالة المدرسة، حاول مجدداً.'
  }
}

const confirmDelete = (id) => {
  schoolToDelete.value = id
  showDeleteConfirm.value = true
}

const deleteSchool = async () => {
  if (!schoolToDelete.value) return
  try {
    await api.delete(`/api/schools/${schoolToDelete.value}`)
    showDeleteConfirm.value = false
    schoolToDelete.value = null
    await fetchSchools()
  } catch (err) {
    alert('حدث خطأ أثناء الحذف')
  }
}

onMounted(() => {
  fetchSchools()
})
</script>

<template>
  <div class="space-y-6" dir="rtl">
    
    <!-- Top info section -->
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
      <div>
        <h1 class="text-2xl font-black text-slate-800 tracking-tight">المدارس والأنظمة</h1>
        <p class="mt-1 text-sm text-slate-500 font-bold leading-relaxed max-w-xl">إدارة جميع الاشتراكات المدرسية النشطة والتحكم في الموارد.</p>
      </div>
      <button @click="openCreateModal" type="button" class="inline-flex items-center justify-center px-5 py-3 bg-[#157f7f] text-white rounded-xl font-black text-sm shadow-md shadow-[#157f7f]/20 hover:bg-[#116666] transition-all">
        <PlusIcon class="w-4 h-4 ml-2" />
        تسجيل مدرسة
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

    <div v-else class="space-y-6">
      
      <!-- Filters and Search -->
      <div class="flex flex-col md:flex-row gap-4 items-center justify-between bg-white p-4 rounded-2xl border border-[#f0e8df] shadow-sm">
          <div class="relative w-full md:w-96">
             <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none">
                 <MagnifyingGlassIcon class="h-5 w-5 text-slate-400" />
             </div>
             <input type="text" v-model="searchQuery" placeholder="ابحث باسم المدرسة، المعرف، أو النطاق..." class="block w-full pl-4 pr-11 py-3 bg-[#fcf9f4] border border-[#e8ded1] rounded-xl text-sm outline-none focus:border-[#157f7f] transition-colors placeholder:text-slate-400 font-bold" />
          </div>
          <div class="flex w-full md:w-auto p-1 bg-slate-100 rounded-xl h-[46px]">
              <button @click="filterStatus = 'all'" :class="filterStatus === 'all' ? 'bg-white text-slate-800 shadow-sm' : 'text-slate-500 hover:text-slate-700'" class="flex-1 md:px-6 py-1.5 text-xs font-black rounded-lg transition-all">الكل</button>
              <button @click="filterStatus = 'active'" :class="filterStatus === 'active' ? 'bg-[#157f7f] text-white shadow-sm' : 'text-slate-500 hover:text-slate-700'" class="flex-1 md:px-6 py-1.5 text-xs font-black rounded-lg transition-all">النشطة</button>
              <button @click="filterStatus = 'suspended'" :class="filterStatus === 'suspended' ? 'bg-slate-400 text-white shadow-sm' : 'text-slate-500 hover:text-slate-700'" class="flex-1 md:px-6 py-1.5 text-xs font-black rounded-lg transition-all">المتوقفة</button>
          </div>
      </div>

      <!-- Data Grid/Table -->
      <div class="bg-white rounded-[24px] border border-[#f0e8df] shadow-sm overflow-hidden mb-8">
        <div class="overflow-x-auto custom-scrollbar">
        <table class="w-full text-right border-collapse">
          <thead>
            <tr class="bg-[#fafaf9] border-b border-[#f0e8df]">
              <th class="px-6 py-4 text-[11px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap">المعرف</th>
              <th class="px-4 py-4 text-[11px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap">المدرسة</th>
              <th class="px-4 py-4 text-[11px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap">الباقة</th>
              <th class="px-4 py-4 text-[11px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap text-center">النطاقات</th>
              <th class="px-4 py-4 text-[11px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap text-center">الحالة</th>
              <th class="px-4 py-4 text-[11px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap text-center">قاعدة البيانات</th>
              <th class="px-4 py-4 text-[11px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap text-center">التواريخ</th>
              <th class="px-6 py-4 text-[11px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap text-left">العمليات</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-[#f5f0e9]">
            <tr v-for="school in paginatedSchools" :key="school.id" class="group hover:bg-[#fcf9f4]/40 transition-colors">
              <td class="px-6 py-5 align-middle">
                <span class="text-xs font-black text-[#157f7f] font-mono tracking-tighter">{{ school.id.substring(0,8) }}</span>
              </td>
              <td class="px-4 py-5 align-middle">
                <div class="flex items-center gap-3">
                  <div class="h-10 w-10 rounded-xl bg-[#fcf9f4] border border-[#e8ded1] flex items-center justify-center overflow-hidden flex-shrink-0">
                    <img v-if="school.logo" :src="`http://localhost:8000/storage/${school.logo}`" class="h-full w-full object-cover" />
                    <span v-else class="text-xs font-black text-[#157f7f]">{{ school.name.charAt(0) }}</span>
                  </div>
                  <span class="text-xs font-black text-slate-800 truncate max-w-[150px]">{{ school.name }}</span>
                </div>
              </td>
              <td class="px-4 py-5 align-middle">
                <span class="text-[11px] font-bold text-slate-500 bg-slate-50 border border-slate-200 px-2.5 py-1 rounded-lg truncate block w-fit max-w-[120px]">{{ getPlanName(school.plan_id) }}</span>
              </td>
              <td class="px-4 py-5 align-middle text-center">
                <div class="flex flex-wrap justify-center gap-1.5">
                  <span v-for="domain in school.domains" :key="domain.id" class="inline-flex px-2 py-0.5 bg-slate-50 border border-slate-200 rounded-md text-[10px] font-bold text-slate-400 font-mono">
                    {{ domain.domain }}
                  </span>
                </div>
              </td>
              <td class="px-4 py-5 align-middle text-center">
                <div class="flex justify-center">
                  <button @click="toggleStatus(school)" 
                          :title="school.status === 'active' ? 'اضغط لتعليق المدرسة' : 'اضغط لتفعيل المدرسة'"
                          :class="school.status === 'active' ? 'bg-[#157f7f]/10 text-[#157f7f] border-[#157f7f]/20 hover:bg-[#e64040]/10 hover:text-[#e64040] hover:border-[#e64040]/20' : 'bg-slate-100 text-slate-500 border-slate-200 hover:bg-[#157f7f]/10 hover:text-[#157f7f] hover:border-[#157f7f]/20'"
                          class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full border text-[10px] font-black uppercase tracking-tighter transition-all cursor-pointer active:scale-95">
                    <span :class="school.status === 'active' ? 'bg-[#157f7f]' : 'bg-slate-400'" class="h-1.5 w-1.5 rounded-full shadow-sm animate-pulse"></span>
                    {{ school.status === 'active' ? 'نشط' : 'متوقف' }}
                  </button>
                </div>
              </td>
              <td class="px-4 py-5 align-middle text-center">
                 <span class="text-[10px] font-bold text-slate-500 font-mono">{{ school.tenancy_db_name }}</span>
              </td>
              <td class="px-4 py-5 align-middle text-center">
                <div class="flex flex-col gap-1">
                  <span class="text-[10px] text-slate-600 font-bold" title="تاريخ الإنشاء">
                    <span class="opacity-50 mx-1">إنشاء:</span> {{ new Date(school.created_at).toLocaleDateString('en-GB') }}
                  </span>
                  <span class="text-[10px] text-slate-600 font-bold" title="تاريخ الانتهاء">
                    <span class="opacity-50 mx-1">انتهاء:</span> {{ school.subscription_end_date ? new Date(school.subscription_end_date).toLocaleDateString('en-GB') : 'غير محدد' }}
                  </span>
                </div>
              </td>
              <td class="px-6 py-5 align-middle text-left">
                <div class="flex items-center justify-end gap-3 opacity-60 group-hover:opacity-100 transition-opacity">
                  <router-link :to="`/schools/${school.id}`" class="p-2 text-slate-400 hover:text-[#157f7f] hover:bg-[#157f7f]/10 rounded-lg transition-colors" title="عرض التفاصيل الكاملة">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                  </router-link>
                  <button @click="editSchool(school)" class="p-1.5 text-slate-400 hover:text-[#157f7f] hover:bg-[#157f7f]/5 rounded-lg transition-all">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg>
                  </button>
                  <button @click="confirmDelete(school.id)" class="p-1.5 text-slate-400 hover:text-[#e64040] hover:bg-[#e64040]/5 rounded-lg transition-all">
                    <TrashIcon class="h-4 w-4" />
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
        </div>
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
          إظهار {{ showingFrom }} إلى {{ showingTo }} من أصل {{ filteredSchools.length }} مدرسة
        </span>
      </div>
    </div>

    <!-- Create / Edit Modal -->
    <div v-if="showModal" class="fixed inset-0 z-[100] overflow-y-auto flex items-center justify-center p-4">
      <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm" @click="closeModal"></div>
      
      <div class="relative bg-white w-full max-w-lg rounded-[32px] shadow-2xl border border-[#f0e8df] overflow-hidden animate-zoomIn">
        <div class="p-8">
          <div class="flex items-center justify-between mb-6">
            <h3 class="text-xl font-black text-slate-800 tracking-tight">{{ isEditing ? 'تعديل مدرسة' : 'إضافة مدرسة جديدة' }}</h3>
            <button @click="closeModal" class="h-8 w-8 flex items-center justify-center rounded-xl bg-[#fcf9f4] text-slate-400 hover:text-slate-600 transition-colors">
              <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
          </div>

          <form @submit.prevent="saveSchool" class="space-y-4">
            <!-- Logo Upload -->
            <div class="flex items-center gap-4">
              <div class="h-16 w-16 rounded-2xl bg-[#fcf9f4] border-2 border-dashed border-[#e8ded1] flex items-center justify-center overflow-hidden flex-shrink-0 cursor-pointer" @click="$refs.logoInput.click()">
                <img v-if="logoPreview" :src="logoPreview" class="h-full w-full object-cover" />
                <svg v-else class="h-6 w-6 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
              </div>
              <div>
                <p class="text-xs font-black text-slate-700">شعار المدرسة</p>
                <p class="text-[10px] text-slate-400 font-bold mt-0.5">PNG، JPG حتى 2MB</p>
                <button type="button" @click="$refs.logoInput.click()" class="mt-1.5 text-[10px] font-black text-[#157f7f] hover:underline uppercase tracking-wide">اختر صورة</button>
              </div>
              <input ref="logoInput" type="file" accept="image/*" class="hidden" @change="onLogoChange" />
            </div>

            <!-- School Name -->
            <div class="space-y-1">
              <label class="block text-[11px] font-black text-slate-400 mr-1 uppercase">الاسم الرسمي</label>
              <input type="text" v-model="form.name" required class="block w-full px-4 py-2.5 bg-[#fcf9f4] border border-[#e8ded1] rounded-xl text-sm outline-none focus:border-[#157f7f]" />
            </div>

            <!-- Technical ID (only on create) -->
            <div v-if="!isEditing" class="space-y-1">
              <label class="block text-[11px] font-black text-slate-400 mr-1 uppercase">المعرّف التقني</label>
              <input type="text" v-model="form.id" required class="block w-full px-4 py-2.5 bg-[#fcf9f4] border border-[#e8ded1] rounded-xl text-sm outline-none focus:border-[#157f7f] font-mono" dir="ltr" />
            </div>

            <!-- Domain -->
            <div class="space-y-1">
              <label class="block text-[11px] font-black text-slate-400 mr-1 uppercase">الدومين</label>
              <div class="relative flex items-center">
                <input type="text" v-model="form.domain" :required="!isEditing" class="block w-full px-4 py-2.5 bg-[#fcf9f4] border border-[#e8ded1] rounded-xl text-sm outline-none focus:border-[#157f7f] text-left pr-4 pl-20" dir="ltr" />
                <span class="absolute left-3 text-[10px] font-black text-slate-300" dir="ltr">.localhost</span>
              </div>
            </div>

            <!-- Plan (Only on Edit) -->
            <div v-if="isEditing" class="space-y-1">
              <label class="block text-[11px] font-black text-slate-400 mr-1 uppercase">باقة الاشتراك</label>
              <select v-model="form.plan_id" required class="block w-full px-4 py-2.5 bg-[#fcf9f4] border border-[#e8ded1] rounded-xl text-sm outline-none focus:border-[#157f7f] appearance-none cursor-pointer font-bold">
                <option v-for="plan in plans" :key="plan.id" :value="plan.id">{{ plan.name }}</option>
              </select>
            </div>

            <!-- Contact Email & Phone -->
            <div class="grid grid-cols-2 gap-3">
              <div class="space-y-1">
                <label class="block text-[11px] font-black text-slate-400 mr-1 uppercase">البريد الإلكتروني</label>
                <input type="email" v-model="form.contact_email" class="block w-full px-4 py-2.5 bg-[#fcf9f4] border border-[#e8ded1] rounded-xl text-sm outline-none focus:border-[#157f7f]" dir="ltr" />
              </div>
              <div class="space-y-1">
                <label class="block text-[11px] font-black text-slate-400 mr-1 uppercase">رقم الهاتف</label>
                <input type="text" v-model="form.contact_phone" class="block w-full px-4 py-2.5 bg-[#fcf9f4] border border-[#e8ded1] rounded-xl text-sm outline-none focus:border-[#157f7f]" dir="ltr" />
              </div>
            </div>

            <div class="pt-4 flex gap-3">
              <button type="submit" :disabled="isSubmitting" class="flex-1 py-3 px-6 bg-[#157f7f] text-white rounded-xl font-black text-sm transition-all disabled:opacity-50">
                {{ isSubmitting ? 'جارٍ الحفظ...' : (isEditing ? 'تحديث البيانات' : 'حفظ المدرسة') }}
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
        <h3 class="text-xl font-black text-slate-800 mb-2">حذف المدرسة؟</h3>
        <p class="text-xs text-slate-500 font-bold mb-8">هذا الإجراء سيحذف كافة البيانات نهائياً.</p>
        <div class="flex flex-col gap-2">
          <button @click="deleteSchool" class="w-full py-3 bg-[#e64040] text-white rounded-xl font-black text-sm">تأكيد الحذف</button>
          <button @click="showDeleteConfirm = false" class="w-full py-3 bg-[#fcf9f4] text-slate-600 rounded-xl font-bold text-sm">إلغاء</button>
        </div>
      </div>
    
    <!-- New School Credentials Modal -->
    <div v-if="newSchoolCredentials" class="fixed inset-0 z-[100] overflow-y-auto flex items-center justify-center p-4">
      <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" @click="newSchoolCredentials = null"></div>
      
      <div class="relative bg-white rounded-[24px] shadow-2xl w-full max-w-md transform transition-all border border-[#f0e8df] overflow-hidden animate-fade-in-up">
        <div class="pt-6 px-6 pb-4 border-b border-[#f0e8df] bg-[#fafaf9] text-center">
          <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-[#157f7f]/10 mb-4">
            <CheckCircleIcon class="h-6 w-6 text-[#157f7f]" />
          </div>
          <h2 class="text-xl font-black text-slate-800 tracking-tight">تم إنشاء المدرسة بنجاح!</h2>
          <p class="text-xs font-bold text-slate-500 mt-2">يرجى حفظ بيانات الدخول الخاصة بمدير المدرسة وإرسالها إليه، <strong>لن تظهر هذه البيانات مرة أخرى</strong>.</p>
        </div>

        <div class="p-6 space-y-4">
          <div class="bg-blue-50/50 border border-blue-100 rounded-xl p-4">
            <label class="block text-[10px] font-black tracking-widest text-blue-400 uppercase mb-1">رابط الدخول للمدرسة</label>
            <div class="text-sm font-mono font-bold text-blue-900 break-all select-all">http://{{ newSchoolCredentials.domain }}:8000</div>
          </div>
          <div class="bg-slate-50 border border-slate-200 rounded-xl p-4">
            <label class="block text-[10px] font-black tracking-widest text-slate-400 uppercase mb-1">البريد الإلكتروني للإدارة (Admin)</label>
            <div class="text-sm font-mono font-bold text-slate-800 select-all">{{ newSchoolCredentials.email }}</div>
          </div>
          <div class="bg-slate-50 border border-slate-200 rounded-xl p-4">
            <label class="block text-[10px] font-black tracking-widest text-slate-400 uppercase mb-1">كلمة المرور المؤقتة</label>
            <div class="text-sm font-mono font-bold text-slate-800 select-all">{{ newSchoolCredentials.password }}</div>
          </div>
        </div>

        <div class="p-5 bg-[#fafaf9] border-t border-[#f0e8df]">
          <button @click="newSchoolCredentials = null" type="button" class="w-full inline-flex justify-center rounded-xl bg-[#157f7f] px-4 py-3 text-sm font-black text-white hover:bg-[#116666] shadow-md shadow-[#157f7f]/20 transition-all focus:outline-none focus:ring-2 focus:ring-[#157f7f] focus:ring-offset-2">
            تم حفظ البيانات وإغلاق النافذة
          </button>
        </div>
      </div>
    </div>
    </div>
  </div>
</template>

<style>
@keyframes zoomIn {
  from { opacity: 0; transform: scale(0.95); }
  to { opacity: 1; transform: scale(1); }
}
.animate-zoomIn {
  animation: zoomIn 0.3s ease-out;
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
