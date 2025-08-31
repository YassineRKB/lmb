document.addEventListener('DOMContentLoaded', function() {
    // Use a more specific selector to avoid conflicts if multiple widgets are on the page
    document.querySelectorAll('.lmb-my-legal-ads-v2').forEach(function(widget) {
        
        // Tab functionality
        const tabButtons = widget.querySelectorAll('.lmb-tab-btn-user');
        const tabPanes = widget.querySelectorAll('.lmb-tab-pane');

        tabButtons.forEach(button => {
            button.addEventListener('click', function() {
                tabButtons.forEach(btn => btn.classList.remove('active'));
                tabPanes.forEach(pane => pane.classList.remove('active'));
                this.classList.add('active');
                const targetPane = widget.querySelector(this.getAttribute('data-target'));
                if (targetPane) {
                    targetPane.classList.add('active');
                }
            });
        });

        // Clickable row functionality
        const tables = widget.querySelectorAll('.lmb-data-table');
        tables.forEach(table => {
            const tbody = table.querySelector('tbody');
            if(tbody) {
                tbody.addEventListener('click', function(e) {
                    // Prevent row click if a button or link inside the row was clicked
                    if (e.target.closest('button, a')) {
                        return;
                    }
                    
                    const row = e.target.closest('tr.clickable-row');
                    if (row) {
                        // In a real scenario, you'd get the URL from a data attribute
                        console.log('Row clicked:', row.cells[0].textContent);
                        // Example action: window.location.href = '#'; 
                    }
                });
            }
        });
    });
});

