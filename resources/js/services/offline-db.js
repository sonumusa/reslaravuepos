import Dexie from 'dexie';

// ═══════════════════════════════════════════════════════
// DATABASE SCHEMA
// ═══════════════════════════════════════════════════════
class PosDatabase extends Dexie {
    constructor() {
        super('ResLaraVuePOS');
        
        this.version(1).stores({
            // ═══════════════════════════════════════════════
            // CONFIGURATION (synced from server)
            // ═══════════════════════════════════════════════
            config: 'key',
            
            // ═══════════════════════════════════════════════
            // BRANCH DATA
            // ═══════════════════════════════════════════════
            branches: 'id, code, is_active',
            
            // ═══════════════════════════════════════════════
            // USER DATA
            // ═══════════════════════════════════════════════
            users: 'id, email, pin, branch_id, role',
            
            // ═══════════════════════════════════════════════
            // TABLES
            // ═══════════════════════════════════════════════
            posTables: 'id, branch_id, floor, status, is_active',
            
            // ═══════════════════════════════════════════════
            // MENU DATA
            // ═══════════════════════════════════════════════
            categories: 'id, branch_id, slug, sort_order, is_active',
            
            menuItems: 'id, category_id, branch_id, sku, barcode, is_available, is_featured',
            
            modifiers: 'id, branch_id, group_name, is_active',
            
            dailySpecials: 'id, menu_item_id, branch_id, start_date, end_date, is_active',
            
            // ═══════════════════════════════════════════════
            // CUSTOMERS
            // ═══════════════════════════════════════════════
            customers: 'id, phone, branch_id, synced',
            
            // ═══════════════════════════════════════════════
            // DISCOUNTS
            // ═══════════════════════════════════════════════
            discounts: 'id, code, branch_id, is_active',
            
            // ═══════════════════════════════════════════════
            // ORDERS (created offline)
            // ═══════════════════════════════════════════════
            orders: 'uuid, id, branch_id, table_id, status, synced, created_at',
            
            orderItems: '++id, order_uuid, menu_item_id',
            
            // ═══════════════════════════════════════════════
            // INVOICES (created offline)
            // ═══════════════════════════════════════════════
            invoices: 'uuid, id, order_uuid, branch_id, status, pra_status, synced, created_at',
            
            invoiceItems: '++id, invoice_uuid, menu_item_id',
            
            // ═══════════════════════════════════════════════
            // PAYMENTS (created offline)
            // ═══════════════════════════════════════════════
            payments: 'uuid, id, invoice_uuid, method, synced, created_at',
            
            // ═══════════════════════════════════════════════
            // SYNC QUEUE
            // ═══════════════════════════════════════════════
            syncQueue: '++id, entity_type, entity_uuid, action, created_at, retry_count',
            
            // ═══════════════════════════════════════════════
            // POS SESSIONS
            // ═══════════════════════════════════════════════
            posSessions: 'id, branch_id, terminal_id, user_id, status, opened_at',
            
            // ═══════════════════════════════════════════════
            // HELD ORDERS (local only)
            // ═══════════════════════════════════════════════
            heldOrders: 'uuid, table_id, created_at',
            
            // ═══════════════════════════════════════════════
            // PRINT QUEUE (local only)
            // ═══════════════════════════════════════════════
            printQueue: '++id, type, data, created_at',
        });
        
        // Map table classes
        this.config = this.table('config');
        this.branches = this.table('branches');
        this.users = this.table('users');
        this.posTables = this.table('posTables');
        this.categories = this.table('categories');
        this.menuItems = this.table('menuItems');
        this.modifiers = this.table('modifiers');
        this.dailySpecials = this.table('dailySpecials');
        this.customers = this.table('customers');
        this.discounts = this.table('discounts');
        this.orders = this.table('orders');
        this.orderItems = this.table('orderItems');
        this.invoices = this.table('invoices');
        this.invoiceItems = this.table('invoiceItems');
        this.payments = this.table('payments');
        this.syncQueue = this.table('syncQueue');
        this.posSessions = this.table('posSessions');
        this.heldOrders = this.table('heldOrders');
        this.printQueue = this.table('printQueue');
    }
}

