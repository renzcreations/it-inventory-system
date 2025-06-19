<div class="min-h-screen p-6" data-aos="fade-up" data-aos-anchor-placement="top-bottom" data-aos-duration="1000">
    <div class="navigation-buttons mt-5">
        <div class="computerNav mt-3 p-2 bg-gray-500 rounded-t-lg">
            <p class="text-white">Menu Buttons</p>
            <i class="fa-solid fa-caret-down text-white"></i>
        </div>
        <nav class="overflow-hidden border-b-3 shadow-lg bg-gray-200" id="computerBtns">
            <ul class="flex md:flex-row flex-col gap-5 justify-between items-center px-5 py-3 uppercase">
                <li><a href="#" class="computer_nav" id="assignedBtn">Computer History</a></li>
                <li><a href="#" class="computer_nav md:text-[1em] text-xs" id="assignBtn">Assigning of Computers</a>
                </li>
                <li><a href="#" class="computer_nav" id="returnedBtn">Returned History</a></li>
            </ul>
        </nav>
    </div>

    <div class="bg-white rounded-b-xl shadow-sm" id="assigned_content" x-data="genericTable()"
        data-items='<?= htmlspecialchars(json_encode($computers ?? []), ENT_QUOTES, 'UTF-8', true) ?>'
        data-filters='{"Status": ""}'>
        <div class="p-6 border-b border-gray-200">
            <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4">
                <h2 class="text-2xl font-bold text-gray-800">Computer Management</h2>
                <div class="w-full lg:max-w-3xl space-y-4">
                    <input type="text" placeholder="Search..."
                        class="w-full px-4 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-amber-500"
                        x-model="search">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                        <select x-model="currentFilters.Status"
                            class="w-full px-4 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-amber-500">
                            <option value="">Filter By Status</option>
                            <option value="Unassigned">Available</option>
                            <option value="Assigned">Assigned</option>
                            <option value="Returned">Returned</option>
                        </select>
                        <select x-model="itemsPerPage" @change="handleItemsPerPageChange"
                            class="w-full px-4 py-2 border border-gray-300 rounded text-sm focus:ring-2 focus:ring-amber-500">
                            <option :value="15">15/page</option>
                            <option :value="25">25/page</option>
                            <option :value="50">50/page</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 text-sm font-medium text-gray-600">
                    <tr>
                        <th class="px-6 py-4 text-left">Computer</th>
                        <th class="px-6 py-4 text-left">Employee</th>
                        <th class="px-6 py-4 text-left">Status</th>
                        <th class="px-6 py-4 text-left">Assigned Date</th>
                        <th class="px-6 py-4 text-left">Returned Date</th>
                        <th class="px-6 py-4 text-left">Specifications</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 text-sm">
                    <template x-for="pc in paginatedItems" :key="pc.PCID">
                        <tr class="hover:bg-gray-100 border-b">
                            <td class="px-6 py-4 text-left" x-text="pc.PCName"></td>
                            <td class="px-6 py-4 text-left">
                                <template x-if="pc.FirstName && pc.LastName">
                                    <span
                                        x-text="pc.EmployeeID ? `${pc.EmployeeID} - ${pc.FirstName} ${pc.LastName}` : 'No Employee ID Found'"></span>
                                </template>
                                <template x-if="!pc.FirstName || !pc.LastName">
                                    <span>No Employee Name Found</span>
                                </template>
                            </td>
                            <td class="px-6 py-4 text-left">
                                <template x-if="pc.Status === 'Returned' || pc.Status === 'Unassigned'">
                                    <select class="border px-4 py-2 bg-gray-500 cursor-pointer"
                                        :disabled="pc.Status || 'Available'">
                                        <option x-text="pc.Status || 'Available'"></option>
                                    </select>
                                </template>
                                <template x-if="pc.Status && pc.Status !== 'Returned' && pc.Status !== 'Unassigned'">
                                    <form method="post" action="/computer/return">
                                        <input type="hidden" name="PCID" :value="pc.PCID">
                                        <input type="hidden" name="PCName" :value="pc.PCName">
                                        <input type="hidden" name="EmployeeID" :value="pc.EmployeeID">
                                        <select name="Status"
                                            class="status px-3 py-1 border border-gray-300 rounded focus:ring-2 focus:ring-amber-500">
                                            <option x-text="pc.Status ==='Unassigned' ? 'Available': pc.Status">
                                            </option>
                                            <option value="Returned">Returned</option>
                                        </select>
                                    </form>
                                </template>
                            </td>
                            <td class="px-6 py-4 text-left"
                                x-text="pc.AssignedDate ? new Date(pc.AssignedDate).toLocaleString('en-US', { month: 'long', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit', hour12: true }) : 'Available'">
                            </td>
                            <td class="px-6 py-4 text-left"
                                x-text="pc.ReturnedDate ? new Date(pc.ReturnedDate).toLocaleString('en-US', { month: 'long', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit', hour12: true }) : 'No Date Found'">
                            </td>
                            <td class="px-6 py-4 text-left">
                                <a :href="`/computer/specifications/${encodeURIComponent(pc.PCName)}`"
                                    class="inline-flex items-center gap-2 px-4 py-2 bg-black text-white rounded hover:bg-gray-800 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    <span class="text-sm">PDF</span>
                                </a>
                            </td>
                        </tr>
                    </template>

                    <template x-if="noResults">
                        <tr>
                            <td colspan="6" class="px-6 py-4 text-center italic text-gray-400">
                                No matching results found
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="flex flex-col md:flex-row justify-between items-center p-6 border-t border-gray-200">
            <div class="mb-4 md:mb-0 text-sm text-gray-600">
                Showing page <span x-text="currentPage"></span> of <span x-text="totalPages"></span>
            </div>
            <div class="flex items-center gap-2">
                <button @click="currentPage--" :disabled="currentPage === 1"
                    class="px-4 py-2 border border-gray-300 rounded hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                    Previous
                </button>
                <button @click="currentPage++" :disabled="currentPage >= totalPages"
                    class="px-4 py-2 border border-gray-300 rounded hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                    Next
                </button>
            </div>
        </div>
    </div>

    <!-- Assignment Section -->
    <div class="bg-white rounded-b-xl shadow-sm" id="assign_content">
        <!-- Search Form -->
        <div class="p-6 border-b border-gray-200">
            <div class="grid md:grid-cols-2 grid-cols-1 gap-4">
                <div class="w-full">
                    <h1 class="font-bold text-lg my-5 uppercase">Allocating of Computers</h1>
                    <form method="post" action="/computer/create" class="flex flex-col gap-5">
                        <input type="text" name="name" placeholder="Search employee by name or id"
                            class="w-full px-4 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-amber-500"
                            required value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
                        <input type="text" name="computer" placeholder="Search available computer by id or name"
                            class="w-full px-4 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-amber-500"
                            required value="<?= htmlspecialchars($_POST['computer'] ?? '') ?>">
                        <button type="submit" name="search"
                            class="bg-black text-white px-6 py-2 hover:bg-gray-800 transition-colors">
                            Search
                        </button>
                    </form>
                </div>

                <!-- Results Section -->
                <div>
                    <h1 class="font-bold text-lg my-5 uppercase">Search Results</h1>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50 text-sm font-medium text-gray-600">
                                <tr>
                                    <th class="px-6 py-4 text-left">Employee</th>
                                    <th class="px-6 py-4 text-left">Computer</th>
                                    <th class="px-6 py-4 text-left">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 text-sm">
                                <?php if (!empty($tempAssignments)): ?>
                                    <?php foreach ($tempAssignments as $item): ?>
                                        <tr class="hover:bg-gray-100 border-b">
                                            <td class="px-6 py-4 text-left">
                                                <?= htmlspecialchars($item['EmployeeID']) ?> -
                                                <?= htmlspecialchars($item['FirstName'] . ' ' . $item['LastName']) ?>
                                            </td>
                                            <td class="px-6 py-4 text-left">
                                                <?= htmlspecialchars($item['PCID']) ?> -
                                                <?= htmlspecialchars($item['PCName']) ?>
                                            </td>
                                            <td class="px-6 py-4 text-left">
                                                <form action="/computer/remove" method="post" id="removeTempAssignment">
                                                    <input type="hidden" name="EmployeeID"
                                                        value="<?= htmlspecialchars($item['EmployeeID']) ?>">
                                                    <input type="hidden" name="name"
                                                        value="<?= htmlspecialchars($item['FirstName'] . ' ' . $item['LastName']) ?>">
                                                    <button type="submit"
                                                        class="flex-1 bg-red-600 text-white px-6 py-3 rounded hover:bg-red-700 transition-colors"
                                                        name="removeTemp">
                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                            viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7h6m2 0H7m4-3h2a1 1 0 011 1v1H8V5a1 1 0 011-1h2z" />
                                                        </svg>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="3" class="px-6 py-4 text-center italic text-gray-400">
                                            No temporary assignments found
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <?php if (!empty($tempAssignments)): ?>
                        <div class="mt-5">
                            <form method="post" action="/computer/store">

                                <?php foreach ($tempAssignments as $item): ?>
                                    <input type="hidden" name="EmployeeID[]" value="<?= $item['EmployeeID'] ?>">
                                    <input type="hidden" name="PCID[]" value="<?= $item['PCID'] ?>">
                                <?php endforeach; ?>
                                <button type="submit" name="assigned"
                                    class="bg-black text-white px-4 py-2 hover:opacity-80 w-full">
                                    Confirm Assignments
                                </button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- returned history -->
    <div class="bg-white rounded-b-xl shadow-sm" id="returned_content" x-data="genericTable()"
        data-items='<?= htmlspecialchars(json_encode($returnedData ?? []), ENT_QUOTES, 'UTF-8', true) ?>'
        data-filters='{"Status": ""}'>
        <div class="p-6 border-b border-gray-200">
            <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4">
                <h2 class="text-2xl font-bold text-gray-800">Returned History</h2>
                <div class="w-full lg:max-w-3xl space-y-4">
                    <input type="text" placeholder="Search..."
                        class="w-full px-4 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-amber-500"
                        x-model="search">
                </div>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 text-sm font-medium text-gray-600">
                    <tr>
                        <th class="px-6 py-4 text-left">Computer</th>
                        <th class="px-6 py-4 text-left">Employee</th>
                        <th class="px-6 py-4 text-left">Custody</th>
                        <th class="px-6 py-4 text-left">Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 text-sm">
                    <template x-for="returned in paginatedItems" :key="returned.EmployeeID">
                        <tr class="hover:bg-gray-100 border-b">
                            <td class="px-6 py-4 text-left" x-text="returned.PCName ?? 'No Computer Name'"></td>
                            <td class="px-6 py-4 text-left">
                                <template x-if="returned.FirstName && returned.LastName">
                                    <span
                                        x-text="returned.EmployeeID ? `${returned.EmployeeID} - ${returned.FirstName} ${returned.LastName}` : 'No Employee ID Found'"></span>
                                </template>
                                <template x-if="!returned.FirstName || !returned.LastName">
                                    <span>No Employee Name Found</span>
                                </template>
                            </td>

                            <td class="px-6 py-4 text-left">
                                <template x-if="returned.PCID">
                                    <a :href="`/computer/returned/${encodeURIComponent(returned.EmployeeID)}`"
                                        class="inline-flex items-center gap-2 px-4 py-2 bg-black text-white rounded hover:bg-gray-800 transition-colors"
                                        tabindex="-1">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                        <span class="text-sm">PDF</span>
                                    </a>
                                </template>
                                <template x-if="!returned.PCID">
                                    <span>No Data Available</span>
                                </template>
                            </td>
                            <td class="px-6 py-4 text-left"
                                x-text="returned.created_at ? new Date(returned.created_at).toLocaleString('en-US', { month: 'long', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit', hour12: true }) : 'No Date Found'">
                            </td>
                        </tr>
                    </template>

                    <template x-if="noResults">
                        <tr>
                            <td colspan="6" class="px-6 py-4 text-center italic text-gray-400">
                                No matching results found
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="flex flex-col md:flex-row justify-between items-center p-6 border-t border-gray-200">
            <div class="mb-4 md:mb-0 text-sm text-gray-600">
                Showing page <span x-text="currentPage"></span> of <span x-text="totalPages"></span>
            </div>
            <div class="flex items-center gap-2">
                <button @click="currentPage--" :disabled="currentPage === 1"
                    class="px-4 py-2 border border-gray-300 rounded hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                    Previous
                </button>
                <button @click="currentPage++" :disabled="currentPage >= totalPages"
                    class="px-4 py-2 border border-gray-300 rounded hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                    Next
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    function genericTable() {
        return {
            search: '',
            currentFilters: { Status: "" },
            currentPage: 1,
            itemsPerPage: 15,
            allItems: [],
            filteredItems: [],
            sortField: '',
            sortDirection: 'asc',
            noResults: false,

            init() {
                // Initialize with passed data
                try {
                    this.allItems = this.$el.dataset.items ? JSON.parse(this.$el.dataset.items) : [];
                } catch (e) {
                    console.error("Error parsing items data:", e);
                    this.allItems = [];
                }

                try {
                    this.currentFilters = this.$el.dataset.filters ? JSON.parse(this.$el.dataset.filters) : {};
                } catch (e) {
                    console.error("Error parsing filters data:", e);
                    this.currentFilters = {};
                }

                this.$watch('search', () => this.handleFilterChange());
                this.$watch('currentFilters', (newValue) => {
                    this.handleFilterChange();
                }, { deep: true });

                this.$watch('itemsPerPage', () => this.handleFilterChange());
                this.filterItems();
            },

            handleFilterChange() {
                this.currentPage = 1;
                this.filterItems();
            },

            filterItems() {
                this.filteredItems = this.allItems.filter(item => {
                    const matchesSearch = !this.search ||
                        Object.values(item).some(val =>
                            String(val).toLowerCase().includes(this.search.toLowerCase())
                        );

                    const matchesFilters = Object.entries(this.currentFilters).every(([key, value]) => {
                        if (!value) return true;

                        // Special handling for Status filter
                        if (key === 'Status') {
                            if (value === 'Unassigned') {
                                // Show both "Unassigned" and "Available" (if you have both)
                                return item[key] === 'Unassigned' || item[key] === 'Returned';
                            }
                            return String(item[key]).toLowerCase() === value.toLowerCase();
                        }

                        return true;
                    });

                    return matchesSearch && matchesFilters;
                });

                this.updatePagination();
                this.noResults = this.filteredItems.length === 0;
            },

            updatePagination() {
                this.currentPage = Math.max(1, Math.min(this.currentPage, this.totalPages));
            },

            get totalPages() {
                return Math.ceil(this.filteredItems.length / this.itemsPerPage) || 1;
            },

            get paginatedItems() {
                const start = (this.currentPage - 1) * this.itemsPerPage;
                return this.filteredItems.slice(start, start + this.itemsPerPage);
            },

            sortItems(field) {
                if (this.sortField === field) {
                    this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc';
                } else {
                    this.sortField = field;
                    this.sortDirection = 'asc';
                }

                this.filteredItems.sort((a, b) => {
                    if (a[field] < b[field]) return this.sortDirection === 'asc' ? -1 : 1;
                    if (a[field] > b[field]) return this.sortDirection === 'asc' ? 1 : -1;
                    return 0;
                });
            }
        }
    }
</script>