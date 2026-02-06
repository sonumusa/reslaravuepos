<template> 
    <Modal v-model="isOpen" title="Process Payment" size="lg"> 
        <div class="grid grid-cols-2 gap-6"> 
            <!-- Left: Payment Options --> 
            <div class="space-y-4"> 
                <!-- Amount Due --> 
                <div class="bg-slate-700 rounded-xl p-4 text-center"> 
                    <p class="text-sm text-slate-400">Amount Due</p> 
                    <p class="text-4xl font-bold text-green-400">Rs. {{ totalAmount.toLocaleString() }}</p> 
                </div> 
                
                <!-- Payment Method --> 
                <div class="grid grid-cols-2 gap-3"> 
                    <button 
                        @click="paymentMethod = 'cash'" 
                        :class="[ 
                            'flex flex-col items-center gap-2 p-4 rounded-xl transition-colors focus:outline-none', 
                            paymentMethod === 'cash' ? 'bg-green-600 text-white' : 'bg-slate-700 hover:bg-slate-600 text-slate-300' 
                        ]" 
                    > 
                        <i class="fas fa-money-bill-wave text-2xl"></i> 
                        <span class="font-medium">Cash</span> 
                    </button> 
                    <button 
                        @click="paymentMethod = 'card'" 
                        :class="[ 
                            'flex flex-col items-center gap-2 p-4 rounded-xl transition-colors focus:outline-none', 
                            paymentMethod === 'card' ? 'bg-blue-600 text-white' : 'bg-slate-700 hover:bg-slate-600 text-slate-300' 
                        ]" 
                    > 
                        <i class="fas fa-credit-card text-2xl"></i> 
                        <span class="font-medium">Card</span> 
                    </button> 
                </div> 
                
                <!-- Cash Tendered (for cash payment) --> 
                <div v-if="paymentMethod === 'cash'"> 
                    <p class="text-sm text-slate-400 mb-2">Cash Tendered</p> 
                    <NumPad 
                        v-model="cashTendered" 
                        :show-quick-amounts="true" 
                        :quick-amounts="[500, 1000, 2000, 5000]" 
                    /> 
                </div> 
                
                <!-- Card Details (for card payment) --> 
                <div v-if="paymentMethod === 'card'" class="space-y-3"> 
                    <div> 
                        <p class="text-sm text-slate-400 mb-2">Card Type</p> 
                        <div class="grid grid-cols-3 gap-2"> 
                            <button 
                                v-for="type in ['Visa', 'Mastercard', 'Other']" 
                                :key="type" 
                                @click="cardType = type" 
                                :class="[ 
                                    'py-2 rounded-lg text-sm transition-colors focus:outline-none', 
                                    cardType === type ? 'bg-blue-600 text-white' : 'bg-slate-700 text-slate-300 hover:bg-slate-600' 
                                ]" 
                            > 
                                {{ type }} 
                            </button> 
                        </div> 
                    </div> 
                    <div> 
                        <p class="text-sm text-slate-400 mb-2">Last 4 Digits</p> 
                        <input 
                            v-model="cardLastFour" 
                            type="text" 
                            maxlength="4" 
                            class="w-full bg-slate-700 border border-slate-600 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-blue-500 text-center text-2xl tracking-widest placeholder-slate-500" 
                            placeholder="• • • •" 
                        > 
                    </div> 
                </div> 
            </div> 
            
            <!-- Right: Summary & Tip --> 
            <div class="space-y-4"> 
                <!-- Order Summary --> 
                <div class="bg-slate-700 rounded-xl p-4 space-y-2 text-sm max-h-40 overflow-y-auto"> 
                    <div v-for="item in orderItems" :key="item.id" class="flex justify-between text-slate-300"> 
                        <span>{{ item.item_name }} x{{ item.quantity }}</span> 
                        <span>Rs. {{ item.subtotal.toLocaleString() }}</span> 
                    </div> 
                </div> 
                
                <!-- Tip Section --> 
                <div> 
                    <p class="text-sm text-slate-400 mb-2">Add Tip (Optional)</p> 
                    <div class="grid grid-cols-4 gap-2"> 
                        <button 
                            v-for="tipAmount in [0, 100, 200, 500]" 
                            :key="tipAmount" 
                            @click="tip = tipAmount" 
                            :class="[ 
                                'py-2 rounded-lg text-sm transition-colors focus:outline-none', 
                                tip === tipAmount ? 'bg-purple-600 text-white' : 'bg-slate-700 hover:bg-slate-600 text-slate-300' 
                            ]" 
                        > 
                            {{ tipAmount === 0 ? 'No Tip' : 'Rs. ' + tipAmount }} 
                        </button> 
                    </div> 
                </div> 
                
                <!-- Change Due (for cash) --> 
                <div 
                    v-if="paymentMethod === 'cash' && change >= 0" 
                    class="bg-blue-500/20 rounded-xl p-4 text-center" 
                > 
                    <p class="text-sm text-blue-300">Change Due</p> 
                    <p class="text-3xl font-bold text-blue-400">Rs. {{ change.toLocaleString() }}</p> 
                </div> 
                
                <!-- Total with Tip --> 
                <div class="bg-green-500/20 rounded-xl p-4 text-center"> 
                    <p class="text-sm text-green-300">Total Payment</p> 
                    <p class="text-3xl font-bold text-green-400">Rs. {{ (totalAmount + tip).toLocaleString() }}</p> 
                </div> 
            </div> 
        </div> 
        
        <template #footer> 
            <div class="flex gap-3"> 
                <button @click="isOpen = false" class="flex-1 bg-slate-700 hover:bg-slate-600 text-white rounded-xl py-3 transition-colors"> 
                    Cancel 
                </button> 
                <button 
                    @click="processPayment" 
                    :disabled="!canProcess" 
                    class="flex-1 bg-green-600 hover:bg-green-500 text-white rounded-xl py-4 font-bold text-lg disabled:opacity-50 disabled:cursor-not-allowed transition-colors" 
                > 
                    <i class="fas fa-check-circle mr-2"></i> 
                    Complete Payment 
                </button> 
            </div> 
        </template> 
    </Modal> 
