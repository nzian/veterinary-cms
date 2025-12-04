/**
 * Universal List Filter & Search Module
 * Provides client-side filtering and search for table-based list views
 * No form submissions required - all operations happen in JavaScript
 */

class ListFilter {
    constructor(config) {
        this.config = {
            tableSelector: config.tableSelector || 'table tbody',
            searchInputId: config.searchInputId,
            perPageSelectId: config.perPageSelectId,
            paginationContainerId: config.paginationContainerId,
            noResultsMessage: config.noResultsMessage || 'No results found',
            searchColumns: config.searchColumns || [], // Array of column indices to search
            filterSelects: config.filterSelects || [], // Array of {selectId, columnIndex}
            currentPage: 1,
            perPage: 10,
            storageKey: config.storageKey || 'listFilter'
        };

        this.allRows = [];
        this.filteredRows = [];
        this.init();
    }

    init() {
        // Store all rows
        this.cacheRows();
        
        // Read current perPage value from dropdown
        const perPageSelect = document.getElementById(this.config.perPageSelectId);
        if (perPageSelect) {
            const currentValue = perPageSelect.value;
            this.config.perPage = currentValue === 'all' ? Infinity : parseInt(currentValue) || 10;
        }
        
        // Restore previous settings
        this.restoreSettings();
        
        // Setup event listeners
        this.setupSearchListener();
        this.setupPerPageListener();
        this.setupFilterListeners();
        
        // Initial render
        this.applyFilters();
    }

    cacheRows() {
        const tbody = document.querySelector(this.config.tableSelector);
        if (!tbody) return;
        
        // Get all data rows (skip "no results" rows) - keep original references, don't clone
        this.allRows = Array.from(tbody.querySelectorAll('tr')).filter(row => {
            return !row.querySelector('td[colspan]') || row.querySelector('td[colspan]').textContent.trim() !== this.config.noResultsMessage;
        });
        
        this.filteredRows = [...this.allRows];
    }

    setupSearchListener() {
        const searchInput = document.getElementById(this.config.searchInputId);
        if (!searchInput) return;
        
        searchInput.addEventListener('input', (e) => {
            this.saveSettings();
            this.applyFilters();
        });
    }

    setupPerPageListener() {
        const perPageSelect = document.getElementById(this.config.perPageSelectId);
        if (!perPageSelect) return;
        
        // Prevent form submission
        const form = perPageSelect.closest('form');
        if (form) {
            form.addEventListener('submit', (e) => {
                e.preventDefault();
            });
        }
        
        perPageSelect.addEventListener('change', (e) => {
            this.config.perPage = e.target.value === 'all' ? Infinity : parseInt(e.target.value);
            this.currentPage = 1;
            this.saveSettings();
            this.applyFilters();
        });
    }

    setupFilterListeners() {
        this.config.filterSelects.forEach(filter => {
            const select = document.getElementById(filter.selectId);
            if (!select) return;
            
            // Prevent form submission
            const form = select.closest('form');
            if (form) {
                form.addEventListener('submit', (e) => {
                    e.preventDefault();
                });
            }
            
            select.addEventListener('change', () => {
                this.currentPage = 1;
                this.saveSettings();
                this.applyFilters();
            });
        });
    }

    applyFilters() {
        // Start with all rows
        this.filteredRows = [...this.allRows];
        
        // Apply search filter
        const searchTerm = this.getSearchTerm();
        if (searchTerm) {
            this.filteredRows = this.filteredRows.filter(row => {
                return this.rowMatchesSearch(row, searchTerm);
            });
        }
        
        // Apply dropdown filters
        this.config.filterSelects.forEach(filter => {
            const select = document.getElementById(filter.selectId);
            if (!select) return;
            
            const filterValue = select.value.toLowerCase();
            if (filterValue && filterValue !== 'all') {
                this.filteredRows = this.filteredRows.filter(row => {
                    const cell = row.cells[filter.columnIndex];
                    if (!cell) return false;
                    
                    const cellText = cell.textContent.trim().toLowerCase();
                    return cellText.includes(filterValue) || cellText === filterValue;
                });
            }
        });
        
        // Render results
        this.render();
    }

