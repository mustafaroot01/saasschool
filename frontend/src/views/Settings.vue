<script setup>
import { ref, onMounted } from 'vue'
import { 
  CogIcon, 
  ShieldCheckIcon, 
  GlobeAltIcon, 
  BellIcon,
  SwatchIcon,
  DevicePhoneMobileIcon,
  DocumentTextIcon
} from '@heroicons/vue/24/outline'
import api from '../axios'

const isSaving = ref(false)
const successMessage = ref('')
const activeTab = ref('branding')

const settings = ref({
  siteName: 'ORNAMENTAL SAAS',
  contactEmail: 'admin@saas.com',
  maintenanceMode: false,
  allowRegistration: true,
  primaryColor: '#157f7f',
  mobile_app_version: '1.0.0',
  mobile_app_force_update: '0',
  invoice_title: '',
  invoice_address: '',
  invoice_phone: '',
  invoice_notes: ''
})

const fetchSettings = async () => {
    try {
        const response = await api.get('/api/settings')
        const data = response.data
        settings.value.mobile_app_version = data.mobile_app_version || '1.0.0'
        settings.value.mobile_app_force_update = data.mobile_app_force_update || '0'
        settings.value.invoice_title = data.invoice_title || ''
        settings.value.invoice_address = data.invoice_address || ''
        settings.value.invoice_phone = data.invoice_phone || ''
        settings.value.invoice_notes = data.invoice_notes || ''
    } catch (err) {
        console.error('Failed to fetch settings', err)
    }
}

const saveSettings = async () => {
  isSaving.value = true
  try {
    await api.put('/api/settings', {
        mobile_app_version: settings.value.mobile_app_version,
        mobile_app_force_update: settings.value.mobile_app_force_update,
        invoice_title: settings.value.invoice_title,
        invoice_address: settings.value.invoice_address,
        invoice_phone: settings.value.invoice_phone,
        invoice_notes: settings.value.invoice_notes
    })
    successMessage.value = 'تم حفظ الإعدادات بنجاح.'
    setTimeout(() => successMessage.value = '', 3000)
  } catch (err) {
    alert('حدث خطأ أثناء حفظ الإعدادات')
  } finally {
    isSaving.value = false
  }
}

onMounted(fetchSettings)
</script>

