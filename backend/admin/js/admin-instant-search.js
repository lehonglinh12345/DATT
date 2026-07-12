/**
 * Client-side Real-time Table Filter (Instant Search)
 */
function enableInstantSearch(inputId, tableId) {
    const input = document.getElementById(inputId);
    const table = document.getElementById(tableId);
    if (!input || !table) return;

    input.addEventListener('input', function() {
        const query = input.value.toLowerCase().trim();
        const rows = table.querySelectorAll('tbody tr');
        
        // Find if there is a 'no results' row
        let noResultsRow = table.querySelector('.instant-search-no-results');
        
        let visibleCount = 0;

        rows.forEach(row => {
            // Ignore the custom "no results" row during comparison
            if (row.classList.contains('instant-search-no-results')) {
                return;
            }

            const cells = row.querySelectorAll('td');
            let found = false;

            for (let i = 0; i < cells.length; i++) {
                // Skip actions column text to avoid matching icon class names or action labels
                if (cells[i].classList.contains('actions-cell') || cells[i].textContent.trim() === '') {
                    continue;
                }

                if (cells[i].textContent.toLowerCase().includes(query)) {
                    found = true;
                    break;
                }
            }

            if (found || query === '') {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });

        // Handle displaying a "no results" placeholder row inside tbody
        if (visibleCount === 0 && query !== '') {
            if (!noResultsRow) {
                const tbody = table.querySelector('tbody');
                if (tbody) {
                    const colSpan = table.querySelectorAll('thead th').length || 8;
                    noResultsRow = document.createElement('tr');
                    noResultsRow.className = 'instant-search-no-results';
                    noResultsRow.innerHTML = `
                        <td colspan="${colSpan}" style="text-align: center; padding: 2.5rem; color: var(--color-admin-text-muted);">
                            <i class="fa-solid fa-magnifying-glass-minus" style="font-size: 2rem; display: block; margin-bottom: 0.5rem; opacity: 0.5;"></i>
                            Không tìm thấy dữ liệu khớp với từ khóa "<strong>${input.value}</strong>"
                        </td>
                    `;
                    tbody.appendChild(noResultsRow);
                }
            } else {
                noResultsRow.style.display = '';
                noResultsRow.querySelector('strong').textContent = input.value;
            }
        } else if (noResultsRow) {
            noResultsRow.style.display = 'none';
        }
    });
}
