// Global scripts for Mkombozi HRMIS
document.addEventListener('DOMContentLoaded', function () {
    // Auto-dismiss alerts
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach((el) => {
        setTimeout(() => {
            el.classList.add('fade');
            setTimeout(() => el.remove(), 500);
        }, 4000);
    });

    // Enable Bootstrap tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Simple table filters: add input with [data-table-filter="#tableId"]
    document.querySelectorAll('input[data-table-filter]').forEach((input) => {
        const tableSelector = input.getAttribute('data-table-filter');
        const table = document.querySelector(tableSelector);
        if (!table) return;
        input.addEventListener('input', () => {
            const needle = input.value.toLowerCase();
            table.querySelectorAll('tbody tr').forEach((row) => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(needle) ? '' : 'none';
            });
        });
    });
});