<template>
  <div class="space-y-6" dir="rtl">
    
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
      <div>
        <h1 class="text-2xl font-black text-slate-800 tracking-tight">إعدادات النظام</h1>
        <p class="mt-1 text-sm text-slate-500 font-bold leading-relaxed max-w-xl">التحكم في الهوية البصرية، والخيارات التقنية المتقدمة للمنصة.</p>
      </div>
      <button @click="saveSettings" :disabled="isSaving" type="button" class="inline-flex items-center justify-center px-5 py-3 bg-[#157f7f] text-white rounded-xl font-black text-sm shadow-md shadow-[#157f7f]/20 hover:bg-[#116666] transition-all disabled:opacity-50">
        <span v-if="!isSaving">حفظ التغييرات</span>
        <span v-else>جاري الحفظ...</span>
      </button>
    </div>

    <!-- Success Message -->
    <transition name="fade">
      <div v-if="successMessage" class="bg-[#157f7f]/10 border border-[#157f7f]/20 text-[#157f7f] p-3 rounded-xl text-center font-bold text-xs">
        {{ successMessage }}
      </div>
    </transition>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
      <!-- Sidebar Navigation for Settings (Internal) -->
      <div class="lg:col-span-1 space-y-2">
        <button @click="activeTab = 'branding'" :class="activeTab === 'branding' ? 'bg-white border-[#157f7f]/20 text-[#157f7f]' : 'bg-white/50 border-transparent text-slate-400 hover:bg-white hover:text-[#157f7f]'" class="w-full flex items-center gap-3 px-4 py-3 rounded-2xl border font-black text-sm shadow-sm transition-all text-right">
           <SwatchIcon class="h-5 w-5" />
           الهوية البصرية
        </button>
        <button @click="activeTab = 'mobile'" :class="activeTab === 'mobile' ? 'bg-white border-[#157f7f]/20 text-[#157f7f]' : 'bg-white/50 border-transparent text-slate-400 hover:bg-white hover:text-[#157f7f]'" class="w-full flex items-center gap-3 px-4 py-3 rounded-2xl border font-black text-sm transition-all text-right">
           <DevicePhoneMobileIcon class="h-5 w-5" />
           تطبيق الموبايل
        </button>
        <button @click="activeTab = 'invoice'" :class="activeTab === 'invoice' ? 'bg-white border-[#157f7f]/20 text-[#157f7f]' : 'bg-white/50 border-transparent text-slate-400 hover:bg-white hover:text-[#157f7f]'" class="w-full flex items-center gap-3 px-4 py-3 rounded-2xl border font-black text-sm transition-all text-right">
           <DocumentTextIcon class="h-5 w-5" />
           إعدادات الفاتورة
        </button>
        <button @click="activeTab = 'security'" :class="activeTab === 'security' ? 'bg-white border-[#157f7f]/20 text-[#157f7f]' : 'bg-white/50 border-transparent text-slate-400 hover:bg-white hover:text-[#157f7f]'" class="w-full flex items-center gap-3 px-4 py-3 rounded-2xl border font-black text-sm transition-all text-right">
           <ShieldCheckIcon class="h-5 w-5" />
           الأمان والحماية
        </button>
        <button @click="activeTab = 'dns'" :class="activeTab === 'dns' ? 'bg-white border-[#157f7f]/20 text-[#157f7f]' : 'bg-white/50 border-transparent text-slate-400 font-black text-sm hover:bg-white hover:text-[#157f7f] transition-all text-right'">
           <GlobeAltIcon class="h-5 w-5" />
           تهيئة النطاقات (DNS)
        </button>
        <button @click="activeTab = 'notifications'" :class="activeTab === 'notifications' ? 'bg-white border-[#157f7f]/20 text-[#157f7f]' : 'bg-white/50 border-transparent text-slate-400 font-black text-sm hover:bg-white hover:text-[#157f7f] transition-all text-right'">
           <BellIcon class="h-5 w-5" />
           إشعارات النظام
        </button>
      </div>

      <div class="lg:col-span-2 space-y-6 animate-fade-in-up">
        <!-- Branding Tab Content -->
        <div v-if="activeTab === 'branding'" class="bg-white p-8 rounded-[32px] border border-[#f0e8df] shadow-sm space-y-6">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="space-y-1">
              <label class="block text-[11px] font-black text-slate-400 mr-1 uppercase">اسم المنصة</label>
              <input v-model="settings.siteName" type="text" class="block w-full px-4 py-2.5 bg-[#fcf9f4] border border-[#e8ded1] rounded-xl text-sm outline-none focus:border-[#157f7f] font-bold" />
            </div>
            <div class="space-y-1">
              <label class="block text-[11px] font-black text-slate-400 mr-1 uppercase">بريد التواصل التقني</label>
              <input v-model="settings.contactEmail" type="email" class="block w-full px-4 py-2.5 bg-[#fcf9f4] border border-[#e8ded1] rounded-xl text-sm outline-none focus:border-[#157f7f] font-mono" />
            </div>
          </div>

          <div class="space-y-4 pt-4 border-t border-[#fcf9f4]">
            <div class="flex items-center justify-between p-4 bg-[#fcf9f4] rounded-2xl border border-[#e8ded1]">
              <div>
                <p class="text-xs font-black text-slate-700">وضع الصيانة</p>
                <p class="text-[10px] font-bold text-slate-400">إيقاف ظهور المنصة للمدارس مؤقتاً لأغراض التحديث.</p>
              </div>
              <label class="relative inline-flex items-center cursor-pointer">
                <input type="checkbox" v-model="settings.maintenanceMode" class="sr-only peer">
                <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:right-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#157f7f]"></div>
              </label>
            </div>

            <div class="flex items-center justify-between p-4 bg-[#fcf9f4] rounded-2xl border border-[#e8ded1]">
              <div>
                <p class="text-xs font-black text-slate-700">التسجيل المباشر</p>
                <p class="text-[10px] font-bold text-slate-400">السماح للمدارس الجديدة بطلب التسجيل عبر الصفحة الرئيسية.</p>
              </div>
              <label class="relative inline-flex items-center cursor-pointer">
                <input type="checkbox" v-model="settings.allowRegistration" class="sr-only peer">
                <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:right-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#157f7f]"></div>
              </label>
            </div>
          </div>
        </div>

        <!-- Mobile App Tab Content -->
        <div v-if="activeTab === 'mobile'" class="bg-white p-8 rounded-[32px] border border-[#f0e8df] shadow-sm space-y-8">
           <div class="flex items-center gap-4 p-4 bg-blue-50 border border-blue-100 rounded-2xl">
              <div class="h-12 w-12 bg-white rounded-xl flex items-center justify-center shadow-sm">
                 <DevicePhoneMobileIcon class="h-7 w-7 text-blue-500" />
              </div>
              <div>
                 <h3 class="text-sm font-black text-blue-900 leading-tight">إعدادات التطبيق الموحد</h3>
                 <p class="text-[10px] font-bold text-blue-600 mt-1 leading-relaxed">بما أن جميع الطلاب والمدارس يستخدمون تطبيقاً واحداً، فإن هذه الإعدادات تسري على مستوى النظام بالكامل.</p>
              </div>
           </div>

           <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
              <div class="space-y-2">
                 <label class="block text-[11px] font-black text-slate-400 uppercase tracking-widest mr-1">إصدار التطبيق (App Version)</label>
                 <input v-model="settings.mobile_app_version" type="text" placeholder="e.g. 2.1.0" class="block w-full px-4 py-3 bg-[#fcf9f4] border border-[#e8ded1] rounded-2xl text-sm outline-none focus:border-[#157f7f] font-mono font-bold" />
                 <p class="text-[10px] text-slate-400 font-bold px-1">هذا الإصدار هو المرجع الذي يعتمد عليه النظام لمعرفة توفر تحديث.</p>
              </div>

              <div class="space-y-2">
                 <label class="block text-[11px] font-black text-slate-400 uppercase tracking-widest mr-1">حالة التحديث</label>
                 <div class="flex items-center justify-between px-5 py-3 bg-[#fcf9f4] border border-[#e8ded1] rounded-2xl">
                    <span class="text-xs font-black text-slate-700">تحديث إجباري</span>
                    <label class="relative inline-flex items-center cursor-pointer">
                       <input type="checkbox" v-model="settings.mobile_app_force_update" :true-value="'1'" :false-value="'0'" class="sr-only peer">
                       <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:right-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#157f7f]"></div>
                    </label>
                 </div>
                 <p class="text-[10px] text-slate-400 font-bold px-1">عند التفعيل، سيُطالب جميع مستخدمي الإصدارات الأقدم بالتحديث فوراً.</p>
              </div>
           </div>
        </div>

        <!-- Invoice Settings Tab Content -->
        <div v-if="activeTab === 'invoice'" class="bg-white p-8 rounded-[32px] border border-[#f0e8df] shadow-sm space-y-8">
           <div class="flex items-center gap-4 p-4 bg-amber-50 border border-amber-100 rounded-2xl">
              <div class="h-12 w-12 bg-white rounded-xl flex items-center justify-center shadow-sm">
                 <DocumentTextIcon class="h-7 w-7 text-amber-500" />
              </div>
              <div>
                 <h3 class="text-sm font-black text-amber-900 leading-tight">معلومات الفواتير (IQD)</h3>
                 <p class="text-[10px] font-bold text-amber-600 mt-1 leading-relaxed">تظهر هذه المعلومات في أعلى وأسفل الفاتورة المطبوعة للمدارس.</p>
              </div>
           </div>

           <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
              <div class="space-y-2">
                 <label class="block text-[11px] font-black text-slate-400 uppercase tracking-widest mr-1">عنوان الفاتورة / اسم المكتب</label>
                 <input v-model="settings.invoice_title" type="text" placeholder="مثال: شركة الحلول البرمجية" class="block w-full px-4 py-3 bg-[#fcf9f4] border border-[#e8ded1] rounded-2xl text-sm outline-none focus:border-[#157f7f] font-bold" />
              </div>
              <div class="space-y-2">
                 <label class="block text-[11px] font-black text-slate-400 uppercase tracking-widest mr-1">اسم العملة</label>
                 <input value="دينار عراقي (IQD)" disabled type="text" class="block w-full px-4 py-3 bg-slate-50 border border-[#e8ded1] rounded-2xl text-sm text-slate-500 font-bold cursor-not-allowed" />
              </div>
              <div class="space-y-2">
                 <label class="block text-[11px] font-black text-slate-400 uppercase tracking-widest mr-1">رقم الهاتف</label>
                 <input v-model="settings.invoice_phone" type="text" class="block w-full px-4 py-3 bg-[#fcf9f4] border border-[#e8ded1] rounded-2xl text-sm outline-none focus:border-[#157f7f] font-mono font-bold" />
              </div>
              <div class="space-y-2">
                 <label class="block text-[11px] font-black text-slate-400 uppercase tracking-widest mr-1">العنوان</label>
                 <input v-model="settings.invoice_address" type="text" class="block w-full px-4 py-3 bg-[#fcf9f4] border border-[#e8ded1] rounded-2xl text-sm outline-none focus:border-[#157f7f] font-bold" />
              </div>
              <div class="md:col-span-2 space-y-2">
                 <label class="block text-[11px] font-black text-slate-400 uppercase tracking-widest mr-1">ملاحظات أسفل الفاتورة</label>
                 <textarea v-model="settings.invoice_notes" rows="3" class="block w-full px-4 py-3 bg-[#fcf9f4] border border-[#e8ded1] rounded-2xl text-sm outline-none focus:border-[#157f7f] font-bold"></textarea>
              </div>
           </div>
        </div>

        <!-- Security, DNS, Notifications (Placeholders) -->
        <div v-if="['security', 'dns', 'notifications'].includes(activeTab)" class="bg-white p-20 rounded-[32px] border border-[#f0e8df] border-dashed shadow-sm flex flex-col items-center justify-center text-center">
            <div class="h-16 w-16 bg-slate-50 flex items-center justify-center rounded-2xl mb-4 group opacity-50">
                <CogIcon class="h-8 w-8 text-slate-300 group-hover:text-[#157f7f] transition-colors" />
            </div>
            <h3 class="text-slate-400 font-black text-sm uppercase tracking-widest leading-loose">قريباً في التحديثات القادمة</h3>
            <p class="text-[10px] text-slate-300 font-bold mt-2">هذا القسم قيد البرمجة ليتم تفعيله لاحقاً.</p>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active {
  transition: opacity 0.5s ease;
}
.fade-enter-from, .fade-leave-to {
  opacity: 0;
}
</style>
