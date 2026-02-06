import { defineStore } from 'pinia';
import { ref, computed, watch } from 'vue';

export const useSettingsStore = defineStore('settings', () => {
    // ═══════════════════════════════════════════════════════
    // STATE
    // ═══════════════════════════════════════════════════════
    const settings = ref({
        // Display
        theme: 'dark',
        language: 'en',
        currency: 'PKR',
        currencySymbol: 'Rs.',
        dateFormat: 'DD/MM/YYYY',
        timeFormat: '12h',
        
        // POS Settings
        autoSendToKitchen: false,
        printReceiptAutomatically: true,
        showItemImages: true,
        enableBarcode: true,
        enableQuickAdd: true,
        defaultOrderType: 'dine_in',
        
        // Tax
        taxRate: 16,
        taxInclusive: true,
        
        // Receipt
        showLogo: true,
        receiptHeader: '',
        receiptFooter: 'Thank you for dining with us!',
        
        // Sound
        enableSounds: true,
        orderNotificationSound: true,
        
        // Terminal
        terminalId: null,
        terminalName: '',
    });

    // ═══════════════════════════════════════════════════════
    // GETTERS
    // ═══════════════════════════════════════════════════════
    const formatCurrency = computed(() => (amount) => {
        const num = parseFloat(amount) || 0;
        return `${settings.value.currencySymbol} ${num.toLocaleString('en-PK', {
            minimumFractionDigits: 0,
            maximumFractionDigits: 0,
        })}`;
    });

    const formatDate = computed(() => (date) => {
        if (!date) return '';
        const d = new Date(date);
        return d.toLocaleDateString('en-PK');
    });

    const formatTime = computed(() => (date) => {
        if (!date) return '';
        const d = new Date(date);
        return d.toLocaleTimeString('en-PK', {
            hour: '2-digit',
            minute: '2-digit',
            hour12: settings.value.timeFormat === '12h',
        });
    });

    const formatDateTime = computed(() => (date) => {
        if (!date) return '';
        return `${formatDate.value(date)} ${formatTime.value(date)}`;
    });

    // ═══════════════════════════════════════════════════════
    // ACTIONS
    // ═══════════════════════════════════════════════════════
    
    /**
     * Load settings from localStorage
     */
    function loadSettings() {
        const stored = localStorage.getItem('pos_settings');
        if (stored) {
            try {
                const parsed = JSON.parse(stored);
                settings.value = { ...settings.value, ...parsed };
            } catch (e) {
                console.error('Failed to parse settings:', e);
            }
        }
    }

    /**
     * Save settings to localStorage
     */
    function saveSettings() {
        localStorage.setItem('pos_settings', JSON.stringify(settings.value));
    }

    /**
     * Update a setting
     */
    function updateSetting(key, value) {
        if (key in settings.value) {
            settings.value[key] = value;
            saveSettings();
        }
    }

    /**
     * Update multiple settings
     */
    function updateSettings(newSettings) {
        settings.value = { ...settings.value, ...newSettings };
        saveSettings();
    }

    /**
     * Reset to defaults
     */
    function resetSettings() {
        settings.value = {
            theme: 'dark',
            language: 'en',
            currency: 'PKR',
            currencySymbol: 'Rs.',
            dateFormat: 'DD/MM/YYYY',
            timeFormat: '12h',
            autoSendToKitchen: false,
            printReceiptAutomatically: true,
            showItemImages: true,
            enableBarcode: true,
            enableQuickAdd: true,
            defaultOrderType: 'dine_in',
            taxRate: 16,
            taxInclusive: true,
            showLogo: true,
            receiptHeader: '',
            receiptFooter: 'Thank you for dining with us!',
            enableSounds: true,
            orderNotificationSound: true,
            terminalId: null,
            terminalName: '',
        };
        saveSettings();
    }

    /**
     * Set terminal info
     */
    function setTerminal(id, name) {
        settings.value.terminalId = id;
        settings.value.terminalName = name;
        localStorage.setItem('terminal_id', id);
        saveSettings();
    }

    // Load on init
    loadSettings();

    // Watch for changes and auto-save
    watch(settings, saveSettings, { deep: true });

    // ═══════════════════════════════════════════════════════
    // RETURN
    // ═══════════════════════════════════════════════════════
    return {
        // State
        settings,
        
        // Getters
        formatCurrency,
        formatDate,
        formatTime,
        formatDateTime,
        
        // Actions
        loadSettings,
        saveSettings,
        updateSetting,
        updateSettings,
        resetSettings,
        setTerminal,
    };
});