    getSearchTerm() {
        const searchInput = document.getElementById(this.config.searchInputId);
        return searchInput ? searchInput.value.trim().toLowerCase() : '';
    }

    rowMatchesSearch(row, searchTerm) {
        // If no specific columns defined, search all columns
        const columnsToSearch = this.config.searchColumns.length > 0 
            ? this.config.searchColumns 
            : Array.from({ length: row.cells.length }, (_, i) => i);
        
        return columnsToSearch.some(colIndex => {
            const cell = row.cells[colIndex];
            if (!cell) return false;
            
            const cellText = cell.textContent.trim().toLowerCase();
            return cellText.includes(searchTerm);
        });
    }

    render() {
        const tbody = document.querySelector(this.config.tableSelector);
        if (!tbody) return;
        //console.log(tbody);
        
        // Handle no results
        if (this.filteredRows.length === 0) {
            tbody.innerHTML = '';
            const colSpan = this.allRows[0]?.cells.length || 5;
            tbody.innerHTML = `
                <tr>
                    <td colspan="${colSpan}" class="text-center text-gray-500 py-4">
                        ${this.config.noResultsMessage}
                    </td>
                </tr>
            `;
            this.renderPagination();
            return;
        }
        
        // Calculate pagination
        const totalPages = Math.ceil(this.filteredRows.length / this.config.perPage);
        this.currentPage = Math.min(this.currentPage, totalPages);
       // console.log(totalPages);
        
        const startIndex = (this.currentPage - 1) * this.config.perPage;
        const endIndex = this.config.perPage === Infinity 
            ? this.filteredRows.length 
            : Math.min(startIndex + this.config.perPage, this.filteredRows.length);
        
        // Get visible rows that should be displayed
        const visibleRows = this.filteredRows.slice(startIndex, endIndex);
        
        // Hide all rows first
        this.allRows.forEach(row => {
            row.style.display = 'none';
        });
        
        // Show only visible rows and update row numbers
        visibleRows.forEach((row, index) => {
            row.style.display = '';
            // Update row number in first cell (# column)
            const firstCell = row.cells[0];
            if (firstCell && firstCell.textContent.trim().match(/^\d+$/)) {
                firstCell.textContent = startIndex + index + 1;
            }
        });
        
        // Render pagination
        this.renderPagination();
    }

