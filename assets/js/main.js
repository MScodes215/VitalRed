/**
 * VitalRed - General Application JavaScript
 */

document.addEventListener('DOMContentLoaded', function () {
    // Initialize Bootstrap tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Auto-dismiss alerts after 5 seconds
    const autoAlerts = document.querySelectorAll('.alert-dismissible');
    autoAlerts.forEach(alert => {
        setTimeout(() => {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });

    // Live table search filtering
    const tableSearch = document.getElementById('tableSearchInput');
    if (tableSearch) {
        tableSearch.addEventListener('keyup', function () {
            const query = this.value.toLowerCase();
            const rows = document.querySelectorAll('tbody tr');
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(query) ? '' : 'none';
            });
        });
    }

    // Role-dependent registration form toggle
    const roleSelector = document.getElementById('reg_role');
    if (roleSelector) {
        const donorFields = document.getElementById('donor_specific_fields');
        const hospitalFields = document.getElementById('hospital_specific_fields');
        
        function updateRegFields() {
            if (roleSelector.value === 'donor') {
                if (donorFields) donorFields.style.display = 'block';
                if (hospitalFields) hospitalFields.style.display = 'none';
            } else if (roleSelector.value === 'requester') {
                if (donorFields) donorFields.style.display = 'none';
                if (hospitalFields) hospitalFields.style.display = 'block';
            }
        }
        
        roleSelector.addEventListener('change', updateRegFields);
        updateRegFields();
    }
});

/**
 * Print Report Helper
 */
function printReport() {
    window.print();
}

/**
 * Export HTML Table to CSV Helper
 */
function exportTableToCSV(tableId, filename = 'vitalred_report.csv') {
    const table = document.getElementById(tableId);
    if (!table) return;

    let csv = [];
    const rows = table.querySelectorAll('tr');

    for (let i = 0; i < rows.length; i++) {
        let row = [], cols = rows[i].querySelectorAll('td, th');
        for (let j = 0; j < cols.length; j++) {
            // Remove extra whitespace and newlines
            let data = cols[j].innerText.replace(/(\r\n|\n|\r)/gm, '').replace(/(\s\s+)/gm, ' ');
            data = data.replace(/"/g, '""');
            row.push('"' + data + '"');
        }
        csv.push(row.join(','));
    }

    const csvFile = new Blob([csv.join('\n')], { type: 'text/csv' });
    const downloadLink = document.createElement('a');
    downloadLink.download = filename;
    downloadLink.href = window.URL.createObjectURL(csvFile);
    downloadLink.style.display = 'none';
    document.body.appendChild(downloadLink);
    downloadLink.click();
    document.body.removeChild(downloadLink);
}
