/**
 * Universal List Filter & Search Module
 * Provides client-side filtering and search for table-based list views
 */

class ListFilter {
    constructor(config) {
        this.config = {
            tableSelector: config.tableSelector || 'table tbody',
            searchInputId: config.searchInputId,
            perPageSelectId: config.perPageSelectId,
            paginationContainerId: config.paginationContainerId,
            noResultsMessage: config.noResultsMessage || 'No results found',
            searchColumns: config.searchColumns || [],
            filterSelects: config.filterSelects || [],
            storageKey: config.storageKey || 'listFilter'
        };

        this.currentPage = 1;
        this.perPage = 10;
        this.allRows = [];
        this.filteredRows = [];
        
        this.init();
    }

    init() {
        console.log('ListFilter init started for:', this.config.tableSelector);
        
        this.cacheRows();
        
        if (this.allRows.length === 0) {
            console.error('No rows found for ListFilter:', this.config.tableSelector);
            return;
        }
        
        console.log('Found', this.allRows.length, 'rows');
        
        this.setInitialPerPage();
        this.setupEventListeners();
        this.applyFilters();
        
        console.log(`ListFilter initialized: ${this.allRows.length} rows cached, perPage: ${this.perPage}`);
    }

    cacheRows() {
        const tbody = document.querySelector(this.config.tableSelector);
        console.log('Looking for tbody:', this.config.tableSelector, 'Found:', !!tbody);
        
        if (!tbody) return;
        
        this.tbody = tbody;
        const allTrs = tbody.querySelectorAll('tr');
        console.log('Total tr elements found:', allTrs.length);
        
        this.allRows = Array.from(allTrs).filter(row => {
            const firstCell = row.querySelector('td');
            if (!firstCell) {
                console.log('Skipping row without td');
                return false;
            }
            
            // Skip colspan rows (no results messages)
            if (firstCell.getAttribute('colspan')) {
                console.log('Skipping colspan row');
                return false;
            }
            
            return true;
        });
        
        console.log('Filtered to', this.allRows.length, 'data rows');
        this.filteredRows = [...this.allRows];
    }

    setInitialPerPage() {
        const select = document.getElementById(this.config.perPageSelectId);
        if (select && select.value) {
            this.perPage = select.value === 'all' ? Infinity : parseInt(select.value) || 10;
        }
    }

    setupEventListeners() {
        // Search
        const searchInput = document.getElementById(this.config.searchInputId);
        if (searchInput) {
            searchInput.addEventListener('input', () => {
                this.currentPage = 1;
                this.applyFilters();
            });
        }
        
        // Per page
        const perPageSelect = document.getElementById(this.config.perPageSelectId);
        if (perPageSelect) {
            perPageSelect.addEventListener('change', (e) => {
                this.perPage = e.target.value === 'all' ? Infinity : parseInt(e.target.value) || 10;
                this.currentPage = 1;
                this.applyFilters();
            });
        }
        
        // Filter dropdowns
        this.config.filterSelects.forEach(filter => {
            const select = document.getElementById(filter.selectId);
            if (select) {
                select.addEventListener('change', () => {
                    this.currentPage = 1;
                    this.applyFilters();
                });
            }
        });
    }

    applyFilters() {
        console.log('applyFilters started with', this.allRows.length, 'rows');
        this.filteredRows = [...this.allRows];
        console.log('Initial filteredRows:', this.filteredRows.length);
        
        // Search filter
        const searchTerm = this.getSearchTerm();
        console.log('Search term:', searchTerm);
        if (searchTerm) {
            this.filteredRows = this.filteredRows.filter(row => this.matchesSearch(row, searchTerm));
            console.log('After search filter:', this.filteredRows.length);
        }
        
        // Dropdown filters
        this.config.filterSelects.forEach(filter => {
            const select = document.getElementById(filter.selectId);
            console.log('Checking filter:', filter.selectId, 'element found:', !!select);
            if (select) {
                console.log('Filter value:', select.value);
                if (select.value && select.value !== 'all' && select.value !== 'All' && select.value !== '') {
                    const beforeCount = this.filteredRows.length;
                    this.filteredRows = this.filteredRows.filter(row => {
                        const cell = row.cells[filter.columnIndex];
                        if (!cell) return false;
                        const cellText = cell.textContent.trim().toLowerCase();
                        const matches = cellText.includes(select.value.toLowerCase());
                        return matches;
                    });
                    console.log('Filter', filter.selectId, 'reduced from', beforeCount, 'to', this.filteredRows.length);
                }
            }
        });
        
        console.log('Final filteredRows before render:', this.filteredRows.length);
        this.render();
    }

    getSearchTerm() {
        const input = document.getElementById(this.config.searchInputId);
        return input ? input.value.trim().toLowerCase() : '';
    }