    renderPagination() {
        const container = document.getElementById(this.config.paginationContainerId);
        console.log(container);
        if (!container) return;
        
        const totalRows = this.filteredRows.length;
        const totalPages = Math.ceil(totalRows / this.config.perPage);
        
        if (this.config.perPage === Infinity || totalPages <= 1) {
            container.innerHTML = `
                <div class="text-sm font-semibold text-black">
                    Showing ${totalRows} of ${totalRows} entries
                </div>
            `;
            return;
        }
        
        const startIndex = (this.currentPage - 1) * this.config.perPage + 1;
        const endIndex = Math.min(this.currentPage * this.config.perPage, totalRows);
        
        // Generate pagination HTML
        let paginationHTML = `
            <div class="flex justify-between items-center w-full">
                <div class="text-sm font-semibold text-black">
                    Showing ${startIndex} to ${endIndex} of ${totalRows} entries
                </div>
                <div class="inline-flex border border-gray-400 rounded overflow-hidden">
        `;
        
        // Previous button
        paginationHTML += `
            <button onclick="listFilters['${this.config.storageKey}'].goToPage(${this.currentPage - 1})" 
                ${this.currentPage === 1 ? 'disabled' : ''} 
                class="px-3 py-1 bg-white hover:bg-gray-100 border-r border-gray-400 ${this.currentPage === 1 ? 'opacity-50 cursor-not-allowed' : ''}">
                Previous
            </button>
        `;
        
        // Page numbers
        const maxVisiblePages = 5;
        let startPage = Math.max(1, this.currentPage - Math.floor(maxVisiblePages / 2));
        let endPage = Math.min(totalPages, startPage + maxVisiblePages - 1);
        
        if (endPage - startPage < maxVisiblePages - 1) {
            startPage = Math.max(1, endPage - maxVisiblePages + 1);
        }
        
        if (startPage > 1) {
            paginationHTML += `
                <button onclick="listFilters['${this.config.storageKey}'].goToPage(1)" 
                    class="px-3 py-1 bg-white hover:bg-gray-100 border-r border-gray-400">1</button>
            `;
            if (startPage > 2) {
                paginationHTML += `<span class="px-3 py-1 bg-white border-r border-gray-400">...</span>`;
            }
        }
        
        for (let i = startPage; i <= endPage; i++) {
            const activeClass = i === this.currentPage ? 'bg-[#0f7ea0] text-white' : 'bg-white hover:bg-gray-100';
            paginationHTML += `
                <button onclick="listFilters['${this.config.storageKey}'].goToPage(${i})" 
                    class="px-3 py-1 ${activeClass} border-r border-gray-400">${i}</button>
            `;
        }
        
        if (endPage < totalPages) {
            if (endPage < totalPages - 1) {
                paginationHTML += `<span class="px-3 py-1 bg-white border-r border-gray-400">...</span>`;
            }
            paginationHTML += `
                <button onclick="listFilters['${this.config.storageKey}'].goToPage(${totalPages})" 
                    class="px-3 py-1 bg-white hover:bg-gray-100 border-r border-gray-400">${totalPages}</button>
            `;
        }
        
        // Next button
        paginationHTML += `
            <button onclick="listFilters['${this.config.storageKey}'].goToPage(${this.currentPage + 1})" 
                ${this.currentPage === totalPages ? 'disabled' : ''} 
                class="px-3 py-1 bg-white hover:bg-gray-100 ${this.currentPage === totalPages ? 'opacity-50 cursor-not-allowed' : ''}">
                Next
            </button>
        `;
        
        paginationHTML += `</div></div>`;
        
        container.innerHTML = paginationHTML;
    }

    goToPage(page) {
        const totalPages = Math.ceil(this.filteredRows.length / this.config.perPage);
        if (page < 1 || page > totalPages) return;
        
        this.currentPage = page;
        this.render();
    }

    saveSettings() {
        const settings = {
            perPage: this.config.perPage,
            searchTerm: this.getSearchTerm(),
            filters: {}
        };
        
        this.config.filterSelects.forEach(filter => {
            const select = document.getElementById(filter.selectId);
            if (select) {
                settings.filters[filter.selectId] = select.value;
            }
        });
        
        sessionStorage.setItem(this.config.storageKey, JSON.stringify(settings));
    }

    restoreSettings() {
        const saved = sessionStorage.getItem(this.config.storageKey);
        if (!saved) return;
        
        try {
            const settings = JSON.parse(saved);
            
            // Restore per page
            if (settings.perPage) {
                this.config.perPage = settings.perPage;
                const perPageSelect = document.getElementById(this.config.perPageSelectId);
                if (perPageSelect) {
                    perPageSelect.value = settings.perPage === Infinity ? 'all' : settings.perPage;
                }
            }
            
            // Restore search term
            if (settings.searchTerm) {
                const searchInput = document.getElementById(this.config.searchInputId);
                if (searchInput) {
                    searchInput.value = settings.searchTerm;
                }
            }
            
            // Restore filters
            if (settings.filters) {
                Object.keys(settings.filters).forEach(selectId => {
                    const select = document.getElementById(selectId);
                    if (select) {
                        select.value = settings.filters[selectId];
                    }
                });
            }
        } catch (e) {
            console.error('Failed to restore settings:', e);
        }
    }

    refresh() {
        this.cacheRows();
        this.applyFilters();
    }
}

// Global storage for filter instances
window.listFilters = window.listFilters || {};
