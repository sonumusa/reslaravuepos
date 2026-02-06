<template>
    <div class="min-h-screen flex items-center justify-center bg-gray-100 dark:bg-gray-900 px-4">
        <div class="bg-white dark:bg-gray-800 p-8 rounded-lg shadow-md w-full max-w-md">
            <!-- Header -->
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">ResLaraVue POS</h1>
                <p class="text-gray-600 dark:text-gray-400">Sign in to start your session</p>
            </div>

            <!-- Login Mode Toggle -->
            <div class="flex border-b border-gray-200 dark:border-gray-700 mb-6">
                <button 
                    @click="loginMode = 'email'"
                    class="flex-1 py-2 text-sm font-medium border-b-2 transition-colors"
                    :class="loginMode === 'email' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400'"
                >
                    Email Login
                </button>
                <button 
                    @click="loginMode = 'pin'"
                    class="flex-1 py-2 text-sm font-medium border-b-2 transition-colors"
                    :class="loginMode === 'pin' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400'"
                >
                    PIN Login
                </button>
            </div>

            <!-- Error Message -->
            <div v-if="error" class="mb-4 p-3 bg-red-50 dark:bg-red-900/30 text-red-700 dark:text-red-400 rounded-md text-sm flex items-start">
                <ExclamationCircleIcon class="w-5 h-5 mr-2 flex-shrink-0" />
                <span>{{ error }}</span>
            </div>

            <!-- Email Login Form -->
            <form v-if="loginMode === 'email'" @submit.prevent="handleEmailLogin">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Email Address</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <EnvelopeIcon class="h-5 w-5 text-gray-400" />
                        </div>
                        <input 
                            v-model="email" 
                            type="email" 
                            class="w-full pl-10 pr-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white" 
                            placeholder="admin@example.com"
                            required 
                            autofocus
                        />
                    </div>
                </div>
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Password</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <LockClosedIcon class="h-5 w-5 text-gray-400" />
                        </div>
                        <input 
                            v-model="password" 
                            type="password" 
                            class="w-full pl-10 pr-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white" 
                            placeholder="••••••••"
                            required 
                        />
                    </div>
                </div>
                <button 
                    type="submit" 
                    :disabled="isLoading"
                    class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    <span v-if="isLoading" class="mr-2">
                        <svg class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </span>
                    Sign In
                </button>
            </form>

            <!-- PIN Login Form -->
            <form v-else @submit.prevent="handlePinLogin">
                <div class="mb-6">
                    <label class="block text-center text-sm font-medium text-gray-700 dark:text-gray-300 mb-4">Enter your 4-digit PIN</label>
                    <div class="flex justify-center gap-3">
                        <input 
                            v-for="(digit, index) in 4" 
                            :key="index"
                            ref="pinInputs"
                            type="password"
                            inputmode="numeric"
                            maxlength="1"
                            class="w-12 h-12 text-center text-xl font-bold border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                            :value="pin[index] || ''"
                            @input="handlePinInput($event, index)"
                            @keydown.delete="handlePinBackspace($event, index)"
                        />
                    </div>
                </div>
                
                <!-- Numeric Keypad -->
                <div class="grid grid-cols-3 gap-3 mb-6">
                    <button 
                        v-for="num in [1, 2, 3, 4, 5, 6, 7, 8, 9]" 
                        :key="num"
                        type="button"
                        @click="appendPin(num)"
                        class="h-12 bg-gray-100 dark:bg-gray-700 rounded-lg text-lg font-medium hover:bg-gray-200 dark:hover:bg-gray-600 active:bg-gray-300 dark:active:bg-gray-500 transition-colors"
                    >
                        {{ num }}
                    </button>
                    <button 
                        type="button"
                        @click="clearPin"
                        class="h-12 bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400 rounded-lg font-medium hover:bg-red-200 dark:hover:bg-red-900/50 transition-colors"
                    >
                        C
                    </button>
                    <button 
                        type="button"
                        @click="appendPin(0)"
                        class="h-12 bg-gray-100 dark:bg-gray-700 rounded-lg text-lg font-medium hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"
                    >
                        0
                    </button>
                    <button 
                        type="button"
                        @click="backspacePin"
                        class="h-12 bg-gray-100 dark:bg-gray-700 rounded-lg flex items-center justify-center hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"
                    >
                        <BackspaceIcon class="w-6 h-6" />
                    </button>
                </div>

                <button 
                    type="submit" 
                    :disabled="isLoading || pin.length < 4"
                    class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    <span v-if="isLoading" class="mr-2">
                        <svg class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </span>
                    Login with PIN
                </button>
            </form>
        </div>
    </div>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import { useAppStore } from '@/stores/app';
import { EnvelopeIcon, LockClosedIcon, ExclamationCircleIcon, BackspaceIcon } from '@heroicons/vue/24/outline';

const router = useRouter();
const authStore = useAuthStore();
const appStore = useAppStore();

const loginMode = ref('email');
const email = ref('');
const password = ref('');
const pin = ref('');
const isLoading = ref(false);
const error = ref('');
const pinInputs = ref([]);

// Clear error when switching modes
watch(loginMode, () => {
    error.value = '';
    pin.value = '';
    password.value = '';
});

async function handleEmailLogin() {
    if (!email.value || !password.value) {
        error.value = 'Please enter both email and password';
        return;
    }

    isLoading.value = true;
    error.value = '';

    try {
        const result = await authStore.login({ 
            email: email.value, 
            password: password.value 
        });

        if (result.success) {
            appStore.showSuccess('Login successful');
            redirectUser();
        } else {
            error.value = result.error || 'Login failed. Please check your credentials.';
        }
    } catch (err) {
        error.value = 'An unexpected error occurred';
        console.error(err);
    } finally {
        isLoading.value = false;
    }
}

async function handlePinLogin() {
    if (pin.value.length < 4) {
        error.value = 'Please enter a 4-digit PIN';
        return;
    }

    isLoading.value = true;
    error.value = '';

    try {
        const result = await authStore.loginWithPin(pin.value);

        if (result.success) {
            appStore.showSuccess('Login successful');
            redirectUser();
        } else {
            error.value = result.error || 'Invalid PIN';
            pin.value = ''; // Clear PIN on error
        }
    } catch (err) {
        error.value = 'An unexpected error occurred';
        console.error(err);
    } finally {
        isLoading.value = false;
    }
}

function redirectUser() {
    const role = authStore.userRole;
    
    if (role === 'waiter') {
        router.push('/pos/tables');
    } else if (role === 'cashier') {
        router.push('/cashier');
    } else if (role === 'kitchen') {
        router.push('/kitchen');
    } else if (role === 'admin' || role === 'superadmin') {
        router.push('/admin');
    } else {
        router.push('/');
    }
}

// PIN Input Handling
function appendPin(num) {
    if (pin.value.length < 4) {
        pin.value += num;
        if (pin.value.length === 4) {
            // Auto-submit if 4 digits entered
            handlePinLogin();
        }
    }
}

function backspacePin() {
    if (pin.value.length > 0) {
        pin.value = pin.value.slice(0, -1);
    }
}

function clearPin() {
    pin.value = '';
}

function handlePinInput(event, index) {
    // Prevent direct typing in readonly-like inputs (managed by keypad)
    // But allowing backspace
    const val = event.target.value;
    // Implementation can be complex with multiple inputs, keeping it simple with one variable
    // for now, just refocus if they try to type directly
}

function handlePinBackspace(event, index) {
    if (event.key === 'Backspace') {
        backspacePin();
    }
}
</script>
