export function formatCurrency(value) {
    if (typeof value !== 'number') {
        value = parseFloat(value) || 0;
    }
    return `Rs. ${value.toLocaleString('en-PK', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
}

export function formatDate(date) {
    if (!date) return '';
    return new Date(date).toLocaleDateString();
}

export function formatTime(date) {
    if (!date) return '';
    return new Date(date).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
}