// ═══════════════════════════════════════════════════════
// SINGLETON INSTANCE
// ═══════════════════════════════════════════════════════
let dbInstance = null;
export function useOfflineDb() {
    if (!dbInstance) {
        dbInstance = new PosDatabase();
    }
    return dbInstance;
}

// ═══════════════════════════════════════════════════════
// HELPER FUNCTIONS
// ═══════════════════════════════════════════════════════

/**
 * Initialize database
 */
export async function initDatabase() {
    const db = useOfflineDb();
    
    try {
        await db.open();
        console.log('IndexedDB opened successfully');
        
        // Check if we need to seed default data
        const configCount = await db.config.count();
        if (configCount === 0) {
            await seedDefaultConfig(db);
        }
        
        return true;
    } catch (e) {
        console.error('Failed to open IndexedDB:', e);
        return false;
    }
}

/**
 * Seed default configuration
 */
async function seedDefaultConfig(db) {
    await db.config.bulkPut([
        { key: 'db_version', value: '1.0.0' },
        { key: 'initialized_at', value: new Date().toISOString() },
        { key: 'last_sync_at', value: null },
    ]);
}

/**
 * Clear all data
 */
export async function clearDatabase() {
    const db = useOfflineDb();
    
    await db.transaction('rw', db.tables, async () => {
        for (const table of db.tables) {
            await table.clear();
        }
    });
    
    console.log('Database cleared');
}

/**
 * Export database for backup
 */
export async function exportDatabase() {
    const db = useOfflineDb();
    const data = {};
    
    for (const table of db.tables) {
        data[table.name] = await table.toArray();
    }
    
    return data;
}

/**
 * Import database from backup
 */
export async function importDatabase(data) {
    const db = useOfflineDb();
    
    await db.transaction('rw', db.tables, async () => {
        for (const [tableName, records] of Object.entries(data)) {
            const table = db.table(tableName);
            if (table && records.length) {
                await table.clear();
                await table.bulkPut(records);
            }
        }
    });
    
    console.log('Database imported');
}

/**
 * Get database size
 */
export async function getDatabaseSize() {
    const db = useOfflineDb();
    let totalSize = 0;
    
    for (const table of db.tables) {
        const records = await table.toArray();
        totalSize += JSON.stringify(records).length;
    }
    
    return {
        bytes: totalSize,
        kb: Math.round(totalSize / 1024),
        mb: Math.round(totalSize / (1024 * 1024) * 100) / 100,
    };
}

// ═══════════════════════════════════════════════════════
// ORDER OPERATIONS
// ═══════════════════════════════════════════════════════

/**
 * Save order to offline storage
 */
export async function saveOrderOffline(order) {
    const db = useOfflineDb();
    
    await db.transaction('rw', [db.orders, db.orderItems], async () => {
        // Save order
        await db.orders.put({
            ...order,
            synced: false,
            updated_at: new Date().toISOString(),
        });
        
        // Save order items
        if (order.items?.length) {
            for (const item of order.items) {
                await db.orderItems.put({
                    ...item,
                    order_uuid: order.uuid,
                });
            }
        }
    });
    
    // Add to sync queue
    await addToSyncQueue('order', order.uuid, 'create', order);
    
    return order;
}

/**
 * Get order with items
 */
export async function getOrderWithItems(uuid) {
    const db = useOfflineDb();
    
    const order = await db.orders.get(uuid);
    if (!order) return null;
    
    const items = await db.orderItems.where('order_uuid').equals(uuid).toArray();
    order.items = items;
    
    return order;
}

/**
 * Get all unsynced orders
 */
export async function getUnsyncedOrders() {
    const db = useOfflineDb();
    return await db.orders.where('synced').equals(false).toArray();
}

/**
 * Mark order as synced
 */
export async function markOrderSynced(uuid, serverId = null) {
    const db = useOfflineDb();
    
    await db.orders.update(uuid, {
        synced: true,
        id: serverId,
        synced_at: new Date().toISOString(),
    });
}

