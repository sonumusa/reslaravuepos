export const printService = {
    async print(invoice) {
        return this.printReceipt(invoice);
    },

    printReceipt(invoice) {
        // Simple browser print for now
        // In a real app, this would generate HTML or use a thermal printer library
        const printWindow = window.open('', '_blank', 'width=350,height=600');
        printWindow.document.write(this.generateReceiptHtml(invoice));
        printWindow.document.close();
        
        // Wait for images/resources to load
        setTimeout(() => {
            printWindow.focus();
            printWindow.print();
            // Optional: printWindow.close();
        }, 500);
    },

    generateReceiptHtml(invoice) {
        const branch = invoice.branch || {};
        const items = invoice.items || [];
        const date = new Date(invoice.created_at || Date.now()).toLocaleString();
        
        return `
            <!DOCTYPE html>
            <html>
            <head>
                <title>Receipt ${invoice.invoice_number}</title>
                <style>
                    body { 
                        font-family: 'Courier New', monospace; 
                        font-size: 12px; 
                        width: 80mm; 
                        margin: 0;
                        padding: 10px;
                        background: white;
                    }
                    .header { text-align: center; margin-bottom: 15px; }
                    .header h2 { margin: 0 0 5px 0; font-size: 16px; }
                    .header p { margin: 2px 0; }
                    
                    .info { margin-bottom: 10px; border-bottom: 1px dashed #000; padding-bottom: 5px; }
                    .info div { display: flex; justify-content: space-between; }
                    
                    .items-header { font-weight: bold; border-bottom: 1px solid #000; margin-bottom: 5px; padding-bottom: 2px; }
                    .item { margin-bottom: 5px; }
                    .item-row { display: flex; justify-content: space-between; }
                    .item-mods { font-size: 10px; padding-left: 15px; font-style: italic; }
                    
                    .totals { margin-top: 10px; border-top: 1px dashed #000; padding-top: 5px; }
                    .total-row { display: flex; justify-content: space-between; margin-bottom: 2px; }
                    .grand-total { font-weight: bold; font-size: 14px; margin-top: 5px; border-top: 1px solid #000; padding-top: 5px; }
                    
                    .footer { text-align: center; margin-top: 20px; font-size: 10px; }
                    .qr-code { text-align: center; margin: 15px 0; }
                    .qr-code img { max-width: 100px; height: auto; }
                    
                    @media print {
                        @page { margin: 0; }
                        body { width: 100%; }
                    }
                </style>
            </head>
            <body>
                <div class="header">
                    <h2>${branch.name || 'Restaurant Name'}</h2>
                    <p>${branch.address || ''}</p>
                    <p>${branch.phone || ''}</p>
                    ${branch.ntn_number ? `<p>NTN: ${branch.ntn_number}</p>` : ''}
                </div>

                <div class="info">
                    <div><span>Inv:</span> <span>${invoice.invoice_number}</span></div>
                    <div><span>Date:</span> <span>${date}</span></div>
                    ${invoice.order?.table ? `<div><span>Table:</span> <span>${invoice.order.table.name}</span></div>` : ''}
                    ${invoice.customer ? `<div><span>Cust:</span> <span>${invoice.customer.name}</span></div>` : ''}
                </div>

                <div class="items">
                    <div class="items-header item-row">
                        <span>Item</span>
                        <span>Amt</span>
                    </div>
                    ${items.map(item => `
                        <div class="item">
                            <div class="item-row">
                                <span>${item.quantity} x ${item.item_name}</span>
                                <span>${parseFloat(item.subtotal).toFixed(2)}</span>
                            </div>
                            ${item.modifiers?.length ? 
                                item.modifiers.map(mod => `
                                    <div class="item-mods">+ ${mod.modifier_name}</div>
                                `).join('') 
                            : ''}
                        </div>
                    `).join('')}
                </div>

                <div class="totals">
                    <div class="total-row">
                        <span>Subtotal</span>
                        <span>${parseFloat(invoice.subtotal).toFixed(2)}</span>
                    </div>
                    ${invoice.discount_amount > 0 ? `
                        <div class="total-row">
                            <span>Discount</span>
                            <span>-${parseFloat(invoice.discount_amount).toFixed(2)}</span>
                        </div>
                    ` : ''}
                    <div class="total-row">
                        <span>Tax (${invoice.tax_rate}%)</span>
                        <span>${parseFloat(invoice.tax_amount).toFixed(2)}</span>
                    </div>
                    <div class="total-row grand-total">
                        <span>TOTAL</span>
                        <span>${parseFloat(invoice.total_amount).toFixed(2)}</span>
                    </div>
                    <div class="total-row" style="margin-top: 5px; font-size: 11px;">
                        <span>Payment:</span>
                        <span>${invoice.payment_method?.toUpperCase() || 'CASH'}</span>
                    </div>
                </div>

                ${invoice.pra_qr_code ? `
                    <div class="qr-code">
                        <img src="${invoice.pra_qr_code}" alt="PRA QR" />
                        <p style="font-size: 10px; margin-top: 2px;">FBR Invoice: ${invoice.pra_fiscal_invoice_number}</p>
                    </div>
                ` : ''}

                <div class="footer">
                    <p>Thank You! Please Come Again</p>
                    <p>Powered by ResLaraVuePOS</p>
                </div>
            </body>
            </html>
        `;
    }
};

export default printService;
