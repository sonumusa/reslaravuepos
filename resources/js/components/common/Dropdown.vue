<template> 
    <div class="relative" ref="dropdownRef"> 
        <!-- Trigger --> 
        <button 
            @click="toggle" 
            :class="[ 
                'w-full flex items-center justify-between px-4 py-3 rounded-xl border transition-colors', 
                isOpen 
                    ? 'bg-slate-700 border-blue-500' 
                    : 'bg-slate-800 border-slate-700 hover:border-slate-600' 
            ]" 
        > 
            <span :class="selectedLabel ? 'text-white' : 'text-slate-400'"> 
                {{ selectedLabel || placeholder }} 
            </span> 
            <i :class="['fas fa-chevron-down transition-transform text-slate-400', isOpen && 'rotate-180']"></i> 
        </button> 
        
        <!-- Dropdown Menu --> 
        <Transition name="dropdown"> 
            <div 
                v-if="isOpen" 
                class="absolute z-50 w-full mt-2 bg-slate-800 border border-slate-700 rounded-xl shadow-xl overflow-hidden" 
            > 
                <!-- Search --> 
                <div v-if="searchable" class="p-2 border-b border-slate-700"> 
                    <input 
                        v-model="searchQuery" 
                        type="text" 
                        placeholder="Search..." 
                        class="w-full bg-slate-700 border-none rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:ring-1 focus:ring-blue-500 placeholder-slate-400" 
                        @click.stop 
                    > 
                </div> 
                
                <!-- Options --> 
                <div class="max-h-60 overflow-y-auto"> 
                    <button 
                        v-for="option in filteredOptions" 
                        :key="option[valueKey]" 
                        @click="select(option)" 
                        :class="[ 
                            'w-full flex items-center gap-3 px-4 py-3 text-left transition-colors', 
                            option[valueKey] === modelValue 
                                ? 'bg-blue-600 text-white' 
                                : 'text-slate-300 hover:bg-slate-700' 
                        ]" 
                    > 
                        <i v-if="option.icon" :class="option.icon"></i> 
                        <span>{{ option[labelKey] }}</span> 
                        <i v-if="option[valueKey] === modelValue" class="fas fa-check ml-auto"></i> 
                    </button> 
                    
                    <div v-if="filteredOptions.length === 0" class="px-4 py-3 text-slate-400 text-sm text-center"> 
                        No options found 
                    </div> 
                </div> 
            </div> 
        </Transition> 
    </div> 
</template> 

<script setup> 
import { ref, computed, onMounted, onUnmounted } from 'vue'; 

const props = defineProps({ 
    modelValue: [String, Number], 
    options: { 
        type: Array, 
        default: () => [] 
    }, 
    placeholder: { 
        type: String, 
        default: 'Select option' 
    }, 
    labelKey: { 
        type: String, 
        default: 'label' 
    }, 
    valueKey: { 
        type: String, 
        default: 'value' 
    }, 
    searchable: { 
        type: Boolean, 
        default: false 
    } 
}); 

const emit = defineEmits(['update:modelValue', 'change']); 

const dropdownRef = ref(null); 
const isOpen = ref(false); 
const searchQuery = ref(''); 

const selectedLabel = computed(() => { 
    const selected = props.options.find(o => o[props.valueKey] === props.modelValue); 
    return selected ? selected[props.labelKey] : ''; 
}); 

const filteredOptions = computed(() => { 
    if (!searchQuery.value) return props.options; 
    const query = searchQuery.value.toLowerCase(); 
    return props.options.filter(o => 
        o[props.labelKey].toLowerCase().includes(query) 
    ); 
}); 

function toggle() { 
    isOpen.value = !isOpen.value; 
    if (!isOpen.value) { 
        searchQuery.value = ''; 
    } 
} 

function select(option) { 
    emit('update:modelValue', option[props.valueKey]); 
    emit('change', option); 
    isOpen.value = false; 
    searchQuery.value = ''; 
} 

function handleClickOutside(e) { 
    if (dropdownRef.value && !dropdownRef.value.contains(e.target)) { 
        isOpen.value = false; 
        searchQuery.value = ''; 
    } 
} 

onMounted(() => { 
    document.addEventListener('click', handleClickOutside); 
}); 

onUnmounted(() => { 
    document.removeEventListener('click', handleClickOutside); 
}); 
</script> 

<style scoped> 
.dropdown-enter-active, 
.dropdown-leave-active { 
    transition: all 0.2s ease; 
} 

.dropdown-enter-from, 
.dropdown-leave-to { 
    opacity: 0; 
    transform: translateY(-10px); 
} 
</style>
