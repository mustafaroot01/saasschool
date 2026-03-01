<script setup>
import { ref, onMounted, computed } from 'vue'
import { 
  CreditCardIcon, 
  MagnifyingGlassIcon, 
  FunnelIcon,
  TicketIcon,
  CalendarDaysIcon,
  PrinterIcon,
  ArrowDownTrayIcon
} from '@heroicons/vue/24/outline'
import api from '../axios'

const history = ref([])
const isLoading = ref(false)
const searchQuery = ref('')
const filterStatus = ref('all')
const settings = ref({
    invoice_title: '',
    invoice_address: '',
    invoice_phone: '',
    invoice_notes: ''
})

const fetchSettings = async () => {
    try {
        const response = await api.get('/api/settings')
        settings.value = response.data
    } catch (err) {
        console.error('Failed to fetch settings', err)
    }
}

const fetchAllHistory = async () => {
    isLoading.value = true
    try {
        const response = await api.get('/api/all-subscriptions-history')
        history.value = response.data
    } catch (err) {
        console.error('Failed to fetch global subscription history', err)
    } finally {
        isLoading.value = false
    }
}

const filteredHistory = computed(() => {
    let result = history.value

    if (filterStatus.value !== 'all') {
        result = result.filter(h => h.payment_status === filterStatus.value)
    }

    if (searchQuery.value) {
        const q = searchQuery.value.toLowerCase()
        result = result.filter(h => 
            (h.tenant?.name || '').toLowerCase().includes(q) || 
            (h.plan?.name || '').toLowerCase().includes(q) ||
            (h.invoice_number || '').toLowerCase().includes(q)
        )
    }

    return result
})

// Calculate summary of latest statuses per school
const latestStatuses = computed(() => {
    const schools = {}
    
    // Assuming history is sorted newest first, or we can just sort it
    const sorted = [...history.value].sort((a, b) => new Date(b.created_at) - new Date(a.created_at))
    
    sorted.forEach(entry => {
        if (entry.tenant_id && !schools[entry.tenant_id]) {
            schools[entry.tenant_id] = {
                name: entry.tenant?.name || entry.tenant_id,
                status: entry.payment_status
            }
        }
    })
    
    const paidCount = Object.values(schools).filter(s => s.status === 'paid').length
    const unpaidCount = Object.values(schools).length - paidCount
    
    return { paid: paidCount, unpaid: unpaidCount, total: Object.values(schools).length }
})

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('en-US').format(amount) + ' IQD'
}

const formatDate = (date) => {
    if (!date) return '-'
    return new Date(date).toLocaleDateString('en-GB')
}

const showPreviewModal = ref(false)
const selectedEntry = ref(null)

const openPreview = (entry) => {
    selectedEntry.value = entry
    showPreviewModal.value = true
}

const closePreview = () => {
    showPreviewModal.value = false
    selectedEntry.value = null
}

