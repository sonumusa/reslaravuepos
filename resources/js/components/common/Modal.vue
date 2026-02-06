<template>
    <Teleport to="body">
        <Transition name="modal">
            <div 
                v-if="modelValue" 
                class="fixed inset-0 z-50 flex items-center justify-center p-4" 
                @click.self="closeOnBackdrop && close()" 
            >
                <!-- Backdrop -->
                <div class="absolute inset-0 bg-black/70 backdrop-blur-sm"></div>
                
                <!-- Modal Content -->
                <div 
                    :class="[ 
                        'relative bg-slate-800 rounded-2xl shadow-2xl w-full overflow-hidden slide-up', 
                        sizeClasses 
                    ]" 
                >
                    <!-- Header -->
                    <div v-if="title || $slots.header" class="px-6 py-4 border-b border-slate-700 flex items-center justify-between">
                        <slot name="header">
                            <div>
                                <h3 class="text-lg font-bold text-white">{{ title }}</h3>
                                <p v-if="subtitle" class="text-sm text-slate-400">{{ subtitle }}</p>
                            </div>
                        </slot>
                        <button 
                            v-if="showClose" 
                            @click="close" 
                            class="w-10 h-10 bg-slate-700 hover:bg-slate-600 rounded-xl flex items-center justify-center transition-colors text-white" 
                        >
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    
                    <!-- Body -->
                    <div :class="['overflow-y-auto', bodyClass]" :style="{ maxHeight: maxHeight }">
                        <slot></slot>
                    </div>
                    
                    <!-- Footer -->
                    <div v-if="$slots.footer" class="px-6 py-4 border-t border-slate-700 bg-slate-800">
                        <slot name="footer"></slot>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup>
import { computed, watch, onMounted, onUnmounted } from 'vue';

const props = defineProps({
    modelValue: {
        type: Boolean,
        default: false
    },
    title: {
        type: String,
        default: ''
    },
    subtitle: {
        type: String,
        default: ''
    },
    size: {
        type: String,
        default: 'md',
        validator: (v) => ['sm', 'md', 'lg', 'xl', 'full'].includes(v)
    },
    showClose: {
        type: Boolean,
        default: true
    },
    closeOnBackdrop: {
        type: Boolean,
        default: true
    },
    closeOnEscape: {
        type: Boolean,
        default: true
    },
    bodyClass: {
        type: String,
        default: 'p-6'
    },
    maxHeight: {
        type: String,
        default: '70vh'
    }
});

const emit = defineEmits(['update:modelValue', 'close']);

const sizeClasses = computed(() => {
    const sizes = {
        sm: 'max-w-sm',
        md: 'max-w-md',
        lg: 'max-w-lg',
        xl: 'max-w-xl',
        full: 'max-w-4xl'
    };
    return sizes[props.size];
});

function close() {
    emit('update:modelValue', false);
    emit('close');
}

function handleEscape(e) {
    if (e.key === 'Escape' && props.closeOnEscape && props.modelValue) {
        close();
    }
}

// Lock body scroll when modal is open
watch(() => props.modelValue, (isOpen) => {
    document.body.style.overflow = isOpen ? 'hidden' : '';
});

onMounted(() => {
    document.addEventListener('keydown', handleEscape);
});

onUnmounted(() => {
    document.removeEventListener('keydown', handleEscape);
    document.body.style.overflow = '';
});
</script>

<style scoped>
.modal-enter-active,
.modal-leave-active {
    transition: opacity 0.3s ease;
}

.modal-enter-from,
.modal-leave-to {
    opacity: 0;
}

.modal-enter-active .slide-up,
.modal-leave-active .slide-up {
    transition: transform 0.3s ease;
}

.modal-enter-from .slide-up,
.modal-leave-to .slide-up {
    transform: translateY(20px);
}
</style>
