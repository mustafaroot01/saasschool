<script setup>
import { ref, onMounted } from 'vue'
import { SpeakerWaveIcon, PlusIcon, TrashIcon, ExclamationTriangleIcon, CheckCircleIcon, InformationCircleIcon } from '@heroicons/vue/24/outline'
import api from '../axios'

const announcements = ref([])
const isLoading = ref(true)
const isSubmitting = ref(false)
const error = ref(null)

const showModal = ref(false)
const formData = ref({
  title: '',
  content: '',
  type: 'info',
  start_date: new Date().toISOString().slice(0, 16),
  end_date: '',
  is_active: true
})

const fetchAnnouncements = async () => {
  try {
    isLoading.value = true
    error.value = null
    const response = await api.get('/api/announcements')
    announcements.value = response.data
  } catch (err) {
    error.value = 'تعذّر جلب الإعلانات'
  } finally {
    isLoading.value = false
  }
}

const submitForm = async () => {
  try {
    isSubmitting.value = true
    await api.post('/api/announcements', formData.value)
    showModal.value = false
    formData.value = { title: '', content: '', type: 'info', start_date: new Date().toISOString().slice(0, 16), end_date: '', is_active: true }
    fetchAnnouncements()
  } catch (err) {
    alert('حدث خطأ أثناء إضافة الإعلان')
  } finally {
    isSubmitting.value = false
  }
}

const deleteAnnouncement = async (id) => {
  if (!confirm('هل أنت متأكد من حذف هذا الإعلان؟')) return
  try {
    await api.delete(`/api/announcements/${id}`)
    fetchAnnouncements()
  } catch (err) {
    alert('حدث خطأ أثناء الحذف')
  }
}

const toggleStatus = async (item) => {
  try {
    await api.put(`/api/announcements/${item.id}`, { is_active: !item.is_active })
    item.is_active = !item.is_active
  } catch (err) {
    alert('حدث خطأ أثناء تغيير الحالة')
  }
}

const getTypeProps = (type) => {
    if(type === 'warning') return { icon: ExclamationTriangleIcon, color: 'text-amber-500', bg: 'bg-amber-50', border: 'border-amber-200' }
    if(type === 'success') return { icon: CheckCircleIcon, color: 'text-green-500', bg: 'bg-green-50', border: 'border-green-200' }
    return { icon: InformationCircleIcon, color: 'text-blue-500', bg: 'bg-blue-50', border: 'border-blue-200' }
}

onMounted(() => {
  fetchAnnouncements()
})
</script>