const printInvoice = () => {
    const entry = selectedEntry.value
    if (!entry) return
    
    const printWindow = window.open('', '_blank');
    
    // Check if amount is zero or null to show "Manual Selection"
    const isManual = !entry.amount || entry.amount === 0;
    const amountDisplay = isManual ? 'تحديد يدوي' : formatCurrency(entry.amount);
    
    const html = `
        <!DOCTYPE html>
        <html dir="rtl" lang="ar">
        <head>
            <meta charset="UTF-8">
            <title>فاتورة رقم - ${entry.invoice_number || '---'}</title>
            <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;900&display=swap" rel="stylesheet">
            <style>
                :root {
                    --primary: ${settings.value.primaryColor || '#157f7f'};
                    --secondary: #334155;
                    --text: #1e293b;
                    --text-light: #64748b;
                    --border: #e2e8f0;
                    --bg-light: #f8fafc;
                }
                body { 
                    font-family: 'Cairo', sans-serif; 
                    margin: 0; 
                    padding: 0; 
                    color: var(--text); 
                    background: #fff;
                    line-height: 1.6;
                }
                .invoice-container {
                    max-width: 800px;
                    margin: 0 auto;
                    padding: 30px;
                    background: #fff;
                }
                .header { 
                    display: flex; 
                    justify-content: space-between; 
                    align-items: flex-start;
                    border-bottom: 2px solid var(--primary); 
                    padding-bottom: 20px; 
                    margin-bottom: 30px; 
                }
                .company-details {
                    flex: 1;
                }
                .logo-text { 
                    font-size: 32px; 
                    font-weight: 900; 
                    color: var(--primary); 
                    margin-bottom: 5px;
                    letter-spacing: -0.5px;
                }
                .company-info {
                    font-size: 14px;
                    color: var(--text-light);
                    margin-bottom: 3px;
                }
                .invoice-details-header { 
                    text-align: left; 
                    background: var(--bg-light);
                    padding: 20px;
                    border-radius: 8px;
                    min-width: 200px;
                }
                .invoice-title {
                    font-size: 24px;
                    font-weight: 900;
                    color: var(--secondary);
                    margin: 0 0 10px 0;
                    text-transform: uppercase;
                    letter-spacing: 1px;
                }
                .invoice-meta {
                    display: flex;
                    justify-content: space-between;
                    font-size: 13px;
                    margin-bottom: 5px;
                }
                .invoice-meta strong {
                    color: var(--secondary);
                }
                
                .billing-section {
                    display: flex;
                    justify-content: space-between;
                    margin-bottom: 30px;
                    gap: 30px;
                }
                .billing-box {
                    flex: 1;
                }
                .billing-title {
                    font-size: 12px;
                    font-weight: 700;
                    color: var(--text-light);
                    text-transform: uppercase;
                    letter-spacing: 1px;
                    margin-bottom: 15px;
                    border-bottom: 1px solid var(--border);
                    padding-bottom: 5px;
                }
                .billing-name {
                    font-size: 18px;
                    font-weight: 700;
                    color: var(--text);
                    margin-bottom: 5px;
                }
                .billing-info {
                    font-size: 14px;
                    color: var(--text-light);
                }

                .items-table { 
                    width: 100%; 
                    border-collapse: collapse; 
                    margin-bottom: 30px; 
                }
                .items-table th { 
                    background: var(--bg-light); 
                    text-align: right; 
                    padding: 15px; 
                    font-size: 13px; 
                    font-weight: 700;
                    color: var(--secondary); 
                    border-bottom: 2px solid var(--border);
                }
                .items-table td { 
                    padding: 15px; 
                    font-size: 14px; 
                    border-bottom: 1px solid var(--border);
                    color: var(--text);
                }
                .items-table tr:nth-child(even) td {
                    background-color: #fcfcfc;
                }
                
                .totals-section {
                    display: flex;
                    justify-content: flex-end;
                    margin-bottom: 30px;
                }
                .totals-box {
                    width: 300px;
                }
                .total-row {
                    display: flex;
                    justify-content: space-between;
                    padding: 10px 15px;
                    font-size: 14px;
                    color: var(--text);
                }
                .total-row.grand-total {
                    background: var(--primary);
                    color: #fff;
                    font-size: 18px;
                    font-weight: 900;
                    border-radius: 8px;
                    margin-top: 10px;
                    padding: 15px;
                }
                
                .footer { 
                    margin-top: 30px; 
                    padding-top: 15px;
                    border-top: 1px solid var(--border);
                    font-size: 13px; 
                    color: var(--text-light); 
                }
                .footer-title {
                    font-weight: 700;
                    color: var(--secondary);
                    margin-bottom: 5px;
                }

                .print-actions {
                    padding: 20px;
                    background: var(--bg-light);
                    text-align: center;
                    display: flex;
                    justify-content: center;
                    gap: 15px;
                }
                .btn {
                    padding: 12px 24px;
                    border: none;
                    border-radius: 6px;
                    font-size: 14px;
                    font-weight: 700;
                    font-family: 'Cairo', sans-serif;
                    cursor: pointer;
                    transition: all 0.2s;
                }
                .btn-primary {
                    background: var(--primary);
                    color: white;
                }
                .btn-secondary {
                    background: white;
                    color: var(--text);
                    border: 1px solid var(--border);
                }
                
                @media print { 
                    @page { margin: 0; size: auto; }
                    .print-actions { display: none !important; } 
                    body { background: white; margin: 0; padding: 20px; }
                    .invoice-container { padding: 0; box-shadow: none; max-width: 100%; height: 95vh; overflow: hidden; display: flex; flex-direction: column; justify-content: space-between; }
                    .header, .billing-section, .items-table { flex-shrink: 0; }
                    .footer { padding-bottom: 15px; flex-shrink: 0; }
                    .total-row.grand-total {
                        background: var(--primary) !important;
                        -webkit-print-color-adjust: exact;
                        print-color-adjust: exact;
                        color: white !important;
                    }
                    .items-table th {
                        background: var(--bg-light) !important;
                        -webkit-print-color-adjust: exact;
                        print-color-adjust: exact;
                    }
                    .invoice-details-header {
                        background: var(--bg-light) !important;
                        -webkit-print-color-adjust: exact;
                        print-color-adjust: exact;
                    }
                }
            </style>
        </head>
        <body>
            <div class="print-actions">
                <button class="btn btn-primary" onclick="window.print()">طباعة الفاتورة</button>
                <button class="btn btn-secondary" onclick="window.close()">إغلاق</button>
            </div>
            
            <div class="invoice-container">
                <div class="header">
                    <div class="company-details">
                        <div class="logo-text">${settings.value.invoice_title || 'نظام إدارة المدارس'}</div>
                        <div class="company-info">${settings.value.invoice_address || '---'}</div>
                        <div class="company-info" style="direction: ltr; text-align: right;">${settings.value.invoice_phone || '---'}</div>
                    </div>
                    <div class="invoice-details-header">
                        <h1 class="invoice-title">فاتورة ضريبية</h1>
                        <div class="invoice-meta">
                            <span>رقم الفاتورة:</span>
                            <strong>${entry.invoice_number || '---'}</strong>
                        </div>
                        <div class="invoice-meta">
                            <span>تاريخ الإصدار:</span>
                            <strong>${new Date(entry.created_at).toLocaleDateString('en-GB')}</strong>
                        </div>
                        <div class="invoice-meta">
                            <span>طريقة الدفع:</span>
                            <strong>${entry.payment_status === 'paid' ? 'مدفوع' : 'آجل'}</strong>
                        </div>
                    </div>
                </div>
                
                <div class="billing-section">
                    <div class="billing-box">
                        <div class="billing-title">فاتورة إلى</div>
                        <div class="billing-name">${entry.tenant?.name || '---'}</div>
                        <div class="billing-info">معرف الحساب: ${entry.tenant_id}</div>
                    </div>
                    <div class="billing-box">
                        <div class="billing-title">معلومات الاشتراك</div>
                        <div class="billing-info">تاريخ البدء: ${new Date(entry.created_at).toLocaleDateString('en-GB')}</div>
                        <div class="billing-info">صالح حتى: ${new Date(entry.end_date).toLocaleDateString('en-GB')}</div>
                    </div>
                </div>

                <table class="items-table">
                    <thead>
                        <tr>
                            <th style="width: 5%;">#</th>
                            <th style="width: 45%;">البيان / الوصف</th>
                            <th style="width: 25%; text-align: center;">الباقة</th>
                            <th style="width: 25%; text-align: left;">المبلغ الإجمالي</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>1</td>
                            <td>
                                <strong>تجديد اشتراك النظام السحابي</strong><br>
                                <span style="font-size: 12px; color: var(--text-light);">
                                    ${isManual ? 'تجديد يدوي للنظام وفترة صلاحية مخصصة' : 'رسوم تجديد الخدمة للفترة المحددة'}
                                </span>
                            </td>
                            <td style="text-align: center;">${entry.plan?.name || '---'}</td>
                            <td style="text-align: left; font-weight: bold;">${amountDisplay}</td>
                        </tr>
                    </tbody>
                </table>

                <div class="totals-section">
                    <div class="totals-box">
                        <div class="total-row">
                            <span>المجموع الفرعي:</span>
                            <strong>${amountDisplay}</strong>
                        </div>
                        <div class="total-row">
                            <span>الخصم:</span>
                            <strong>0 IQD</strong>
                        </div>
                        <div class="total-row grand-total">
                            <span>الإجمالي النهائي:</span>
                            <span>${amountDisplay}</span>
                        </div>
                    </div>
                </div>

                <div class="footer">
                    <div class="footer-title">الشروط والأحكام / ملاحظات هامة:</div>
                    <div style="white-space: pre-line;">${settings.value.invoice_notes || 'شكراً لتعاملكم معنا. هذه الفاتورة صدرت آلياً من النظام ولا تحتاج إلى توقيع أو ختم.'}</div>
                </div>
            </div>
        </body>
        </html>
    `;
    
    printWindow.document.write(html);
    printWindow.document.close();
}

