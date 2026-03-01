<script setup>
import { ref, onMounted } from 'vue'
import api from '../axios'
import { 
  AcademicCapIcon, 
  CreditCardIcon, 
  CheckBadgeIcon,
  ClockIcon,
  ArrowUpRightIcon,
  ChartBarIcon,
  ExclamationTriangleIcon,
  ArrowRightOnRectangleIcon,
  ChartPieIcon,
  UsersIcon
} from '@heroicons/vue/24/outline'

import { Bar, Doughnut } from 'vue-chartjs'
import { Chart as ChartJS, Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale, ArcElement } from 'chart.js'

ChartJS.register(Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale, ArcElement)

const stats = ref(null)
const schools = ref([])
const isLoading = ref(true)
const error = ref(null)

const chartData = ref({
  labels: [],
  datasets: [{ data: [] }]
})

const doughnutChartData = ref({
  labels: [],
  datasets: [{ data: [], backgroundColor: [] }]
})

const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: { display: false },
    tooltip: {
      backgroundColor: '#1e293b',
      padding: 12,
      titleFont: { family: 'Tajawal, sans-serif', size: 13 },
      bodyFont: { family: 'Tajawal, sans-serif', size: 14, weight: 'bold' },
      displayColors: false,
      callbacks: {
        label: (context) => `${context.parsed.y} مدرسة`
      }
    }
  },
  scales: {
    y: {
      beginAtZero: true,
      grid: { color: '#f0e8df', drawBorder: false },
      ticks: { precision: 0, font: { family: 'Tajawal, sans-serif' }, color: '#94a3b8' }
    },
    x: {
      grid: { display: false },
      ticks: { font: { family: 'Tajawal, sans-serif', weight: 'bold' }, color: '#64748b' }
    }
  }
}

onMounted(async () => {
  try {
    const [statsRes, schoolsRes] = await Promise.all([
      api.get('/api/dashboard/stats'),
      api.get('/api/schools?limit=5')
    ])
    stats.value = statsRes.data
    schools.value = schoolsRes.data.slice(0, 5)

    if (stats.value.chart_data) {
      chartData.value = {
        labels: stats.value.chart_data.map(d => d.month),
        datasets: [
          {
            label: 'المدارس الجديدة',
            backgroundColor: '#157f7f',
            hoverBackgroundColor: '#0f6060',
            borderRadius: 6,
            data: stats.value.chart_data.map(d => d.count)
          }
        ]
      }
    }

    if (stats.value.schools_by_plan) {
      const colors = ['#157f7f', '#f59e0b', '#3b82f6', '#10b981', '#8b5cf6', '#64748b']
      doughnutChartData.value = {
        labels: stats.value.schools_by_plan.map(d => d.plan_name),
        datasets: [{
          data: stats.value.schools_by_plan.map(d => d.count),
          backgroundColor: colors.slice(0, stats.value.schools_by_plan.length),
          borderWidth: 0,
          hoverOffset: 4
        }]
      }
    }
  } catch (err) {
    error.value = 'الرجاء التحقق من الاتصال، لم يتم تحميل بيانات الإحصائيات.'
  } finally {
    isLoading.value = false
  }
})

const doughnutOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: { position: 'bottom', labels: { font: { family: 'Tajawal, sans-serif', size: 12 }, padding: 20 } },
    tooltip: { backgroundColor: '#1e293b', padding: 12, titleFont: { family: 'Tajawal, sans-serif' }, bodyFont: { family: 'Tajawal, sans-serif', weight: 'bold' }, displayColors: true }
  },
  cutout: '70%'
}
</script>

