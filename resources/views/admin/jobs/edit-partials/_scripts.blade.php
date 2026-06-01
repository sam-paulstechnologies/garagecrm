<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('jobEditForm');
    const statusSelect = document.getElementById('job_status');

    const modal = document.getElementById('invoiceModal');
    const cancelBtn = document.getElementById('cancelInvoiceModal');
    const confirmBtn = document.getElementById('confirmInvoiceModal');

    const modalInvoiceNumber = document.getElementById('modal_invoice_number');
    const modalInvoiceAmount = document.getElementById('modal_invoice_amount');

    const hiddenInvoiceNumber = document.getElementById('hidden_invoice_number');
    const hiddenInvoiceAmount = document.getElementById('hidden_invoice_amount');

    const error = document.getElementById('invoiceModalError');

    let allowSubmit = false;

    function openModal() {
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        error.classList.add('hidden');
        setTimeout(() => modalInvoiceNumber.focus(), 100);
    }

    function closeModal() {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        error.classList.add('hidden');
    }

    form.addEventListener('submit', function (e) {
        if (allowSubmit) {
            return true;
        }

        if (statusSelect.value === 'completed') {
            const invoiceNumber = hiddenInvoiceNumber.value.trim();
            const invoiceAmount = hiddenInvoiceAmount.value.trim();

            if (!invoiceNumber || !invoiceAmount || parseFloat(invoiceAmount) <= 0) {
                e.preventDefault();
                openModal();
                return false;
            }
        }
    });

    cancelBtn.addEventListener('click', function () {
        closeModal();
    });

    confirmBtn.addEventListener('click', function () {
        const invoiceNumber = modalInvoiceNumber.value.trim();
        const invoiceAmount = modalInvoiceAmount.value.trim();

        if (!invoiceNumber || !invoiceAmount || parseFloat(invoiceAmount) <= 0) {
            error.classList.remove('hidden');
            return;
        }

        hiddenInvoiceNumber.value = invoiceNumber;
        hiddenInvoiceAmount.value = invoiceAmount;

        allowSubmit = true;
        form.submit();
    });
});
</script>