// ═══════════════════════════════════════════════════════
// INVOICE OPERATIONS
// ═══════════════════════════════════════════════════════

/**
 * Save invoice to offline storage
 */
export async function saveInvoiceOffline(invoice) {
    const db = useOfflineDb();
    
    await db.transaction('rw', [db.invoices, db.invoiceItems], async () => {
        // Save invoice
        await db.invoices.put({
            ...invoice,
            synced: false,
            pra_status: 'pending',
            updated_at: new Date().toISOString(),
        });
        
        // Save invoice items
        if (invoice.items?.length) {
            for (const item of invoice.items) {
                await db.invoiceItems.put({
                    ...item,
                    invoice_uuid: invoice.uuid,
                });
            }
        }
    });
    
    // Add to sync queue
    await addToSyncQueue('invoice', invoice.uuid, 'create', invoice);
    
    return invoice;
}

/**
 * Get invoice with items
 */
export async function getInvoiceWithItems(uuid) {
    const db = useOfflineDb();
    
    const invoice = await db.invoices.get(uuid);
    if (!invoice) return null;
    
    const items = await db.invoiceItems.where('invoice_uuid').equals(uuid).toArray();
    invoice.items = items;
    
    return invoice;
}

/**
 * Get all unsynced invoices
 */
export async function getUnsyncedInvoices() {
    const db = useOfflineDb();
    return await db.invoices.where('synced').equals(false).toArray();
}

/**
 * Get invoices pending PRA submission
 */
export async function getPraPendingInvoices() {
    const db = useOfflineDb();
    return await db.invoices
        .where('pra_status')
        .anyOf(['pending', 'failed'])
        .toArray();
}

// ═══════════════════════════════════════════════════════
// PAYMENT OPERATIONS
// ═══════════════════════════════════════════════════════

/**
 * Save payment to offline storage
 */
export async function savePaymentOffline(payment) {
    const db = useOfflineDb();
    
    await db.payments.put({
        ...payment,
        synced: false,
        created_at: new Date().toISOString(),
    });
    
    // Add to sync queue
    await addToSyncQueue('payment', payment.uuid, 'create', payment);
    
    return payment;
}

/**
 * Get all unsynced payments
 */
export async function getUnsyncedPayments() {
    const db = useOfflineDb();
    return await db.payments.where('synced').equals(false).toArray();
}

// ═══════════════════════════════════════════════════════
// SYNC QUEUE OPERATIONS
// ═══════════════════════════════════════════════════════

/**
 * Add item to sync queue
 */
export async function addToSyncQueue(entityType, entityUuid, action, data) {
    const db = useOfflineDb();
    
    await db.syncQueue.add({
        entity_type: entityType,
        entity_uuid: entityUuid,
        action: action,
        data: JSON.stringify(data),
        created_at: new Date().toISOString(),
        retry_count: 0,
        last_error: null,
    });
    
    // Dispatch event for sync status update
    window.dispatchEvent(new CustomEvent('sync:queued'));
}

/**
 * Get pending sync items
 */
export async function getPendingSyncItems() {
    const db = useOfflineDb();
    return await db.syncQueue.orderBy('created_at').toArray();
}

/**
 * Remove item from sync queue
 */
export async function removeFromSyncQueue(id) {
    const db = useOfflineDb();
    await db.syncQueue.delete(id);
}

/**
 * Update sync queue item retry count
 */
export async function updateSyncQueueRetry(id, error) {
    const db = useOfflineDb();
    
    const item = await db.syncQueue.get(id);
    if (item) {
        await db.syncQueue.update(id, {
            retry_count: item.retry_count + 1,
            last_error: error,
            last_retry_at: new Date().toISOString(),
        });
    }
}

/**
 * Get sync queue count
 */
export async function getSyncQueueCount() {
    const db = useOfflineDb();
    return await db.syncQueue.count();
}

// ═══════════════════════════════════════════════════════
// HELD ORDERS OPERATIONS
// ═══════════════════════════════════════════════════════

/**
 * Save held order
 */
