<script setup>
import { ref, onMounted } from 'vue'
import { TicketIcon, ChatBubbleLeftEllipsisIcon, CheckCircleIcon, XMarkIcon, PaperAirplaneIcon, EnvelopeIcon } from '@heroicons/vue/24/outline'
import api from '../axios'

const tickets = ref([])
const selectedTicket = ref(null)
const isLoading = ref(true)
const replyMessage = ref('')
const isSubmitting = ref(false)

const fetchTickets = async () => {
  try {
    isLoading.value = true
    const response = await api.get('/api/support-tickets')
    tickets.value = response.data
  } catch (err) {
    console.error('Error fetching tickets', err)
  } finally {
    isLoading.value = false
  }
}

const openTicket = async (id) => {
  try {
    const response = await api.get(`/api/support-tickets/${id}`)
    selectedTicket.value = response.data
  } catch (err) {
    alert('تعذّر فتح التذكرة')
  }
}

const sendReply = async () => {
    if(!replyMessage.value.trim() || isSubmitting.value) return
    isSubmitting.value = true
    try {
        const response = await api.post(`/api/support-tickets/${selectedTicket.value.id}/reply`, {
            message: replyMessage.value
        })
        selectedTicket.value.messages.push(response.data)
        replyMessage.value = ''
    } catch (err) {
        alert('حدث خطأ أثناء إرسال الرد')
    } finally {
        isSubmitting.value = false
    }
}

const changeStatus = async (status) => {
    try {
        await api.put(`/api/support-tickets/${selectedTicket.value.id}/status`, { status })
        selectedTicket.value.status = status
        fetchTickets() // refresh list silently
    } catch (err) {
        alert('حدث خطأ أثناء تغيير الحالة')
    }
}

const getStatusColor = (status) => {
    if(status === 'open') return 'bg-amber-100 text-amber-700 border-amber-200'
    if(status === 'pending') return 'bg-blue-100 text-blue-700 border-blue-200'
    return 'bg-slate-100 text-slate-500 border-slate-200'
}

const getPriorityColor = (priority) => {
    if(priority === 'high') return 'text-red-600 bg-red-50'
    if(priority === 'medium') return 'text-amber-600 bg-amber-50'
    return 'text-slate-600 bg-slate-50'
}

const getStatusLabel = (status) => {
    if(status === 'open') return 'مفتوحة'
    if(status === 'pending') return 'قيد المراجعة'
    return 'مغلقة'
}

onMounted(() => {
  fetchTickets()
})
</script>

