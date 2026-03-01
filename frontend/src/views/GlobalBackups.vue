<script setup>
import { ref, onMounted, computed } from 'vue'
import { CloudArrowDownIcon, MagnifyingGlassIcon, FunnelIcon } from '@heroicons/vue/24/outline'
import api from '../axios'

const backups = ref([])
const isLoading = ref(false)
const searchQuery = ref('')
const selectedSchool = ref('all')
const schools = ref([])

const fetchSchools = async () => {
    try {
        const response = await api.get('/api/schools')
        schools.value = response.data
    } catch (err) {
        console.error('Failed to fetch schools', err)
    }
}

const fetchAllBackups = async () => {
    isLoading.value = true
    try {
        const response = await api.get('/api/all-backups')
        backups.value = response.data
    } catch (err) {
        console.error('Failed to fetch all backups', err)
    } finally {
        isLoading.value = false
    }
}

const filteredBackups = computed(() => {
    let result = backups.value

    if (selectedSchool.value !== 'all') {
        result = result.filter(b => b.school_id === selectedSchool.value)
    }

    if (!searchQuery.value) return result
    
    const q = searchQuery.value.toLowerCase()
    return result.filter(b => 
        b.school_name.toLowerCase().includes(q) || 
        b.name.toLowerCase().includes(q)
    )
})

const formatSize = (bytes) => {
    if (bytes === 0) return '0 B'
    const k = 1024
    const sizes = ['B', 'KB', 'MB', 'GB']
    const i = Math.floor(Math.log(bytes) / Math.log(k))
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
}

const downloadBackup = (backup) => {
    window.open(`http://localhost:8000/api/schools/${backup.school_id}/backups/${backup.name}`, '_blank')
}

const deleteAllBackups = async () => {
    if (!confirm('هل أنت متأكد من مسح كافة النسخ الاحتياطية لجميع المدارس؟ لا يمكن التراجع عن هذا الإجراء.')) return;
    
    const code = prompt('لتأكيد الحذف، يرجى كتابة كلمة "حذف" أدناه:');
    if (code !== 'حذف') {
        alert('تم إلغاء عملية الحذف.');
        return;
    }

    try {
        await api.delete('/api/all-backups');
        backups.value = [];
        alert('تم مسح جميع النسخ الاحتياطية بنجاح.');
    } catch (err) {
        alert('حدث خطأ أثناء مسح النسخ الاحتياطية.');
    }
}

onMounted(() => {
    fetchAllBackups()
    fetchSchools()
})
</script>

<template>
  <div class="space-y-6" dir="rtl">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
      <div>
        <h1 class="text-2xl font-black text-slate-800 tracking-tight">إدارة النسخ الاحتياطية</h1>
        <p class="mt-1 text-sm text-slate-500 font-bold leading-relaxed max-w-xl">مركز التحكم الموحد لمراجعة وتحميل النسخ الاحتياطية لكافة المدارس المسجلة.</p>
      </div>
      <div>
        <button @click="deleteAllBackups" class="px-4 py-2 bg-[#e64040]/10 text-[#e64040] hover:bg-[#e64040] hover:text-white rounded-xl text-xs font-black transition-colors flex items-center gap-2 border border-[#e64040]/20">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
            تصفير النسخ الاحتياطية
        </button>
      </div>
    </div>

    <!-- Filters & Search -->
    <div class="bg-white p-4 rounded-3xl border border-[#f0e8df] shadow-sm flex flex-col md:flex-row gap-4 items-center">
      <div class="relative flex-1 w-full">
        <MagnifyingGlassIcon class="absolute right-4 top-1/2 -translate-y-1/2 h-5 w-5 text-slate-300" />
        <input v-model="searchQuery" type="text" placeholder="البحث حسب اسم المدرسة أو اسم الملف..." class="w-full pr-12 pl-4 py-3 bg-[#fcf9f4] border border-[#e8ded1] rounded-2xl text-sm font-bold outline-none focus:border-[#157f7f] transition-all" />
      </div>
      <div class="flex items-center gap-2 w-full md:w-64">
         <select v-model="selectedSchool" class="w-full px-4 py-3 bg-[#fcf9f4] border border-[#e8ded1] rounded-2xl text-xs font-black outline-none focus:border-[#157f7f] appearance-none cursor-pointer">
            <option value="all">كل المدارس</option>
            <option v-for="school in schools" :key="school.id" :value="school.id">{{ school.name }}</option>
         </select>
      </div>
      <div class="hidden md:flex items-center gap-2 text-[#157f7f] bg-[#157f7f]/5 px-4 py-3 rounded-2xl border border-[#157f7f]/10">
         <FunnelIcon class="h-5 w-5" />
         <span class="text-[10px] font-black uppercase tracking-widest">تصفية حسب المدرسة</span>
      </div>
    </div>

    <!-- Backups Table -->
    <div class="bg-white rounded-[32px] border border-[#f0e8df] shadow-sm overflow-hidden">
        <table class="w-full text-right border-collapse">
            <thead>
                <tr class="bg-[#fafaf9] border-b border-[#f0e8df]">
                    <th class="px-6 py-4 text-[11px] font-black text-slate-400 uppercase tracking-widest">المدرسة</th>
                    <th class="px-6 py-4 text-[11px] font-black text-slate-400 uppercase tracking-widest">اسم الملف</th>
                    <th class="px-6 py-4 text-[11px] font-black text-slate-400 uppercase tracking-widest">الحجم</th>
                    <th class="px-6 py-4 text-[11px] font-black text-slate-400 uppercase tracking-widest text-center">التاريخ</th>
                    <th class="px-6 py-4 text-[11px] font-black text-slate-400 uppercase tracking-widest text-left">الإجراءات</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-[#f5f0e9]">
                <tr v-for="backup in filteredBackups" :key="backup.school_id + backup.name" class="group hover:bg-[#fcf9f4]/40 transition-colors">
                    <td class="px-6 py-5 align-middle">
                        <div class="flex flex-col">
                            <span class="text-xs font-black text-slate-800">{{ backup.school_name }}</span>
                            <span class="text-[9px] text-slate-400 font-bold uppercase tracking-tighter">{{ backup.school_id }}</span>
                        </div>
                    </td>
                    <td class="px-6 py-5 align-middle font-mono text-[11px] text-[#157f7f] font-black">
                        {{ backup.name }}
                    </td>
                    <td class="px-6 py-5 align-middle text-xs font-bold text-slate-500">
                        {{ formatSize(backup.size) }}
                    </td>
                    <td class="px-6 py-5 align-middle text-center text-xs font-black text-slate-700 font-mono tracking-tighter">
                        {{ backup.date }}
                    </td>
                    <td class="px-6 py-5 align-middle text-left">
                        <button @click="downloadBackup(backup)" class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-[#e8ded1] text-slate-700 hover:text-[#157f7f] hover:border-[#157f7f] rounded-xl text-xs font-black transition-all shadow-sm">
                            <CloudArrowDownIcon class="h-4 w-4" />
                            تحميل النسخة
                        </button>
                    </td>
                </tr>
                <tr v-if="filteredBackups.length === 0 && !isLoading">
                    <td colspan="5" class="px-6 py-20 text-center">
                        <div class="flex flex-col items-center opacity-30">
                            <CloudArrowDownIcon class="h-16 w-16 text-slate-200 mb-4" />
                            <p class="text-xs font-black text-slate-400 uppercase tracking-widest">لا توجد نسخ احتياطية متوفرة</p>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
  </div>
</template>
