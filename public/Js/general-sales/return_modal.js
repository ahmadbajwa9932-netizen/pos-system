// Return Modal Functions
function openReturnModal(saleId, voucherNo) {
    document.getElementById('returnSaleId').value = saleId;
    document.getElementById('returnVoucherNo').textContent = voucherNo;
    document.getElementById('returnModal').style.display = 'flex';
    document.getElementById('returnModal').setAttribute('aria-hidden', 'false');
}

function closeReturnModal() {
    document.getElementById('returnModal').style.display = 'none';
    document.getElementById('returnModal').setAttribute('aria-hidden', 'true');
    document.getElementById('returnSaleId').value = '';
    document.getElementById('returnVoucherNo').textContent = '';
}

function submitFullReturn() {
    const saleId = document.getElementById('returnSaleId').value;
    const voucherNo = document.getElementById('returnVoucherNo').textContent;
    closeReturnModal();
    processFullReturn(saleId, voucherNo);
}

function submitPartialReturn() {
    const saleId = document.getElementById('returnSaleId').value;
    closeReturnModal();
    window.location.href = `/sales/returns/partial/${saleId}`;
}

// Close modal when clicking outside
window.onclick = function(event) {
    const returnModal = document.getElementById('returnModal');
    if (event.target == returnModal) {
        closeReturnModal();
    }
}