<template>
  <div class="h-full flex flex-col pt-2 animate-fade-in pb-10" dir="rtl">
    
    <!-- Header -->
    <div class="flex justify-between items-center mb-8">
      <div>
        <h1 class="text-3xl font-black text-slate-800 tracking-tight">نظام التذاكر (Support)</h1>
        <p class="text-sm font-bold text-slate-400 mt-2">متابعة طلبات الدعم الفني والرسائل الواردة من المدارس.</p>
      </div>
    </div>

    <!-- Main Content -->
    <div class="flex flex-col lg:flex-row gap-6 h-[800px]">
      
      <!-- Tickets List -->
      <div class="w-full lg:w-1/3 bg-white rounded-[24px] border border-[#f0e8df] shadow-sm flex flex-col overflow-hidden">
        <div class="p-5 border-b border-slate-100 bg-[#fafaf9] flex justify-between items-center">
            <h2 class="text-sm font-black text-slate-800">صندوق الوارد</h2>
            <span class="bg-[#157f7f] text-white text-[10px] font-black px-2.5 py-1 rounded-full">{{ tickets.length }}</span>
        </div>
        
        <div class="flex-1 overflow-y-auto p-2 space-y-2 relative">
            <div v-if="isLoading" class="absolute inset-0 flex justify-center items-center bg-white/50 backdrop-blur-sm z-10">
                <div class="animate-spin rounded-full h-8 w-8 border-3 border-[#fcf9f4] border-t-[#157f7f]"></div>
            </div>

            <div v-if="tickets.length === 0 && !isLoading" class="flex flex-col items-center justify-center py-20 text-slate-400">
                <TicketIcon class="h-12 w-12 opacity-20 mb-3" />
                <p class="text-xs font-bold">لا توجد تذاكر حالياً</p>
            </div>

            <button v-for="ticket in tickets" :key="ticket.id" @click="openTicket(ticket.id)" class="w-full text-right p-4 rounded-2xl border transition-all hover:bg-slate-50" :class="selectedTicket?.id === ticket.id ? 'border-[#157f7f] bg-slate-50 ring-1 ring-[#157f7f]/20' : 'border-slate-100 border-transparent'">
                <div class="flex justify-between items-start mb-2">
                    <span :class="getStatusColor(ticket.status)" class="px-2 py-0.5 rounded-md text-[10px] font-black uppercase tracking-widest border">{{ getStatusLabel(ticket.status) }}</span>
                    <span class="text-[10px] font-bold text-slate-400 font-mono">{{ new Date(ticket.created_at).toLocaleDateString() }}</span>
                </div>
                <h3 class="text-sm font-black text-slate-800 mb-1 truncate">{{ ticket.subject }}</h3>
                <p class="text-xs font-bold text-slate-500 truncate">{{ ticket.tenant?.name || 'مدرسة غير معروفة' }}</p>
            </button>
        </div>
      </div>

      <!-- Ticket Details -->
      <div class="w-full lg:w-2/3 bg-white rounded-[24px] border border-[#f0e8df] shadow-sm flex flex-col overflow-hidden">
         <div v-if="!selectedTicket" class="flex-1 flex flex-col items-center justify-center text-slate-400">
            <ChatBubbleLeftEllipsisIcon class="h-20 w-20 opacity-10 mb-6" />
            <h3 class="text-lg font-black text-slate-600 mb-2">الرجاء اختيار تذكرة</h3>
            <p class="text-sm font-bold">اضغط على إحدى التذاكر في القائمة لعرض المحادثة والرد عليها.</p>
         </div>

         <template v-else>
            <!-- Details Header -->
            <div class="p-6 border-b border-slate-100 bg-[#fafaf9] flex flex-col sm:flex-row justify-between items-start gap-4">
               <div>
                  <h2 class="text-xl font-black text-slate-800 tracking-tight">{{ selectedTicket.subject }}</h2>
                  <div class="flex items-center gap-3 mt-2 text-xs font-bold text-slate-500">
                      <span class="flex items-center gap-1"><EnvelopeIcon class="h-4 w-4" /> {{ selectedTicket.tenant?.name }}</span>
                      <span class="px-2 py-0.5 rounded-md uppercase tracking-wider" :class="getPriorityColor(selectedTicket.priority)">{{ selectedTicket.priority }}</span>
                  </div>
               </div>
               <div class="flex items-center gap-2">
                   <button v-if="selectedTicket.status !== 'open'" @click="changeStatus('open')" class="px-3 py-1.5 rounded-lg border border-slate-200 text-xs font-black text-slate-600 hover:bg-slate-50 transition-colors">إعادة فتح</button>
                   <button v-if="selectedTicket.status !== 'pending'" @click="changeStatus('pending')" class="px-3 py-1.5 rounded-lg border border-blue-200 text-xs font-black text-blue-600 hover:bg-blue-50 transition-colors">قيد المراجعة</button>
                   <button v-if="selectedTicket.status !== 'closed'" @click="changeStatus('closed')" class="px-3 py-1.5 rounded-lg border border-red-200 text-xs font-black text-red-600 hover:bg-red-50 transition-colors flex items-center gap-1">
                       <CheckCircleIcon class="h-4 w-4" /> إغلاق التذكرة
                   </button>
               </div>
            </div>

            <!-- Messages Area -->
            <div class="flex-1 overflow-y-auto p-6 space-y-6 bg-slate-50/50">
                <div v-for="msg in selectedTicket.messages" :key="msg.id" class="flex flex-col max-w-[80%]" :class="msg.is_admin_reply ? 'mr-auto items-end' : 'ml-auto items-start'">
                    <div class="flex items-end gap-2 mb-1" :class="{ 'flex-row-reverse': !msg.is_admin_reply }">
                        <span class="text-[10px] font-black text-slate-400">{{ msg.is_admin_reply ? 'الدعم الفني (Super Admin)' : (msg.user?.name || 'مدير المدرسة') }}</span>
                        <span class="text-[9px] font-bold text-slate-300">{{ new Date(msg.created_at).toLocaleTimeString('en-GB', {hour: '2-digit', minute:'2-digit'}) }}</span>
                    </div>
                    <div class="p-4 rounded-2xl text-sm leading-relaxed" :class="msg.is_admin_reply ? 'bg-[#157f7f] text-white rounded-tr-none' : 'bg-white border text-slate-700 border-[#f0e8df] shadow-sm rounded-tl-none'">
                        {{ msg.message }}
                    </div>
                </div>
            </div>

            <!-- Reply Area -->
            <div class="p-4 border-t border-slate-100 bg-white" v-if="selectedTicket.status !== 'closed'">
                <form @submit.prevent="sendReply" class="flex gap-3">
                    <input v-model="replyMessage" type="text" placeholder="اكتب ردك هنا..." class="flex-1 bg-slate-50 border-slate-200 text-slate-800 text-sm rounded-xl focus:ring-[#157f7f] focus:border-[#157f7f] block p-3 font-bold transition-colors">
                    <button type="submit" :disabled="!replyMessage.trim() || isSubmitting" class="bg-[#157f7f] text-white w-12 h-12 rounded-xl flex items-center justify-center hover:bg-[#126b6b] transition-colors disabled:opacity-50">
                        <PaperAirplaneIcon class="h-5 w-5 -rotate-90 transform" />
                    </button>
                </form>
            </div>
            <div v-else class="p-4 border-t border-slate-100 bg-slate-50 text-center">
                <p class="text-xs font-bold text-slate-500">هذه التذكرة مغلقة. لا يمكنك الرد عليها حالياً.</p>
            </div>
         </template>
      </div>

    </div>
  </div>
</template>