</template> 

<script setup> 
import { ref, computed } from 'vue'; 
import Modal from '@/components/common/Modal.vue'; 
import NumPad from '@/components/common/NumPad.vue'; 

const props = defineProps({ 
    modelValue: Boolean, 
    totalAmount: { 
        type: Number, 
        default: 0 
    }, 
    orderItems: { 
        type: Array, 
        default: () => [] 
    } 
}); 

const emit = defineEmits(['update:modelValue', 'process']); 

const isOpen = computed({ 
    get: () => props.modelValue, 
    set: (val) => emit('update:modelValue', val) 
}); 

const paymentMethod = ref('cash'); 
const cashTendered = ref(''); 
const cardType = ref('Visa'); 
const cardLastFour = ref(''); 
const tip = ref(0); 

const change = computed(() => { 
    const tendered = Number(cashTendered.value) || 0; 
    return tendered - (props.totalAmount + tip.value); 
}); 

const canProcess = computed(() => { 
    if (paymentMethod.value === 'cash') { 
        return Number(cashTendered.value) >= (props.totalAmount + tip.value); 
    } 
    return cardLastFour.value.length === 4; 
}); 

function processPayment() { 
    emit('process', { 
        method: paymentMethod.value, 
        amount: props.totalAmount, 
        tip: tip.value, 
        tendered: paymentMethod.value === 'cash' ? Number(cashTendered.value) : null, 
        change: paymentMethod.value === 'cash' ? change.value : 0, 
        cardType: paymentMethod.value === 'card' ? cardType.value : null, 
        cardLastFour: paymentMethod.value === 'card' ? cardLastFour.value : null 
    }); 
} 
</script>