const resetHistory = async () => {
    if (!confirm('هل أنت متأكد من رغبتك في مسح كافة سجل الاشتراكات؟ هذا الإجراء لا يمكن التراجع عنه.')) return
    
    // extra safety check
    const code = prompt('لتأكيد عملية الحذف، يرجى كتابة كلمة "حذف" في المربع أدناه:');
    if (code !== 'حذف') {
        alert('تم إلغاء عملية الحذف لأن الكلمة غير متطابقة.');
        return;
    }

    try {
        await api.delete('/api/all-subscriptions-history');
        history.value = [];
        alert('تم مسح السجل بنجاح.');
    } catch (err) {
        alert('تعذّر مسح السجل.');
    }
}

const togglePaymentStatus = async (entry) => {
    if (entry.payment_status === 'paid') return; // Only allow unpaid to paid (or adjust as needed)
    
    if (!confirm('هل أنت متأكد من تحويل حالة هذه الفاتورة إلى مدفوع؟')) return;

    try {
        const res = await api.put(`/api/subscriptions/${entry.id}/toggle-payment`);
        entry.payment_status = res.data.subscription.payment_status;
        alert('تم تحديث حالة الفاتورة بنجاح.');
    } catch (err) {
        alert('حدث خطأ أثناء تحديث حالة الفاتورة.');
    }
}

