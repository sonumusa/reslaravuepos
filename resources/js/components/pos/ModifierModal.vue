<template> 
    <Modal v-model="isOpen" :title="item?.name || 'Customize Item'" size="md"> 
        <div class="space-y-6"> 
            <!-- Quantity --> 
            <div> 
                <p class="text-sm text-slate-400 mb-3">Quantity</p> 
                <div class="flex items-center justify-center gap-4"> 
                    <button 
                        @click="quantity > 1 && quantity--" 
                        class="w-12 h-12 bg-slate-600 hover:bg-red-500 rounded-xl text-xl font-bold transition-colors text-white" 
                    > 
                        - 
                    </button> 
                    <span class="text-3xl font-bold w-16 text-center text-white">{{ quantity }}</span> 
                    <button 
                        @click="quantity++" 
                        class="w-12 h-12 bg-slate-600 hover:bg-green-500 rounded-xl text-xl font-bold transition-colors text-white" 
                    > 
                        + 
                    </button> 
                </div> 
            </div> 
            
            <!-- Modifier Groups --> 
            <div v-for="group in modifierGroups" :key="group.name"> 
                <p class="text-sm text-slate-400 mb-2">{{ group.name }}</p> 
                <div class="grid grid-cols-3 gap-2"> 
                    <button 
                        v-for="mod in group.modifiers" 
                        :key="mod.id" 
                        @click="toggleModifier(mod)" 
                        :class="[ 
                            'py-3 rounded-xl font-medium transition-colors focus:outline-none text-sm', 
                            isModifierSelected(mod) 
                                ? 'bg-blue-600 text-white' 
                                : 'bg-slate-700 hover:bg-slate-600 text-slate-300' 
                        ]" 
                    > 
                        {{ mod.name }} 
                        <span v-if="mod.price > 0" class="block text-xs opacity-70"> 
                            +Rs. {{ mod.price }} 
                        </span> 
                    </button> 
                </div> 
            </div> 
            
            <!-- Special Instructions --> 
            <div> 
                <p class="text-sm text-slate-400 mb-2">Special Instructions</p> 
                <textarea 
                    v-model="notes" 
                    class="w-full bg-slate-700 border border-slate-600 rounded-xl p-3 text-white placeholder-slate-400 resize-none focus:outline-none focus:border-blue-500" 
                    rows="2" 
                    placeholder="e.g., No onion, less oil..." 
                ></textarea> 
            </div> 
            
            <!-- Price Summary --> 
            <div class="bg-slate-700 rounded-xl p-4"> 
                <div class="flex justify-between mb-2 text-sm"> 
                    <span class="text-slate-400">Base Price</span> 
                    <span class="text-white">Rs. {{ item?.price?.toLocaleString() }}</span> 
                </div> 
                <div v-if="modifiersTotal > 0" class="flex justify-between mb-2 text-sm"> 
                    <span class="text-slate-400">Modifiers</span> 
                    <span class="text-white">Rs. {{ modifiersTotal.toLocaleString() }}</span> 
                </div> 
                <div class="flex justify-between text-lg font-bold pt-2 border-t border-slate-600"> 
                    <span class="text-white">Total (x{{ quantity }})</span> 
                    <span class="text-green-400">Rs. {{ totalPrice.toLocaleString() }}</span> 
                </div> 
            </div> 
        </div> 
        
        <template #footer> 
            <div class="flex gap-3"> 
                <button 
                    @click="isOpen = false" 
                    class="flex-1 bg-slate-700 hover:bg-slate-600 text-white rounded-xl py-3 transition-colors" 
                > 
                    Cancel 
                </button> 
                <button 
                    @click="addToOrder" 
                    class="flex-1 bg-green-600 hover:bg-green-500 text-white rounded-xl py-3 font-bold transition-colors" 
                > 
                    <i class="fas fa-plus mr-2"></i> 
                    Add to Order 
                </button> 
            </div> 
        </template> 
    </Modal> 
</template> 

<script setup> 
import { ref, computed, watch } from 'vue'; 
import Modal from '@/components/common/Modal.vue'; 

const props = defineProps({ 
    modelValue: Boolean, 
    item: Object, 
    modifiers: { 
        type: Array, 
        default: () => [] 
    } 
}); 

const emit = defineEmits(['update:modelValue', 'add']); 

const isOpen = computed({ 
    get: () => props.modelValue, 
    set: (val) => emit('update:modelValue', val) 
}); 

const quantity = ref(1); 
const selectedModifiers = ref([]); 
const notes = ref(''); 

// Reset when modal opens 
watch(() => props.modelValue, (open) => { 
    if (open) { 
        quantity.value = 1; 
        selectedModifiers.value = []; 
        notes.value = ''; 
        
        // Select default modifiers 
        if (props.modifiers) {
            props.modifiers.forEach(mod => { 
                if (mod.is_default) { 
                    selectedModifiers.value.push(mod); 
                } 
            }); 
        }
    } 
}); 

const modifierGroups = computed(() => { 
    const groups = {}; 
    if (props.modifiers) {
        props.modifiers.forEach(mod => { 
            if (!groups[mod.group_name]) { 
                groups[mod.group_name] = { name: mod.group_name, modifiers: [] }; 
            } 
            groups[mod.group_name].modifiers.push(mod); 
        }); 
    }
    return Object.values(groups); 
}); 

const modifiersTotal = computed(() => { 
    return selectedModifiers.value.reduce((sum, mod) => sum + mod.price, 0); 
}); 

const unitPrice = computed(() => { 
    return (props.item?.price || 0) + modifiersTotal.value; 
}); 

const totalPrice = computed(() => { 
    return unitPrice.value * quantity.value; 
}); 

function isModifierSelected(mod) { 
    return selectedModifiers.value.some(m => m.id === mod.id); 
} 

function toggleModifier(mod) { 
    const index = selectedModifiers.value.findIndex(m => m.id === mod.id); 
    if (index >= 0) { 
        selectedModifiers.value.splice(index, 1); 
    } else { 
        selectedModifiers.value.push(mod); 
    } 
} 

function addToOrder() { 
    emit('add', { 
        menuItem: props.item, 
        quantity: quantity.value, 
        modifiers: [...selectedModifiers.value], 
        notes: notes.value, 
        unitPrice: unitPrice.value, 
        subtotal: totalPrice.value 
    }); 
    emit('update:modelValue', false); 
} 
</script>