<template>
  <div class="h-full flex flex-col pt-2 animate-fade-in pb-10" dir="rtl">
    
    <!-- Header -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-8">
      <div>
        <h1 class="text-3xl font-black text-slate-800 tracking-tight">لوحة الإعلانات</h1>
        <p class="text-sm font-bold text-slate-400 mt-2">إدارة الرسائل والإشعارات العامة لجميع المدارس المعتمدة.</p>
      </div>
      <button @click="showModal = true" class="px-5 py-2.5 bg-[#157f7f] text-white rounded-xl font-black text-sm hover:bg-[#126b6b] transition-colors shadow-sm flex items-center gap-2">
        <PlusIcon class="h-5 w-5" />
        إضافة إعلان جديد
      </button>
    </div>

    <div v-if="error" class="bg-[#e64040]/10 text-[#e64040] p-4 rounded-2xl mb-6 text-sm font-bold border border-[#e64040]/20 flex items-center gap-3">
      <ExclamationTriangleIcon class="h-5 w-5" />
      {{ error }}
    </div>

    <!-- Data List -->
    <div class="bg-white rounded-[24px] border border-[#f0e8df] shadow-sm overflow-hidden min-h-[400px]">
      <div v-if="isLoading" class="flex justify-center items-center py-20">
        <div class="animate-spin rounded-full h-8 w-8 border-3 border-[#fcf9f4] border-t-[#157f7f]"></div>
      </div>

      <div v-else-if="announcements.length === 0" class="flex flex-col items-center justify-center py-24 text-slate-400">
        <SpeakerWaveIcon class="h-16 w-16 opacity-20 mb-4" />
        <h3 class="text-lg font-black text-slate-600 mb-1">لا توجد إعلانات</h3>
        <p class="text-sm font-bold">لم تقم بإضافة أي إعلانات عامة حتى الآن.</p>
      </div>

      <div v-else class="divide-y divide-slate-100">
        <div v-for="item in announcements" :key="item.id" class="p-6 hover:bg-slate-50 transition-colors flex flex-col sm:flex-row gap-5 items-start">
            
            <div :class="[getTypeProps(item.type).bg, getTypeProps(item.type).border, 'h-12 w-12 rounded-2xl flex items-center justify-center flex-shrink-0 border']">
                <component :is="getTypeProps(item.type).icon" :class="getTypeProps(item.type).color" class="h-6 w-6" />
            </div>

            <div class="flex-1">
                <div class="flex items-center gap-3 mb-2">
                    <h3 class="text-lg font-black text-slate-800">{{ item.title }}</h3>
                    <span :class="[item.is_active ? 'bg-green-100 text-green-700 border-green-200' : 'bg-slate-100 text-slate-500 border-slate-200', 'px-2.5 py-0.5 rounded-md text-[10px] font-black uppercase tracking-widest border border-transparent']">
                        {{ item.is_active ? 'نشط' : 'مخفي' }}
                    </span>
                </div>
                <p class="text-sm font-bold text-slate-500 leading-relaxed mb-4">{{ item.content }}</p>
                <div class="flex items-center gap-4 text-[11px] font-black tracking-wide text-slate-400 font-mono">
                    <span>البدء: {{ new Date(item.start_date).toLocaleString('en-GB') }}</span>
                    <span v-if="item.end_date">الانتهاء: {{ new Date(item.end_date).toLocaleString('en-GB') }}</span>
                    <span v-else>الانتهاء: مستمر</span>
                </div>
            </div>

            <div class="flex items-center gap-2 mt-4 sm:mt-0">
                <button @click="toggleStatus(item)" class="px-3 py-1.5 rounded-lg border text-xs font-black transition-colors" :class="item.is_active ? 'text-amber-600 border-amber-200 hover:bg-amber-50' : 'text-green-600 border-green-200 hover:bg-green-50'">
                    {{ item.is_active ? 'إخفاء' : 'تفعيل' }}
                </button>
                <button @click="deleteAnnouncement(item.id)" class="p-1.5 text-slate-300 hover:text-red-500 hover:bg-red-50 rounded-lg transition-colors">
                    <TrashIcon class="h-5 w-5" />
                </button>
            </div>
        </div>
      </div>
    </div>

    <!-- Create Modal -->
    <div v-if="showModal" class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4">
      <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm" @click="showModal = false"></div>
      
      <div class="relative bg-white rounded-[24px] shadow-2xl w-full max-w-lg transform transition-all border border-[#f0e8df] overflow-hidden">
        <div class="pt-6 px-6 pb-4 border-b border-[#f0e8df] bg-[#fafaf9]">
          <h2 class="text-xl font-black text-slate-800 tracking-tight">إضافة إعلان جديد</h2>
          <p class="text-xs font-bold text-slate-400 mt-1">سيظهر هذا الإعلان في لوحة تحكم جميع المدارس بناءً على التاريخ المحدد.</p>
        </div>

        <form @submit.prevent="submitForm" class="p-6">
          <div class="space-y-4">
            <div>
              <label class="block text-xs font-black text-slate-700 uppercase tracking-widest mb-2">عنوان الإعلان</label>
              <input v-model="formData.title" type="text" required class="w-full bg-slate-50 border-slate-200 text-slate-800 text-sm rounded-xl focus:ring-[#157f7f] focus:border-[#157f7f] block p-3 font-bold transition-colors" placeholder="مثال: تحديث النظام القادم">
            </div>
            
            <div>
              <label class="block text-xs font-black text-slate-700 uppercase tracking-widest mb-2">محتوى الإعلان</label>
              <textarea v-model="formData.content" required rows="3" class="w-full bg-slate-50 border-slate-200 text-slate-800 text-sm rounded-xl focus:ring-[#157f7f] focus:border-[#157f7f] block p-3 font-bold transition-colors resize-none" placeholder="نص الرسالة..."></textarea>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-black text-slate-700 uppercase tracking-widest mb-2">نوع الإعلان</label>
                    <select v-model="formData.type" class="w-full bg-slate-50 border-slate-200 text-slate-800 text-sm rounded-xl focus:ring-[#157f7f] focus:border-[#157f7f] block p-3 font-bold">
                        <option value="info">معلومة (Info)</option>
                        <option value="success">تحديث ناجح (Success)</option>
                        <option value="warning">تحذير مهم (Warning)</option>
                    </select>
                </div>
                <div>
                     <label class="block text-xs font-black text-slate-700 uppercase tracking-widest mb-2">حالة النشر فوراً</label>
                     <select v-model="formData.is_active" class="w-full bg-slate-50 border-slate-200 text-slate-800 text-sm rounded-xl focus:ring-[#157f7f] focus:border-[#157f7f] block p-3 font-bold">
                        <option :value="true">مفعل ويظهر للمدارس</option>
                        <option :value="false">مخفي (مسودة)</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-black text-slate-700 uppercase tracking-widest mb-2">تاريخ البدء</label>
                    <input v-model="formData.start_date" type="datetime-local" required class="w-full bg-slate-50 border-slate-200 text-slate-800 text-sm rounded-xl focus:ring-[#157f7f] focus:border-[#157f7f] block p-3 font-bold">
                </div>
                <div>
                    <label class="block text-xs font-black text-slate-700 uppercase tracking-widest mb-2">تاريخ الانتهاء <span class="text-slate-400 font-normal">(اختياري)</span></label>
                    <input v-model="formData.end_date" type="datetime-local" class="w-full bg-slate-50 border-slate-200 text-slate-800 text-sm rounded-xl focus:ring-[#157f7f] focus:border-[#157f7f] block p-3 font-bold">
                </div>
            </div>
          </div>

          <div class="mt-8 flex gap-3">
             <button type="submit" :disabled="isSubmitting" class="flex-1 bg-[#157f7f] text-white px-5 py-3 rounded-xl text-sm font-black hover:bg-[#126b6b] transition-all disabled:opacity-50">
                {{ isSubmitting ? 'جاري الحفظ...' : 'حفظ الإعلان' }}
             </button>
             <button @click="showModal = false" type="button" class="px-5 py-3 rounded-xl text-sm font-black text-slate-500 hover:bg-slate-50 border border-slate-200 transition-all">إلغاء</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