    matchesSearch(row, searchTerm) {
        const columnsToSearch = this.config.searchColumns.length > 0 
            ? this.config.searchColumns 
            : Array.from({ length: row.cells.length }, (_, i) => i);
        
        return columnsToSearch.some(colIndex => {
            const cell = row.cells[colIndex];
            if (!cell) return false;
            return cell.textContent.trim().toLowerCase().includes(searchTerm);
        });
    }

    render() {
        console.log('Render called - filtered rows:', this.filteredRows.length, 'perPage:', this.perPage, 'currentPage:', this.currentPage);
        
        // Hide all rows
        this.allRows.forEach(row => row.style.display = 'none');
        console.log('Hidden all', this.allRows.length, 'rows');
        
        // Remove existing no-results
        this.removeNoResults();
        
        // Handle empty results
        if (this.filteredRows.length === 0) {
            this.showNoResults();
            this.renderPagination();
            return;
        }
        
        // Calculate pagination
        const totalPages = this.perPage === Infinity ? 1 : Math.ceil(this.filteredRows.length / this.perPage);
        this.currentPage = Math.min(this.currentPage, Math.max(1, totalPages));
        
        // Show visible rows
        const startIndex = (this.currentPage - 1) * this.perPage;
        const endIndex = this.perPage === Infinity ? this.filteredRows.length : startIndex + this.perPage;
        
        console.log('Showing rows from index', startIndex, 'to', endIndex, 'of', this.filteredRows.length);
        
        const visibleRows = this.filteredRows.slice(startIndex, endIndex);
        console.log('Visible rows count:', visibleRows.length);
        
        visibleRows.forEach((row, index) => {
            row.style.display = '';
            // Update row numbers
            const firstCell = row.cells[0];
            if (firstCell && /^\d+$/.test(firstCell.textContent.trim())) {
                firstCell.textContent = startIndex + index + 1;
            }
        });
        
        console.log('Showed', visibleRows.length, 'rows');
        
        this.renderPagination();
    }

    showNoResults() {
        const colSpan = this.allRows[0]?.cells.length || 5;
        const row = document.createElement('tr');
        row.className = 'no-results-row';
        row.innerHTML = `<td colspan="${colSpan}" class="text-center text-gray-500 py-4">${this.config.noResultsMessage}</td>`;
        this.tbody.appendChild(row);
    }

    removeNoResults() {
        const existing = this.tbody.querySelector('.no-results-row');
        if (existing) existing.remove();
    }

    renderPagination() {
        const container = document.getElementById(this.config.paginationContainerId);
        if (!container) return;
        
        const total = this.filteredRows.length;
        
        if (total === 0) {
            container.innerHTML = '<div class="text-sm text-black">No entries to show</div>';
            return;
        }
        
        if (this.perPage === Infinity) {
            container.innerHTML = `<div class="text-sm text-black">Showing all ${total} entries</div>`;
            return;
        }
        
        const totalPages = Math.ceil(total / this.perPage);
        const start = (this.currentPage - 1) * this.perPage + 1;
        const end = Math.min(this.currentPage * this.perPage, total);
        
        let html = `
            <div class="flex justify-between items-center">
                <div class="text-sm text-black">Showing ${start} to ${end} of ${total} entries</div>
                <div class="flex border border-gray-300 rounded">
        `;
        
        // Previous
        html += `
            <button onclick="window.listFilters['${this.getFilterKey()}'].goToPage(${this.currentPage - 1})" 
                ${this.currentPage === 1 ? 'disabled' : ''} 
                class="px-3 py-1 bg-white hover:bg-gray-100 border-r ${this.currentPage === 1 ? 'opacity-50' : ''}">
                Previous
            </button>
        `;
        
        // Pages (simple version)
        for (let i = 1; i <= Math.min(totalPages, 5); i++) {
            const active = i === this.currentPage ? 'bg-blue-500 text-white' : 'bg-white hover:bg-gray-100';
            html += `
                <button onclick="window.listFilters['${this.getFilterKey()}'].goToPage(${i})" 
                    class="px-3 py-1 ${active} border-r">${i}</button>
            `;
        }
        
        // Next
        html += `
            <button onclick="window.listFilters['${this.getFilterKey()}'].goToPage(${this.currentPage + 1})" 
                ${this.currentPage === totalPages ? 'disabled' : ''} 
                class="px-3 py-1 bg-white hover:bg-gray-100 ${this.currentPage === totalPages ? 'opacity-50' : ''}">
                Next
            </button>
        `;
        
        html += '</div></div>';
        container.innerHTML = html;
    }

    getFilterKey() {
        return this.config.storageKey.replace('Filter', '');
    }

    goToPage(page) {
        const totalPages = this.perPage === Infinity ? 1 : Math.ceil(this.filteredRows.length / this.perPage);
        this.currentPage = Math.max(1, Math.min(page, totalPages));
        this.render();
    }

    refresh() {
        this.cacheRows();
        this.applyFilters();
    }
}

// Global storage
window.listFilters = window.listFilters || {};