export async function saveHeldOrder(order) {
    const db = useOfflineDb();
    
    await db.heldOrders.put({
        ...order,
        created_at: new Date().toISOString(),
    });
    
    return order;
}

/**
 * Get all held orders
 */
export async function getHeldOrders() {
    const db = useOfflineDb();
    return await db.heldOrders.orderBy('created_at').reverse().toArray();
}

/**
 * Delete held order
 */
export async function deleteHeldOrder(uuid) {
    const db = useOfflineDb();
    await db.heldOrders.delete(uuid);
}

// ═══════════════════════════════════════════════════════
// PRINT QUEUE OPERATIONS
// ═══════════════════════════════════════════════════════

/**
 * Add to print queue
 */
export async function addToPrintQueue(type, data) {
    const db = useOfflineDb();
    
    return await db.printQueue.add({
        type, // 'receipt', 'kitchen', 'report'
        data: JSON.stringify(data),
        created_at: new Date().toISOString(),
        printed: false,
    });
}

/**
 * Get pending prints
 */
export async function getPendingPrints() {
    const db = useOfflineDb();
    return await db.printQueue.where('printed').equals(false).toArray();
}

/**
 * Mark as printed
 */
export async function markAsPrinted(id) {
    const db = useOfflineDb();
    await db.printQueue.update(id, { printed: true });
}

// ═══════════════════════════════════════════════════════
// MENU OPERATIONS
// ═══════════════════════════════════════════════════════

/**
 * Get menu for offline use
 */
export async function getOfflineMenu() {
    const db = useOfflineDb();
    
    const categories = await db.categories
        .where('is_active')
        .equals(1)
        .sortBy('sort_order');
    
    const menuItems = await db.menuItems
        .where('is_available')
        .equals(1)
        .toArray();
    
    const modifiers = await db.modifiers
        .where('is_active')
        .equals(1)
        .toArray();
    
    return { categories, menuItems, modifiers };
}

/**
 * Search menu items offline
 */
export async function searchMenuOffline(query) {
    const db = useOfflineDb();
    const lowerQuery = query.toLowerCase();
    
    return await db.menuItems
        .filter(item =>
            item.name.toLowerCase().includes(lowerQuery) ||
            item.short_name?.toLowerCase().includes(lowerQuery) ||
            item.sku?.toLowerCase().includes(lowerQuery)
        )
        .toArray();
}

/**
 * Find item by barcode
 */
export async function findByBarcodeOffline(barcode) {
    const db = useOfflineDb();
    return await db.menuItems.where('barcode').equals(barcode).first();
}

// ═══════════════════════════════════════════════════════
// TABLE OPERATIONS
// ═══════════════════════════════════════════════════════

/**
 * Get tables for offline use
 */
export async function getOfflineTables(branchId) {
    const db = useOfflineDb();
    
    return await db.posTables
        .where('branch_id')
        .equals(branchId)
        .and(t => t.is_active)
        .toArray();
}

/**
 * Update table status offline
 */
export async function updateTableStatusOffline(tableId, status) {
    const db = useOfflineDb();
    await db.posTables.update(tableId, { status });
}

// ═══════════════════════════════════════════════════════
// EXPORT DEFAULT
// ═══════════════════════════════════════════════════════
export default {
    useOfflineDb,
    initDatabase,
    clearDatabase,
    exportDatabase,
    importDatabase,
    getDatabaseSize,
    saveOrderOffline,
    getOrderWithItems,
    getUnsyncedOrders,
    markOrderSynced,
    saveInvoiceOffline,
    getInvoiceWithItems,
    getUnsyncedInvoices,
    getPraPendingInvoices,
    savePaymentOffline,
    getUnsyncedPayments,
    addToSyncQueue,
    getPendingSyncItems,
    removeFromSyncQueue,
    getSyncQueueCount,
    saveHeldOrder,
    getHeldOrders,
    deleteHeldOrder,
    addToPrintQueue,
    getPendingPrints,
    markAsPrinted,
    getOfflineMenu,
    searchMenuOffline,
    findByBarcodeOffline,
    getOfflineTables,
    updateTableStatusOffline,
};