onMounted(() => {
    fetchAllHistory()
    fetchSettings()
})
</script>

<template>
  <div class="space-y-6" dir="rtl">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
      <div>
        <h1 class="text-2xl font-black text-slate-800 tracking-tight">سجل الاشتراكات الموحد</h1>
        <p class="mt-1 text-sm text-slate-500 font-bold leading-relaxed max-w-xl">مراقبة كافة عمليات التجديد، المبالغ المحصلة، والحركات المالية لكل المدارس.</p>
      </div>
      <div>
        <button @click="resetHistory" class="px-4 py-2 bg-[#e64040]/10 text-[#e64040] hover:bg-[#e64040] hover:text-white rounded-xl text-xs font-black transition-colors flex items-center gap-2 border border-[#e64040]/20">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
            تصفير السجل
        </button>
      </div>
    </div>

    <!-- History Stats and Filters -->
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
      
      <!-- Stats Cards -->
      <div class="lg:col-span-3 grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white p-5 rounded-3xl border border-[#f0e8df] shadow-sm flex items-center gap-4">
            <div class="h-12 w-12 rounded-2xl bg-slate-50 flex items-center justify-center border border-slate-100">
                <span class="text-xl font-black text-slate-700">{{ latestStatuses.total }}</span>
            </div>
            <div>
                <p class="text-[11px] font-black text-slate-400 uppercase tracking-widest">إجمالي المدارس</p>
                <p class="text-xs font-bold text-slate-500 mt-0.5">مسجلة في النظام</p>
            </div>
        </div>
        
        <div class="bg-white p-5 rounded-3xl border border-[#f0e8df] shadow-sm flex items-center gap-4">
            <div class="h-12 w-12 rounded-2xl bg-[#157f7f]/5 flex items-center justify-center border border-[#157f7f]/10">
                <span class="text-xl font-black text-[#157f7f]">{{ latestStatuses.paid }}</span>
            </div>
            <div>
                <p class="text-[11px] font-black text-[#157f7f] uppercase tracking-widest">مدرسة مسددة</p>
                <p class="text-xs font-bold text-slate-500 mt-0.5">آخر فاتورة مدفوعة</p>
            </div>
        </div>

        <div class="bg-white p-5 rounded-3xl border border-[#e64040]/20 shadow-sm flex items-center gap-4">
            <div class="h-12 w-12 rounded-2xl bg-[#e64040]/5 flex items-center justify-center border border-[#e64040]/10">
                <span class="text-xl font-black text-[#e64040]">{{ latestStatuses.unpaid }}</span>
            </div>
            <div>
                <p class="text-[11px] font-black text-[#e64040] uppercase tracking-widest">مدرسة غير مسددة</p>
                <p class="text-xs font-bold text-slate-500 mt-0.5">آخر فاتورة غير مدفوعة</p>
            </div>
        </div>
      </div>

      <!-- Filters & Search -->
      <div class="lg:col-span-1 flex flex-col gap-4">
        <div class="relative w-full">
            <MagnifyingGlassIcon class="absolute right-4 top-1/2 -translate-y-1/2 h-5 w-5 text-slate-300" />
            <input v-model="searchQuery" type="text" placeholder="البحث برقم الفاتورة أو اسم المدرسة..." class="w-full pr-12 pl-4 py-3 bg-white border border-[#e8ded1] rounded-2xl text-sm font-bold outline-none focus:border-[#157f7f] transition-all shadow-sm" />
        </div>
        <select v-model="filterStatus" class="w-full px-4 py-3 bg-white border border-[#e8ded1] rounded-2xl text-sm font-bold outline-none focus:border-[#157f7f] transition-all shadow-sm appearance-none cursor-pointer">
            <option value="all">جميع الحالات التمويلية</option>
            <option value="paid">المدفوعة فقط</option>
            <option value="unpaid">الغير مدفوعة فقط</option>
        </select>
      </div>
    </div>

    <!-- History Table -->
    <div class="bg-white rounded-[32px] border border-[#f0e8df] shadow-sm overflow-hidden">
        <table class="w-full text-right border-collapse">
            <thead>
                <tr class="bg-[#fafaf9] border-b border-[#f0e8df]">
                    <th class="px-6 py-4 text-[11px] font-black text-slate-400 uppercase tracking-widest">رقم الفاتورة</th>
                    <th class="px-6 py-4 text-[11px] font-black text-slate-400 uppercase tracking-widest whitespace-nowrap">المدرسة / التاريخ</th>
                    <th class="px-6 py-4 text-[11px] font-black text-slate-400 uppercase tracking-widest text-center whitespace-nowrap">الباقة</th>
                    <th class="px-6 py-4 text-[11px] font-black text-slate-400 uppercase tracking-widest text-center whitespace-nowrap">الحالة</th>
                    <th class="px-6 py-4 text-[11px] font-black text-slate-400 uppercase tracking-widest text-center whitespace-nowrap">المبلغ</th>
                    <th class="px-6 py-4 text-[11px] font-black text-slate-400 uppercase tracking-widest text-left whitespace-nowrap">الإجراءات</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-[#f5f0e9]">
                <tr v-for="entry in filteredHistory" :key="entry.id" class="group hover:bg-[#fcf9f4]/40 transition-colors">
                    <td class="px-6 py-5 align-middle">
                        <span class="text-[10px] font-black text-[#157f7f] font-mono tracking-tighter bg-[#157f7f]/5 px-2 py-1 rounded-md border border-[#157f7f]/10">
                            {{ entry.invoice_number || '---' }}
                        </span>
                    </td>
                    <td class="px-6 py-5 align-middle">
                        <div class="flex items-center gap-3">
                            <div class="h-8 w-8 rounded-lg bg-[#fcf9f4] border border-[#e8ded1] flex items-center justify-center text-[#157f7f]">
                                <CalendarDaysIcon class="h-5 w-5" />
                            </div>
                            <div class="flex flex-col">
                                <span class="text-xs font-black text-slate-800">{{ entry.tenant?.name || entry.tenant_id }}</span>
                                <span class="text-[9px] text-slate-400 font-bold uppercase tracking-tighter">{{ formatDate(entry.created_at) }}</span>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-5 align-middle text-center">
                        <span class="text-[10px] font-bold text-slate-500 bg-white border border-slate-200 px-2.5 py-1 rounded-lg">
                            {{ entry.plan?.name || 'خطة غير معروفة' }}
                        </span>
                    </td>
                    <td class="px-6 py-5 align-middle text-center">
                        <span :class="entry.payment_status === 'paid' ? 'bg-[#157f7f]/10 text-[#157f7f] border-[#157f7f]/20' : 'bg-[#e64040]/10 text-[#e64040] border-[#e64040]/20'" 
                              class="px-2 py-0.5 rounded-full border text-[8px] font-black uppercase tracking-widest">
                            {{ entry.payment_status === 'paid' ? 'مدفوع' : 'غير مدفوع' }}
                        </span>
                    </td>
                    <td class="px-6 py-5 align-middle text-center">
                        <span class="text-xs font-black text-slate-700">{{ entry.amount > 0 ? formatCurrency(entry.amount) : 'تحديد يدوي' }}</span>
                    </td>
                    <td class="px-6 py-5 align-middle text-left">
                        <div class="flex items-center justify-end gap-2">
                           <button v-if="entry.payment_status === 'unpaid'" @click="togglePaymentStatus(entry)" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-[#157f7f]/10 text-[#157f7f] hover:bg-[#157f7f] hover:text-white rounded-lg text-xs font-black transition-all shadow-sm group/btn mr-2">
                               <svg class="h-3.5 w-3.5 group-hover/btn:scale-110 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" /></svg>
                               تأكيد الدفع
                           </button>
                           <button @click="openPreview(entry)" class="inline-flex items-center gap-2 px-3 py-1.5 bg-white border border-[#e8ded1] text-slate-400 hover:text-[#157f7f] hover:border-[#157f7f] rounded-lg text-xs font-black transition-all shadow-sm group/btn">
                               <DocumentTextIcon class="h-3.5 w-3.5 group-hover/btn:scale-110 transition-transform" />
                               عرض وطباعة
                           </button>
                        </div>
                    </td>
                </tr>
                <tr v-if="filteredHistory.length === 0 && !isLoading">
                    <td colspan="5" class="px-6 py-20 text-center">
                        <div class="flex flex-col items-center opacity-30">
                            <CreditCardIcon class="h-16 w-16 text-slate-200 mb-4" />
                            <p class="text-xs font-black text-slate-400 uppercase tracking-widest">لا يوجد سجل اشتراكات بعد</p>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
    <!-- Invoice Preview Modal -->
    <div v-if="showPreviewModal" class="fixed inset-0 z-[100] overflow-y-auto flex items-center justify-center p-4">
      <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm" @click="closePreview"></div>
      
      <div class="relative bg-white rounded-[32px] shadow-2xl w-full max-w-2xl transform transition-all border border-[#f0e8df] overflow-hidden flex flex-col max-h-[90vh]">
        <!-- Modal Header -->
        <div class="p-6 border-b border-[#f0e8df] bg-[#fafaf9] flex items-center justify-between">
           <div>
              <h2 class="text-xl font-black text-slate-800 tracking-tight flex items-center gap-2">
                <DocumentTextIcon class="h-5 w-5 text-[#157f7f]" />
                معاينة الفاتورة
              </h2>
              <p class="text-[10px] font-bold text-slate-400 mt-1 uppercase">رقم الفاتورة: {{ selectedEntry?.invoice_number }}</p>
           </div>
           <button @click="closePreview" class="h-10 w-10 flex items-center justify-center rounded-xl bg-white border border-[#e8ded1] text-slate-400 hover:text-[#e64040] transition-colors shadow-sm">
             <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
           </button>
        </div>

        <!-- Invoice Body (Simulated Print View) -->
        <div class="flex-1 overflow-y-auto p-10 bg-white custom-scrollbar">
           <div class="border-2 border-slate-50 p-8 rounded-2xl relative overflow-hidden">
              <!-- Content -->
              <div class="relative space-y-12">
                 <!-- Header -->
                 <div class="flex justify-between items-start">
                    <div>
                       <h3 class="text-2xl font-black text-[#157f7f] tracking-tight">{{ settings.invoice_title || 'نظام إدارة المدارس' }}</h3>
                       <p class="text-[11px] font-bold text-slate-400 mt-2 max-w-xs leading-relaxed">{{ settings.invoice_address || 'لم يتم تحديد العنوان في الإعدادات' }}</p>
                       <p class="text-[11px] font-black text-slate-500 font-mono mt-1">{{ settings.invoice_phone || '---' }}</p>
                    </div>
                    <div class="text-left">
                       <h4 class="text-lg font-black text-slate-800 uppercase tracking-tighter">فاتورة تجديد</h4>
                       <p class="text-[10px] font-bold text-slate-400 mt-1">تاريخ الإصدار: {{ formatDate(selectedEntry?.created_at) }}</p>
                    </div>
                 </div>

                 <!-- Client Info -->
                 <div class="py-6 border-y border-slate-50 flex justify-between gap-10">
                    <div class="space-y-2">
                       <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest block">الفاتورة موجهة إلى:</span>
                       <h5 class="text-base font-black text-slate-800">{{ selectedEntry?.tenant?.name }}</h5>
                       <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">معرف المدرسة: {{ selectedEntry?.tenant_id }}</p>
                    </div>
                    <div class="space-y-2 text-left">
                       <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest block">حالة الدفع:</span>
                       <span :class="selectedEntry?.payment_status === 'paid' ? 'bg-[#157f7f]/10 text-[#157f7f] border-[#157f7f]/20' : 'bg-[#e64040]/10 text-[#e64040] border-[#e64040]/20'" 
                             class="inline-block px-3 py-1 rounded-full border text-[10px] font-black uppercase tracking-widest">
                          {{ selectedEntry?.payment_status === 'paid' ? 'مدفوع' : 'غير مدفوع' }}
                       </span>
                    </div>
                 </div>

                 <!-- Table -->
                 <div class="space-y-4">
                    <table class="w-full text-right border-collapse">
                       <thead>
                          <tr class="bg-slate-50">
                             <th class="px-4 py-3 text-[10px] font-black text-slate-400 uppercase tracking-widest">الوصف</th>
                             <th class="px-4 py-3 text-[10px] font-black text-slate-400 uppercase tracking-widest text-center">الباقة</th>
                             <th class="px-4 py-3 text-[10px] font-black text-slate-400 uppercase tracking-widest text-left">المبلغ</th>
                          </tr>
                       </thead>
                       <tbody>
                          <tr class="border-b border-slate-50">
                             <td class="px-4 py-6">
                                <span class="text-xs font-black text-slate-700 block">رسوم تجديد الخدمة</span>
                                <span class="text-[10px] font-bold text-slate-400 mt-1 block">فترة الاشتراك حتى: {{ formatDate(selectedEntry?.end_date) }}</span>
                             </td>
                             <td class="px-4 py-6 text-center">
                                <span class="text-xs font-bold text-slate-500 uppercase">{{ selectedEntry?.plan?.name }}</span>
                             </td>
                             <td class="px-4 py-6 text-left">
                                <span class="text-sm font-black text-slate-800">{{ selectedEntry?.amount > 0 ? formatCurrency(selectedEntry?.amount) : 'تحديد يدوي' }}</span>
                             </td>
                          </tr>
                       </tbody>
                    </table>
                 </div>

                 <!-- Totals -->
                 <div class="flex justify-end">
                    <div class="w-64 bg-slate-50/50 p-6 rounded-2xl border border-slate-50">
                       <div class="flex justify-between pt-2">
                          <span class="text-sm font-black text-slate-800 uppercase">الإجمالي الكلي:</span>
                          <span class="text-lg font-black text-[#157f7f]">{{ selectedEntry?.amount > 0 ? formatCurrency(selectedEntry?.amount) : 'تحديد يدوي' }}</span>
                       </div>
                    </div>
                 </div>

                 <!-- Footer Notes -->
                 <div class="pt-8">
                    <span class="text-[10px] font-black text-slate-400 uppercase border-b border-slate-100 pb-2 mb-3 block">ملاحظات وشروط الفاتورة</span>
                    <p class="text-[11px] font-bold text-slate-500 leading-relaxed">{{ settings.invoice_notes || 'شكراً لتعاملكم معنا.' }}</p>
                 </div>
              </div>
           </div>
        </div>

        <!-- Modal Footer -->
        <div class="p-6 bg-[#fafaf9] border-t border-[#f0e8df] flex justify-between items-center gap-4">
           <button @click="closePreview" type="button" class="px-6 py-3 rounded-2xl text-xs font-black text-slate-400 hover:text-slate-600 hover:bg-white border border-transparent hover:border-[#e8ded1] transition-all">
             إغلاق المعاينة
           </button>
           <button @click="printInvoice" class="px-8 py-3 rounded-2xl text-xs font-black text-white bg-[#157f7f] hover:bg-[#0f6060] shadow-lg shadow-[#157f7f]/20 transition-all flex items-center gap-3 active:scale-95">
             <PrinterIcon class="h-4.5 w-4.5" />
             طباعة الفاتورة النهائية
           </button>
        </div>
      </div>
    </div>
  </div>
</template>