<template>
  <div class="space-y-8" dir="rtl">
    
    <!-- Welcome Header -->
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
      <div>
        <h1 class="text-2xl font-black text-slate-800 tracking-tight">نظرة عامة على النظام</h1>
        <p class="mt-1 text-sm text-slate-500 font-bold leading-relaxed max-w-xl">متابعة أداء المنصات التعليمية والنمو اللحظي لكافة المدارس المشتركة.</p>
      </div>
    </div>

    <!-- Error state -->
    <div v-if="error" class="bg-[#e64040]/5 border border-[#e64040]/10 text-[#e64040] p-4 rounded-2xl flex items-center gap-3 animate-shake">
        <div class="h-8 w-8 flex-shrink-0 bg-white rounded-lg flex items-center justify-center shadow-sm">
           <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
        </div>
        <span class="font-bold text-xs">{{ error }}</span>
    </div>

    <!-- Loading skeleton -->
    <div v-if="isLoading" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <div v-for="i in 3" :key="i" class="h-32 bg-white rounded-3xl border border-[#f0e8df] animate-pulse"></div>
    </div>

    <div v-else-if="stats" class="space-y-8">
      
      <!-- Top Alerts Level -->
      <div v-if="(stats.alerts.suspended_schools.length > 0) || (stats.alerts.capacity_alerts.length > 0)" class="flex flex-col gap-4 mb-6">
         <!-- Suspended Schools Alert -->
         <div v-if="stats.alerts.suspended_schools.length > 0" class="bg-amber-50 border border-amber-200 p-4 rounded-2xl flex items-start gap-4">
            <ExclamationTriangleIcon class="h-6 w-6 text-amber-500 mt-1 flex-shrink-0" />
            <div>
               <h3 class="text-sm font-black text-amber-800 tracking-tight">تنبيه: يوجد {{ stats.alerts.suspended_schools.length }} مدرسة موقوفة النظام حالياً.</h3>
               <p class="text-[11px] font-bold text-amber-600 mt-1">المدارس الموقوفة: 
                 <span v-for="(sch, i) in stats.alerts.suspended_schools" :key="sch.id">{{ sch.name }}<span v-if="i !== stats.alerts.suspended_schools.length - 1">، </span></span>
               </p>
            </div>
         </div>
         
         <!-- Capacity Limits Alert -->
         <div v-if="stats.alerts.capacity_alerts.length > 0" class="bg-rose-50 border border-rose-200 p-4 rounded-2xl flex items-start gap-4">
            <ExclamationTriangleIcon class="h-6 w-6 text-rose-500 mt-1 flex-shrink-0" />
            <div>
               <h3 class="text-sm font-black text-rose-800 tracking-tight">تنبيه تجاوز السعة: توجد مدارس يتجاوز عدد مستخدميها المسموح به في باقتها!</h3>
               <div class="mt-2 flex flex-col gap-1">
                 <div v-for="sch in stats.alerts.capacity_alerts" :key="sch.id" class="text-[11px] font-bold text-rose-600 bg-rose-100/50 px-2 py-1 rounded inline-block w-fit">
                   مدرسة <span class="font-black">{{ sch.name }}</span> مسجل بها {{ sch.current }} طالب (الحد الأقصى هو {{ sch.max }})
                 </div>
               </div>
            </div>
         </div>
      </div>

      <!-- Stats Cards -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <!-- Schools -->
        <div class="bg-white p-5 rounded-3xl border border-[#f0e8df] shadow-sm group hover:border-[#157f7f]/30 transition-all duration-300">
          <div class="flex items-start justify-between">
            <div class="h-10 w-10 bg-[#fcf9f4] rounded-xl flex items-center justify-center text-[#157f7f] border border-[#f0e8df]">
              <AcademicCapIcon class="h-5 w-5" />
            </div>
            <span class="text-[10px] font-black text-[#157f7f] bg-[#157f7f]/5 px-2 py-0.5 rounded-full uppercase tracking-wider">المدارس</span>
          </div>
          <div class="mt-4">
            <div class="text-3xl font-black text-slate-800">{{ stats.schools.total }}</div>
            <p class="text-[11px] font-bold text-slate-400 mt-1 uppercase tracking-tight">إجمالي المدارس المسجلة</p>
          </div>
          <div class="mt-4 pt-4 border-t border-[#fcf9f4] flex items-center gap-4">
            <div class="flex items-center gap-1.5">
              <div class="h-1.5 w-1.5 rounded-full bg-emerald-500"></div>
              <span class="text-[10px] font-black text-slate-600">{{ stats.schools.active }} نشط</span>
            </div>
            <div class="flex items-center gap-1.5">
              <div class="h-1.5 w-1.5 rounded-full bg-amber-500"></div>
              <span class="text-[10px] font-black text-slate-600">{{ stats.schools.suspended }} متوقف</span>
            </div>
            <div class="flex items-center gap-1.5 flex-1 justify-end">
              <div class="h-1.5 w-1.5 rounded-full bg-[#157f7f]"></div>
              <span class="text-[10px] font-black text-slate-600 font-mono">+{{ stats.schools.new_this_month }} هذا الشهر</span>
            </div>
          </div>
        </div>

        <!-- Teachers -->
        <div class="bg-white p-5 rounded-3xl border border-[#f0e8df] shadow-sm group hover:border-[#f59e0b]/30 transition-all duration-300">
          <div class="flex items-start justify-between">
            <div class="h-10 w-10 bg-[#fcf9f4] rounded-xl flex items-center justify-center text-[#f59e0b] border border-[#f0e8df]">
              <UsersIcon class="h-5 w-5" />
            </div>
            <span class="text-[10px] font-black text-[#f59e0b] bg-[#f59e0b]/5 px-2 py-0.5 rounded-full uppercase tracking-wider">المعلمين</span>
          </div>
          <div class="mt-4">
            <div class="text-3xl font-black text-slate-800">{{ stats.schools.total_teachers || 0 }}</div>
            <p class="text-[11px] font-bold text-slate-400 mt-1 uppercase tracking-tight">إجمالي المعلمين في النظام</p>
          </div>
          <div class="mt-4 pt-4 border-t border-[#fcf9f4] flex flex-col gap-2">
            <div class="flex items-center gap-1.5 opacity-60">
              <span class="text-[9px] font-bold text-slate-600">بناءً على حسابات مبدئية للمدارس النشطة.</span>
            </div>
          </div>
        </div>

        <!-- Subscriptions -->
        <div class="bg-white p-5 rounded-3xl border border-[#f0e8df] shadow-sm group hover:border-[#157f7f]/30 transition-all duration-300">
          <div class="flex items-start justify-between">
            <div class="h-10 w-10 bg-[#fcf9f4] rounded-xl flex items-center justify-center text-[#157f7f] border border-[#f0e8df]">
              <CheckBadgeIcon class="h-5 w-5" />
            </div>
            <span class="text-[10px] font-black text-[#157f7f] bg-[#157f7f]/5 px-2 py-0.5 rounded-full uppercase tracking-wider">الاشتراكات</span>
          </div>
          <div class="mt-4">
            <div class="text-3xl font-black text-slate-800">{{ stats.subscriptions.active }}</div>
            <p class="text-[11px] font-bold text-slate-400 mt-1 uppercase tracking-tight">الاشتراكات الفعالة حالياً</p>
          </div>
          <div class="mt-4 pt-4 border-t border-[#fcf9f4] flex items-center gap-4">
            <div class="flex items-center gap-1.5">
              <div class="h-1.5 w-1.5 rounded-full bg-[#e64040]"></div>
              <span class="text-[10px] font-black text-slate-600">{{ stats.subscriptions.expired }} منتهي</span>
            </div>
          </div>
        </div>

        <!-- Daily Logins replacing the Mock System Usage -->
        <div class="bg-white p-5 rounded-3xl border border-[#f0e8df] shadow-sm group hover:border-[#3b82f6]/30 transition-all duration-300">
          <div class="flex items-start justify-between">
            <div class="h-10 w-10 bg-[#fcf9f4] rounded-xl flex items-center justify-center text-[#3b82f6] border border-[#f0e8df]">
              <ArrowRightOnRectangleIcon class="h-5 w-5" />
            </div>
            <span class="text-[10px] font-black text-[#3b82f6] bg-[#3b82f6]/5 px-2 py-0.5 rounded-full uppercase tracking-wider">عمليات</span>
          </div>
          <div class="mt-4">
            <div class="text-3xl font-black text-slate-800">{{ stats.schools.today_logins || 0 }}</div>
            <p class="text-[11px] font-bold text-slate-400 mt-1 uppercase tracking-tight">تسجيلات الدخول للإدارة اليوم</p>
          </div>
          <div class="mt-4 pt-4 border-t border-[#fcf9f4] flex flex-col gap-2">
            <div class="flex items-center gap-1.5">
              <div class="h-1.5 w-1.5 rounded-full bg-amber-500"></div>
              <span class="text-[10px] font-black text-slate-600">{{ stats.subscriptions.expiring_in_week }} ينتهي خلال أسبوع</span>
            </div>
            <div class="flex items-center gap-1.5">
              <div class="h-1.5 w-1.5 rounded-full bg-blue-500"></div>
              <span class="text-[10px] font-black text-slate-600">{{ stats.subscriptions.expiring_in_month }} ينتهي خلال شهر</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Recent Schools & Activity Section -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Recent Schools Table -->
        <div class="bg-white rounded-3xl border border-[#f0e8df] shadow-sm overflow-hidden">
          <div class="p-5 border-b border-[#f0e8df] flex items-center justify-between">
            <h3 class="text-sm font-black text-slate-800 uppercase tracking-tight">آخر المدارس المسجلة</h3>
            <router-link to="/schools" class="text-[10px] font-black text-[#157f7f] hover:underline flex items-center gap-1">
              عرض الكل
              <ArrowUpRightIcon class="h-3 w-3" />
            </router-link>
          </div>
          <div class="overflow-x-auto">
            <table class="w-full text-right">
              <thead>
                <tr class="bg-[#fcf9f4]">
                  <th class="px-5 py-3 text-[9px] font-black text-slate-400 uppercase tracking-widest">المدرسة</th>
                  <th class="px-5 py-3 text-[9px] font-black text-slate-400 uppercase tracking-widest text-left">التاريخ</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-[#f0e8df]">
                <tr v-for="school in schools" :key="school.id" class="hover:bg-slate-50/50 transition-colors">
                  <td class="px-5 py-3">
                    <div class="flex flex-col">
                      <span class="text-xs font-bold text-slate-700">{{ school.name }}</span>
                      <span class="text-[9px] font-bold text-slate-400 font-mono">{{ school.id }}</span>
                    </div>
                  </td>
                  <td class="px-5 py-3 text-left">
                    <span class="text-[10px] font-bold text-slate-500">{{ new Date(school.created_at).toLocaleDateString('ar-EG') }}</span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Growth Chart -->
        <div class="bg-white rounded-[32px] p-6 border border-[#f0e8df] shadow-sm flex flex-col min-h-[300px]">
          <div class="flex items-center justify-between mb-6">
            <h3 class="text-sm font-black text-slate-800 uppercase tracking-tight flex items-center gap-2">
              <ChartBarIcon class="h-5 w-5 text-[#157f7f]" />
              نمو المدراس آخر 6 أشهر
            </h3>
          </div>
          <div class="relative flex-1 w-full h-full min-h-[250px]">
             <Bar v-if="chartData.labels.length > 0" :data="chartData" :options="chartOptions" />
             <div v-else class="absolute inset-0 flex items-center justify-center">
               <span class="text-xs font-bold text-slate-400">جاري تحميل الرسم البياني...</span>
             </div>
          </div>
        </div>

        <!-- Schools by Plan Pie Chart -->
        <div class="bg-white rounded-[32px] p-6 border border-[#f0e8df] shadow-sm flex flex-col min-h-[300px] lg:col-span-2">
          <div class="flex items-center justify-between mb-2">
            <h3 class="text-sm font-black text-slate-800 uppercase tracking-tight flex items-center gap-2">
              <ChartPieIcon class="h-5 w-5 text-[#f59e0b]" />
              توزيع المدارس حسب الباقات
            </h3>
          </div>
          <div class="relative flex-1 w-full h-full min-h-[350px] pb-4">
             <Doughnut v-if="doughnutChartData.labels.length > 0" :data="doughnutChartData" :options="doughnutOptions" />
             <div v-else class="absolute inset-0 flex items-center justify-center">
               <span class="text-xs font-bold text-slate-400">لا توجد بيانات للباقات حالياً</span>
             </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
