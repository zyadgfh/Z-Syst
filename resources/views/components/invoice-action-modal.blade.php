<div id="invoice-action-modal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" x-data="invoiceModal()">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-2xl shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <!-- Header -->
            <div class="flex items-center justify-between pb-4 border-b">
                <h3 class="text-lg font-medium text-gray-900">تم حفظ الفاتورة بنجاح</h3>
                <button @click="closeModal" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <!-- Invoice Info -->
            <div class="bg-green-50 border border-green-200 rounded-lg p-4 mt-4">
                <div class="flex items-center gap-3">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2l4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <div>
                        <p class="font-semibold text-green-900">
                            فاتورة <span x-text="invoice?.invoice_number"></span>
                        </p>
                        <p class="text-sm text-green-700">
                            المبلغ الإجمالي: <span x-text="invoice?.total_amount"></span> ج.م
                        </p>
                    </div>
                </div>
            </div>

            <form @submit.prevent="submit">
                <div class="space-y-4 mt-4 max-h-96 overflow-y-auto">
                    <!-- WhatsApp Options -->
                    <div class="border rounded-lg p-4">
                        <h4 class="font-semibold text-gray-900 flex items-center gap-2 mb-3">
                            <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12.031 6.5a5.5 5.5 0 015.5 5.5v3.5a5.5 5.5 0 01-5.5 5.5H6.5a5.5 5.5 0 01-5.5-5.5v-3.5a5.5 5.5 0 015.5-5.5h5.527l2.004-2.5H12.031z"/>
                            </svg>
                            إرسال عبر واتساب
                        </h4>
                        
                        <div class="bg-gray-50 rounded-lg p-3 mb-3">
                            <p class="text-sm text-gray-600 mb-2" x-text="'إلى: ' + invoice?.customer?.phone + ' (' + invoice?.customer?.name + ')'"></p>
                            
                            <div class="space-y-2">
                                <label class="flex items-center">
                                    <input type="checkbox" x-model="options.whatsapp.sendImage" class="w-4 h-4 text-green-600 rounded">
                                    <span class="mr-2 text-sm">إرسال كصورة</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="checkbox" x-model="options.whatsapp.sendPDF" class="w-4 h-4 text-green-600 rounded">
                                    <span class="mr-2 text-sm">إرسال كـ PDF</span>
                                </label>
                            </div>

                            <div class="mt-3">
                                <label class="flex items-center mb-2">
                                    <input type="checkbox" x-model="options.whatsapp.includeMessage" class="w-4 h-4 text-green-600 rounded">
                                    <span class="mr-2 text-sm">إضافة رسالة مخصصة</span>
                                </label>
                                <textarea 
                                    x-show="options.whatsapp.includeMessage"
                                    x-model="options.whatsapp.customMessage"
                                    placeholder="شكراً لتسوقكم من صيدليتنا..."
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm"
                                    rows="2"
                                ></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Print Options -->
                    <div class="border rounded-lg p-4">
                        <h4 class="font-semibold text-gray-900 flex items-center gap-2 mb-3">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2h2m0 0l5-5m-5 0l5 5"></path>
                            </svg>
                            طباعة الفاتورة
                        </h4>
                        
                        <div class="space-y-2">
                            <label class="flex items-center">
                                <input type="checkbox" x-model="options.print.thermal" class="w-4 h-4 text-blue-600 rounded">
                                <span class="mr-2 text-sm">طابعة حرارية (80mm)</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" x-model="options.print.a4" class="w-4 h-4 text-blue-600 rounded">
                                <span class="mr-2 text-sm">طباعة A4</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" x-model="options.print.downloadPdf" class="w-4 h-4 text-blue-600 rounded">
                                <span class="mr-2 text-sm">تحميل PDF</span>
                            </label>
                        </div>
                    </div>

                    <!-- Save as Default -->
                    <div class="border-t pt-4">
                        <label class="flex items-center">
                            <input type="checkbox" x-model="saveAsDefault" class="w-4 h-4 text-indigo-600 rounded">
                            <span class="mr-2 text-sm text-gray-700">حفظ هذه الإعدادات كافتراضية</span>
                        </label>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex justify-end gap-3 pt-4 border-t mt-4">
                    <button 
                        type="button"
                        @click="skip"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded-md hover:bg-gray-200"
                    >
                        تخطي
                    </button>
                    <button 
                        type="submit"
                        :disabled="loading"
                        class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-md hover:bg-indigo-700 disabled:opacity-50"
                    >
                        <span x-show="!loading">تنفيذ</span>
                        <span x-show="loading">جاري الإرسال...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function invoiceModal() {
    return {
        open: false,
        invoice: null,
        loading: false,
        saveAsDefault: false,
        options: {
            whatsapp: {
                sendImage: true,
                sendPDF: false,
                includeMessage: false,
                customMessage: 'شكراً لتسوقكم من صيدليتنا. فاتورتكم في المرفق.'
            },
            print: {
                thermal: false,
                a4: false,
                downloadPdf: true
            }
        },

        init() {
            // Load saved default options if any
            const savedDefaults = localStorage.getItem('invoice_default_options');
            if (savedDefaults) {
                this.options = JSON.parse(savedDefaults);
            }
        },

        openModal(invoice) {
            this.invoice = invoice;
            this.open = true;
            document.getElementById('invoice-action-modal').classList.remove('hidden');
        },

        closeModal() {
            this.open = false;
            this.invoice = null;
            document.getElementById('invoice-action-modal').classList.add('hidden');
        },

        skip() {
            this.closeModal();
            // Redirect to sales list or new sale
            window.location.href = '/admin/sales';
        },

        async submit() {
            if (!this.invoice) return;

            this.loading = true;

            try {
                const response = await fetch(`/api/v1/sales/${this.invoice.id}/send-notifications`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                    },
                    body: JSON.stringify({
                        channels: {
                            whatsapp: {
                                sendImage: this.options.whatsapp.sendImage,
                                sendPDF: this.options.whatsapp.sendPDF,
                                customMessage: this.options.whatsapp.customMessage
                            },
                            print: {
                                template: this.options.print.thermal ? 'thermal' : 'a4'
                            }
                        }
                    })
                });

                const result = await response.json();

                if (this.saveAsDefault) {
                    localStorage.setItem('invoice_default_options', JSON.stringify(this.options));
                }

                // Show success message
                alert(result.message || 'تم إرسال الفاتورة');

                this.closeModal();

                // Optional: redirect or refresh
                window.location.reload();

            } catch (error) {
                console.error('Error:', error);
                alert('حدث خطأ أثناء الإرسال');
            } finally {
                this.loading = false;
            }
        }
    };
}
</script>