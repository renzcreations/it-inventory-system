<div class="min-h-screen p-6 space-y-6" data-aos="fade-up" data-aos-anchor-placement="top-bottom" data-aos-duration="1000">
    <div class="bg-white rounded-xl shadow-sm grid md:grid-cols-2 grid-cols-1">
        <!-- Left side: Build PC form and current temp parts -->
        <div class="p-6 border-b border-gray-200" x-data="checkPCName()">
            <div class="mb-8 space-y-6">
                <h1 class="md:text-2xl text-md font-bold text-gray-800">BUILD A COMPUTER</h1>
                <form action="/build/store" method="post" id="buildPC" class="flex flex-col gap-2">

                    <input type="text" name="PCName" id="PCName"
                        class="w-full px-4 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-amber-500"
                        placeholder="Computer Name" x-model="pcName" @input.debounce.500ms="checkPCName">

                    <?php if (!empty($tempPart)): ?>
                        <?php foreach ($tempPart as $item): ?>
                            <input type="hidden" name="PartID[]" value="<?= htmlspecialchars($item['PartID']) ?>">
                        <?php endforeach; ?>
                    <?php endif; ?>
                    <!-- Status Messages -->
                    <div x-show="isChecking" class="text-gray-500 text-sm">Checking availability...</div>
                    <div x-show="!isChecking && isAvailable" class="text-green-500 text-sm">✓ Name is available!</div>
                    <div x-show="!isChecking && pcNameError" class="text-red-500 text-sm">✗ Name already exists!</div>
                    <button type="submit"
                        class="flex-1 bg-black text-white px-6 py-3 rounded hover:bg-gray-800 transition-colors"
                        name="buildPC" :disabled="pcNameError || isChecking">
                        Register Computer
                    </button>
                </form>
            </div>
            <!-- Temporary parts list -->
            <div class="overflow-x-auto mt-8">
                <table class="w-full">
                    <thead class="bg-gray-50 text-sm font-medium text-gray-600">
                        <tr>
                            <th class="px-6 py-4 text-left">Part/s</th>
                            <th class="px-6 py-4 text-left">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 text-sm">
                        <?php if (!empty($tempPart)): ?>
                            <?php foreach ($tempPart as $data): ?>
                                <tr class="hover:bg-gray-100">
                                    <td class="px-6 py-4 text-left ">
                                        <span class="font-medium text-gray-800">
                                            <?= htmlspecialchars($data['PartType'] . ' ' . $data['Brand'] . ' ' . $data['Model']) ?>
                                        </span>
                                        <span class="text-xs italic">(<?= htmlspecialchars($data['SerialNumber']) ?>)</span>
                                    </td>
                                    <td class="px-6 py-4 text-left ">
                                        <form action="/build/remove" method="post" id="removePart">
                                            <input type="hidden" name="PartID" value="<?= htmlspecialchars($data['PartID']) ?>">
                                            <input type="hidden" name="Brand"
                                                value="<?= htmlspecialchars($data['Brand'] . ' ' . $data['Model']) ?>">
                                            <button type="submit"
                                                class="flex-1 bg-red-600 text-white px-6 py-3 rounded hover:bg-red-700 transition-colors"
                                                name="remove">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7h6m2 0H7m4-3h2a1 1 0 011 1v1H8V5a1 1 0 011-1h2z" />
                                                </svg>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td class="px-6 py-4 text-center italic text-gray-400 text-lg" colspan="2">
                                    Please add parts first, then enter the computer name.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Right side: List of available parts with pagination -->
        <div id="availableParts" class="bg-white rounded-xl shadow-sm" x-data="partTable()">
            <!-- Filter/Search Bar -->
            <div class="p-6 border-b border-gray-200 flex flex-col items-center">
                <div class="flex flex-col md:flex-row gap-3 w-full">
                    <input type="text" placeholder="Search parts..."
                        class="flex-1 px-4 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-amber-500"
                        x-model="search" @input.debounce.300ms="filterParts">
                    <select x-model="partTypeFilter" @change="filterParts"
                        class="flex-1 px-4 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-amber-500">
                        <option value="">All Types</option>
                        <?php foreach ($type as $types): ?>
                            <option value="<?= htmlspecialchars($types['PartType']) ?>">
                                <?= htmlspecialchars($types['PartType']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <select x-model="itemsPerPage" @change="handleItemsPerPageChange"
                        class="flex-1 px-4 py-2 border border-gray-300 rounded text-sm focus:ring-2 focus:ring-amber-500">
                        <option :value="15">15/page</option>
                        <option :value="25">25/page</option>
                        <option :value="50">50/page</option>
                    </select>
                </div>
            </div>
            <!-- Table -->
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 text-sm font-medium text-gray-600">
                        <tr>
                            <th class="px-6 py-4 text-left">ID</th>
                            <th class="px-6 py-4 text-left">Parts</th>
                            <th class="px-6 py-4 text-left">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 text-sm">
                        <template x-for="part in paginatedItems" :key="part.PartID">
                            <tr class="hover:bg-gray-100 border-b">
                                <td class="px-6 py-4 text-left uppercase" x-text="part.uniqueID"></td>
                                <td class="px-6 py-4 text-left">
                                    <span class="font-bold" x-text="part.PartType"></span> -
                                    <span class="font-bold" x-text="part.Brand"></span>
                                    <span class="font-bold" x-text="part.Model"></span>
                                    (<span class="text-xs italic"
                                        x-text="part.SerialNumber ?? 'No Serial Number'"></span>)
                                </td>
                                <td class="px-6 py-4 text-left">
                                    <form action="/build/add" method="post">
                                        <input type="hidden" name="PartID" x-bind:value="part.PartID">
                                        <input type="hidden" name="PartType" x-bind:value="part.PartType">
                                        <input type="hidden" name="Brand" x-bind:value="part.Brand">
                                        <input type="hidden" name="Model" x-bind:value="part.Model">
                                        <input type="hidden" name="SerialNumber" x-bind:value="part.SerialNumber">
                                        <button type="submit"
                                            class="flex-1 bg-black text-white px-3 py-2 rounded hover:bg-gray-800 transition-colors">
                                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                                                xmlns="http://www.w3.org/1000/svg">
                                                <line x1="12" y1="5" x2="12" y2="19" stroke="white" stroke-width="3" />
                                                <line x1="5" y1="12" x2="19" y2="12" stroke="white" stroke-width="3" />
                                            </svg>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        </template>
                        <tr x-show="noResults">
                            <td class="px-4 py-2 text-gray-500 italic text-center" colspan="6">
                                No parts found.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <!-- Pagination Controls -->
            <div
                class="flex flex-col md:flex-row justify-between items-center p-6 border-t border-gray-200 w-full max-w-4xl">
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
</div>

<script>
    // Fixed checkPCName function with correct property name
    function checkPCName() {
        return {
            pcName: '',
            isChecking: false,
            isAvailable: false,
            pcNameError: false,
            async checkPCName() {
                if (this.pcName.trim().length === 0) {
                    this.isAvailable = false;
                    this.pcNameError = false;
                    return;
                }
                this.isChecking = true;
                try {
                    const response = await fetch(`http://localhost:8000/build/check?name=${encodeURIComponent(this.pcName)}`); //change the url to production url
                    if (!response.ok) throw new Error('Network error');
                    const data = await response.json();
                    this.isAvailable = data.available;
                    this.pcNameError = !data.available;
                } catch (error) {
                    console.error('Error:', error);
                    this.pcNameError = true;
                    this.isAvailable = false;
                } finally {
                    this.isChecking = false;
                }
            }
        }
    }

    // Pagination for parts table
    function partTable() {
        return {
            search: '',
            partTypeFilter: '',
            currentPage: 1,
            itemsPerPage: 10,
            allParts: <?= json_encode($parts) ?>,
            filteredParts: [],

            init() {
                this.filteredParts = [...this.allParts];
            },

            filterParts() {
                this.filteredParts = this.allParts.filter(part => {
                    const searchTerm = this.search.toLowerCase();

                    // Safely check each property (fallback to empty string if null/undefined)
                    const uniqueID = String(part.uniqueID ?? '').toLowerCase();
                    const partID = String(part.PartID ?? '').toLowerCase();
                    const partType = String(part.PartType ?? '').toLowerCase();
                    const brand = String(part.Brand ?? '').toLowerCase();
                    const model = String(part.Model ?? '').toLowerCase();
                    const serialNumber = String(part.SerialNumber ?? '').toLowerCase();

                    const matchesSearch = !searchTerm ||
                        uniqueID.includes(searchTerm) ||
                        partID.includes(searchTerm) ||
                        partType.includes(searchTerm) ||
                        brand.includes(searchTerm) ||
                        model.includes(searchTerm) ||
                        serialNumber.includes(searchTerm);

                    const matchesType = !this.partTypeFilter ||
                        partType === this.partTypeFilter.toLowerCase();

                    return matchesSearch && matchesType;
                });

                this.currentPage = 1;
            },

            handleItemsPerPageChange() {
                this.currentPage = 1;
            },

            get noResults() {
                return this.filteredParts.length === 0;
            },

            get totalPages() {
                return Math.ceil(this.filteredParts.length / this.itemsPerPage) || 1;
            },

            get paginatedItems() {
                const start = (this.currentPage - 1) * this.itemsPerPage;
                return this.filteredParts.slice(start, start + this.itemsPerPage);
            }
        }
    }
</script>