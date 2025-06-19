<div class="min-h-screen p-6" data-aos="fade-up" data-aos-anchor-placement="top-bottom" data-aos-duration="1000">
    <div class="navigation-buttons mt-5">
        <div class="computerNav mt-3 p-2 bg-gray-500 rounded-t-lg">
            <p class="text-white">Menu Buttons</p>
            <i class="fa-solid fa-caret-down text-white"></i>
        </div>
        <nav class="overflow-hidden border-b-3 shadow-lg bg-gray-200" id="computerBtns">
            <ul class="flex md:flex-row flex-col gap-5 justify-between items-center px-5 py-3 uppercase">
                <li><button class="accessoriesBtns" id="back">Accessories History</button></li>
                <li><button class="accessoriesBtns" id="add">Add Accessories</button></li>
                <li><button class="accessoriesBtns" id="return">Returned History</button></li>
            </ul>
        </nav>
    </div>

    <!-- accessories history -->
    <div x-data="accessoriesHistory()" id="accessoriesHistory" class="bg-white rounded-b-xl shadow-sm">
        <div class="p-6 border-b border-gray-200">
            <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4">
                <h2 class="md:text-2xl text-md font-bold text-gray-800">Accessories Management</h2>
                <div class="w-full lg:max-w-3xl space-y-4">
                    <input type="text" placeholder="Search Accessory..."
                        class="w-full px-4 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-amber-500"
                        x-model="search">

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                        <select x-model="accFilter"
                            class="w-full px-4 py-2 border border-gray-300 rounded text-sm focus:ring-2 focus:ring-amber-500">
                            <option value="" selected class="bg-gray-400">Filter By Accessories</option>
                            <?php if (!empty($accessoriesNameFilter)): ?>
                                <?php foreach ($accessoriesNameFilter as $items): ?>
                                    <option value="<?= $items['AccessoriesName'] ?>"><?= $items['AccessoriesName'] ?></option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>

                        <select x-model="brandFilter"
                            class="w-full px-4 py-2 border border-gray-300 rounded text-sm focus:ring-2 focus:ring-amber-500">
                            <option value="" selected class="bg-gray-400">Filter By Brand</option>
                            <template
                                x-if="accFilter && brandsByAccessory[accFilter] && brandsByAccessory[accFilter].length">
                                <template x-for="brand in [...new Set(brandsByAccessory[accFilter])]" :key="brand">
                                    <option :value="brand" x-text="brand"></option>
                                </template>
                            </template>
                            <template x-if="!accFilter">
                                <template x-for="brand in allBrands" :key="brand">
                                    <option :value="brand" x-text="brand"></option>
                                </template>
                            </template>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 text-sm font-medium text-gray-600">
                    <tr>
                        <th class="px-6 py-4 text-left">Accessories</th>
                        <th class="px-6 py-4 text-left">Brand</th>
                        <th class="px-6 py-4 text-left">In Stock Quantity</th>
                        <th class="px-6 py-4 text-left">Assigned To</th>
                        <th class="px-6 py-4 text-left">Defective</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 text-sm">
                    <template x-for="(item, index) in paginatedItems" :key="item.AccessoriesID + '-' + index">
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 text-left"
                                x-text="item.AccessoriesName + ' (' + (item.PRNumber ?? 'No PR Number | Old Data') + ')'">
                            </td>
                            <td class="px-6 py-4 text-left" x-text="item.Brand ?? 'No Brand | Old Data'"></td>
                            <td class="px-6 py-4 text-left" x-text="item.Qty + ' pcs'"></td>
                            <td class="px-6 py-4 text-left">
                                <template x-if="item.assignments.length > 0">
                                    <template x-for="(emp, index) in item.assignments"
                                        :key="emp.EmployeeID + '-' + index">
                                        <p class="my-2"
                                            x-text="emp.EmployeeID + ' ' + emp.FirstName + ' ' + emp.LastName">
                                        </p>
                                    </template>
                                </template>
                                <template x-if="item.assignments.length === 0">
                                    <div class="italic text-gray-400">No Data found.</div>
                                </template>
                            </td>
                            <td class="px-6 py-4 text-left flex items-center gap-3">
                                <button title="Click to Mark as Defective"
                                    class="open-modal bg-red-600 text-white px-6 py-3 rounded hover:bg-red-700 transition-colors"
                                    :data-accessories-id="item.AccessoriesID" :data-pr-number="item.PRNumber"
                                    :data-brand="item.Brand" :data-accessories-name="item.AccessoriesName"
                                    x-text="item.Defective ?? '0'">
                                </button>
                            </td>
                        </tr>
                    </template>

                    <tr x-show="noResults">
                        <td colspan="5" class="px-6 py-4 text-center italic text-gray-400">
                            No Data found.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination Controls -->
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

    <!-- returned history -->
    <div x-data="returnHistory()" id="returnHistory" class="bg-white rounded-b-xl shadow-sm">
        <div class="p-6 border-b border-gray-200">
            <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4">
                <h2 class="md:text-2xl text-md font-bold text-gray-800">Returned History</h2>
                <div class="w-full lg:max-w-3xl space-y-4">
                    <input type="text" placeholder="Search Accessory..."
                        class="w-full px-4 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-amber-500"
                        x-model="search">

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                        <select x-model="accFilterReturn"
                            class="w-full px-4 py-2 border border-gray-300 rounded text-sm focus:ring-2 focus:ring-amber-500">
                            <option value="" selected class="bg-gray-400">Filter By Accessories</option>
                            <?php if (!empty($returnAccessoriesNameFilter)): ?>
                                <?php foreach ($returnAccessoriesNameFilter as $items): ?>
                                    <option value="<?= $items['AccessoriesName'] ?>"><?= $items['AccessoriesName'] ?></option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>

                        <select x-model="brandFilterReturn"
                            class="w-full px-4 py-2 border border-gray-300 rounded text-sm focus:ring-2 focus:ring-amber-500">
                            <option value="" selected class="bg-gray-400">Filter By Brand</option>
                            <template
                                x-if="accFilterReturn && returnBrandAccessory[accFilterReturn] && returnBrandAccessory[accFilterReturn].length">
                                <template x-for="brand in [...new Set(returnBrandAccessory[accFilterReturn])]"
                                    :key="brand">
                                    <option :value="brand" x-text="brand"></option>
                                </template>
                            </template>
                            <template x-if="!accFilterReturn">
                                <template x-for="brand in allBrandsReturn" :key="brand">
                                    <option :value="brand" x-text="brand"></option>
                                </template>
                            </template>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 text-sm font-medium text-gray-600">
                    <tr>
                        <th class="px-6 py-4 text-left">Accessories</th>
                        <th class="px-6 py-4 text-left">Brand</th>
                        <th class="px-6 py-4 text-left">PR Number</th>
                        <th class="px-6 py-4 text-left">Returned By</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 text-sm">
                    <!-- <pre x-text="JSON.stringify(paginatedItems, null, 2)" class="text-xs text-left"></pre> -->
                    <template x-for="(item, index) in paginatedItems" :key="item.AccessoriesID + '-' + index">
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 text-left" x-text="item.AccessoriesName "></td>
                            <td class="px-6 py-4 text-left" x-text="item.Brand ?? 'No Brand | Old Data'"></td>
                            <td class="px-6 py-4 text-left" x-text="item.PRNumber ?? 'No PR Number | Old Data'"></td>
                            <td class="px-6 py-4 text-left">
                                <template x-if="item.return.length > 0">
                                    <template x-for="(emp, index) in item.return" :key="emp.EmployeeID + '-' + index">
                                        <p class="my-2"
                                            x-text="emp.EmployeeID + ' ' + emp.FirstName + ' ' + emp.LastName"></p>
                                    </template>
                                </template>
                                <template x-if="item.return.length === 0">
                                    <div class="italic text-gray-400">No Data found.</div>
                                </template>
                            </td>
                        </tr>
                    </template>

                    <tr x-show="noResults">
                        <td colspan="5" class="px-6 py-4 text-center italic text-gray-400">
                            No Data found.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination Controls -->
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

    <!-- add accessories -->
    <div id="addAccessories" class="bg-white rounded-b-xl shadow-sm grid md:grid-cols-2 grid-cols-1">
        <div class="p-6 border-b border-gray-200">
            <div class="flex justify-between items-center">
                <h1 class="md:text-2xl text-md font-bold text-gray-800 mb-8">Add Accessories</h1>
                <span id="prBtn"
                    class="select-none bg-amber-200 px-4 py-2 rounded-lg hover:bg-amber-300 transition-colors text-sm font-medium cursor-pointer">Add
                    Purchase Requisition No.
                </span>
            </div>
            <form action="/accessories/create" method="post" class="flex flex-col gap-3">
                <input type="text" name="PRNumber" id="PRNumber" placeholder="Input Purchase Requisition  No."
                    class="w-full px-4 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-amber-500 hidden"
                    value="<?= $_SESSION['accessories_old_input']['PRNumber'] ?? '' ?>">
                <select name="AccessoriesName" id="AccessoriesName"
                    class="w-full px-4 py-2 border border-gray-300 rounded text-sm focus:ring-2 focus:ring-amber-500"
                    required autofocus>
                    <option class="bg-gray-400" disabled <?= empty($_SESSION['accessories_old_input']['AccessoriesName']) ? 'selected' : '' ?>>
                        -- Select Accessories Type --</option>
                    <option value="Keyboard" <?= ($_SESSION['accessories_old_input']['AccessoriesName'] ?? '') === 'Keyboard' ? 'selected' : '' ?>>Keyboard</option>
                    <option value="Mouse" <?= ($_SESSION['accessories_old_input']['AccessoriesName'] ?? '') === 'Mouse' ? 'selected' : '' ?>>Mouse</option>
                    <option value="Webcam" <?= ($_SESSION['accessories_old_input']['AccessoriesName'] ?? '') === 'Webcam' ? 'selected' : '' ?>>
                        Webcam
                    </option>
                    <option value="Headset" <?= ($_SESSION['accessories_old_input']['AccessoriesName'] ?? '') === 'Headset' ? 'selected' : '' ?>>
                        Headset
                    </option>
                    <option value="Table" <?= ($_SESSION['accessories_old_input']['AccessoriesName'] ?? '') === 'Table' ? 'selected' : '' ?>>
                        Table
                    </option>
                    <option value="Chair" <?= ($_SESSION['accessories_old_input']['AccessoriesName'] ?? '') === 'Chair' ? 'selected' : '' ?>>
                        Chair
                    </option>
                </select>
                <input type="text" name="Brand" id="Brand" placeholder="Brand"
                    class="w-full px-4 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-amber-500"
                    value="<?= $_SESSION['accessories_old_input']['Brand'] ?? '' ?>">
                <input type="number" name="Quantity" id="Quantity" placeholder="Quantity"
                    class="w-full px-4 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-amber-500"
                    value="<?= $_SESSION['accessories_old_input']['Quantity'] ?? '' ?>">
                <button class="bg-black text-white px-6 py-3 hover:bg-gray-800 transition-colors lg:w-auto w-full"
                    name="addPart"> Add </button>
            </form>
        </div>
        <div class="overflow-x-auto mt-8">
            <table class="w-full">
                <thead class="bg-amber-300 text-sm font-medium">
                    <tr>
                        <th colspan="5" class="px-6 py-4 text-center md:text-lg text-md ">Added Accessories</th>
                    </tr>
                </thead>
                <thead class="bg-gray-50 text-sm font-medium text-gray-600">
                    <tr>
                        <th class="px-6 py-4 text-left">PR No.</th>
                        <th class="px-6 py-4 text-left">ID</th>
                        <th class="px-6 py-4 text-left">Accessories</th>
                        <th class="px-6 py-4 text-left">Quantity</th>
                        <th class="px-6 py-4 text-left">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 text-sm">
                    <?php if (!empty($accessories_temp)): ?>
                        <?php foreach ($accessories_temp as $data): ?>
                            <?php
                            $PRNumber = htmlspecialchars($data['PRNumber'] ? $data['PRNumber'] : 'No PR Number');
                            $AccessoriesID = htmlspecialchars($data['AccessoriesID'] ?? '');
                            $AccessoriesName = htmlspecialchars($data['AccessoriesName'] ?? '');
                            $Brand = htmlspecialchars($data['Brand'] ?? '');
                            $Quantity = htmlspecialchars($data['Qty'] ?? '');
                            ?>
                            <tr>
                                <td class="px-6 py-4"><?= $PRNumber ?></td>
                                <td class="px-6 py-4"><?= $AccessoriesID ?></td>
                                <td class="px-6 py-4">
                                    <span class="font-bold">
                                        <?= $AccessoriesName . ' ' . $Brand ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4"><?= $Quantity ?></td>
                                <td class="px-6 py-4">
                                    <form action="/accessories/remove" method="post">

                                        <input type="hidden" name="AccessoriesID" value="<?= $AccessoriesID ?>">
                                        <input type="hidden" name="AccessoriesName" value="<?= $AccessoriesName ?>">
                                        <input type="hidden" name="Brand" value="<?= $Brand ?>">
                                        <button type="submit"
                                            class="flex-1 bg-red-600 text-white px-6 py-3 rounded hover:bg-red-700 transition-colors"
                                            name="removeTemp">
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
                            <td colspan="5" class="px-6 py-4 text-center italic text-gray-400">No parts found.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <div class="py-5 px-2">
                <?php if (!empty($accessories_temp)): ?>
                    <form action="/accessories/store" method="post">
                        <?php if (!empty($accessories)): ?>
                            <?php foreach ($accessories as $data): ?>
                                <input type="hidden" name="PRNumber[]" value="<?= htmlspecialchars($data['PRNumber'] ?? '') ?>">
                                <input type="hidden" name="AccessoriesID[]" value="<?= htmlspecialchars($data['AccessoriesID']) ?>">
                                <input type="hidden" name="AccessoriesName[]"
                                    value="<?= htmlspecialchars($data['AccessoriesName']) ?>">
                                <input type="hidden" name="Brand[]" value="<?= htmlspecialchars($data['Brand']) ?>">
                            <?php endforeach; ?>
                            <button type="submit" class="bg-black py-2 w-full text-white hover:opacity-80"
                                id="addToParts">Submit</button>
                        <?php endif; ?>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Defective Modal -->
    <div class="fixed inset-0 bg-black/50 hidden min-h-screen z-40" id="overlay"></div>
    <div id="modal" class="fixed top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 hidden z-50">
        <div class="bg-white p-8 rounded-xl shadow-xl max-w-2xl w-full mx-4">
            <form action="/accessories/defective" method="post" class="space-y-6">
                <h2 class="text-2xl font-bold text-gray-800 border-b border-gray-200 pb-4">
                    How many defective units does the <span id='accessory'></span> have?
                </h2>

                <div class="space-y-4">
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50 text-sm font-medium text-gray-600">
                                <tr>
                                    <th class="px-6 py-4 text-left">ID</th>
                                    <th class="px-6 py-4 text-left">Type</th>
                                    <th class="px-6 py-4 text-left">Brand</th>
                                    <th class="px-6 py-4 text-left">PRNumber</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 text-sm">
                                <tr>
                                    <td class="px-6 py-4 text-left">
                                        <input readonly type="text" name="AccessoriesID" id="getAccessoriesID"
                                            class="w-full pointer-events-none">
                                    </td>
                                    <td class="px-6 py-4 text-left">
                                        <input readonly type="text" name="AccessoriesName" id="getAccessoriesName"
                                            class="w-full pointer-events-none">
                                    </td>
                                    <td class="px-6 py-4 text-left">
                                        <input readonly type="text" name="Brand" id="getBrand"
                                            class="w-full pointer-events-none">
                                    </td>
                                    <td class="px-6 py-4 text-left">
                                        <input readonly type="text" name="PRNumber" id="getPRNumber"
                                            class="w-full pointer-events-none">
                                    </td>
                                </tr>
                                <tr>
                                    <td class="px-6 py-4 text-left" colspan="4">
                                        <input type="number" name="Defective" id="Defective"
                                            placeholder="How many defectives?"
                                            class="w-full px-4 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-amber-500 focus:border-transparent">
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="flex gap-4 pt-4">
                        <button type="submit"
                            class="accessoriesDefective flex-1 bg-black text-white px-6 py-3 rounded hover:bg-gray-800 transition-colors">
                            Submit
                        </button>
                        <button type="button" id="modal-cancel"
                            class="flex-1 bg-red-600 text-white px-6 py-3 rounded hover:bg-red-700 transition-colors">
                            Cancel
                        </button>
                    </div>
            </form>
        </div>
    </div>

</div>
<script>
    function accessoriesHistory() {
        return {
            search: '',
            statusFilter: '',
            accFilter: '',
            brandFilter: '',
            currentPage: 1,
            itemsPerPage: 15,
            filteredHistory: [],
            history: <?= json_encode($history) ?>,
            brandsByAccessory: <?= json_encode($brandsByAccessory) ?>,
            allBrands: <?= json_encode(array_values(array_unique(array_column($accessories, 'Brand')))) ?>,
            isLoading: false,
            init() {
                // openModal();
                this.isLoading = true;
                this.filterHistory();
                ['search', 'statusFilter', 'accFilter', 'brandFilter'].forEach(filter => {
                    this.$watch(filter, () => {
                        this.currentPage = 1;
                        this.filterHistory();
                        // returnAccessories();
                    });
                });
                this.$watch('itemsPerPage', () => this.handleItemsPerPageChange());
                this.isLoading = false;
                this.$watch('currentPage', () => {
                    this.filterHistory();
                    openModal();
                });
            },

            handleItemsPerPageChange() {
                this.currentPage = 1;
                this.filterHistory();
                returnAccessories();
            },

            filterHistory() {
                this.filteredHistory = this.history.filter(item => {
                    const searchTerm = this.search.toLowerCase();
                    const matchesSearch = !searchTerm ||
                        (item.AccessoriesName?.toLowerCase().includes(searchTerm) || '') ||
                        (`${item.FirstName || ''} ${item.LastName || ''}`).toLowerCase().includes(searchTerm);

                    const statusMatch = !this.statusFilter ||
                        (item.assignmentStatus?.toLowerCase() === this.statusFilter.toLowerCase());

                    const accMatch = !this.accFilter ||
                        (item.AccessoriesName?.toLowerCase() === this.accFilter.toLowerCase());

                    const brandMatch = !this.brandFilter ||
                        (item.Brand?.toLowerCase() === this.brandFilter.toLowerCase());

                    return matchesSearch && statusMatch && accMatch && brandMatch;
                });

                this.updatePagination();
            },

            updatePagination() {
                this.currentPage = Math.max(1, Math.min(this.currentPage, this.totalPages));
            },

            get noResults() {
                return this.filteredHistory.length === 0;
            },

            get totalPages() {
                const itemsPerPage = Number(this.itemsPerPage);
                return Math.ceil(this.filteredHistory.length / itemsPerPage) || 1;
            },

            get paginatedItems() {
                const itemsPerPage = Number(this.itemsPerPage);
                const start = (this.currentPage - 1) * itemsPerPage;
                return this.filteredHistory.slice(start, start + itemsPerPage);
            }
        }
    }

    function returnHistory() {
        return {
            search: '',
            statusFilter: '',
            accFilterReturn: '',
            brandFilterReturn: '',
            currentPage: 1,
            itemsPerPage: 15,
            filteredHistory: [],
            history: <?= json_encode($returnGroupHistory) ?>,
            returnBrandAccessory: <?= json_encode($returnBrandAccessory) ?>,
            allBrandsReturn: <?= json_encode(array_values(array_unique(array_column($returnAccessoriesStmt, 'Brand')))) ?>,
            isLoading: false,
            init() {
                this.isLoading = true;
                this.filterHistory();
                ['search', 'statusFilter', 'accFilterReturn', 'brandFilterReturn'].forEach(filter => {
                    this.$watch(filter, () => {
                        this.currentPage = 1;
                        this.filterHistory();
                        // returnAccessories();
                    });
                });
                this.$watch('itemsPerPage', () => this.handleItemsPerPageChange());
                this.isLoading = false;
                // console.log(this.returnBrandAccessory)
            },

            handleItemsPerPageChange() {
                this.currentPage = 1;
                this.filterHistory();
                returnAccessories();
            },

            filterHistory() {
                this.filteredHistory = this.history.filter(item => {
                    const searchTerm = this.search.toLowerCase();
                    const matchesSearch = !searchTerm ||
                        (item.AccessoriesName?.toLowerCase().includes(searchTerm) || '') ||
                        (`${item.FirstName || ''} ${item.LastName || ''}`).toLowerCase().includes(searchTerm);

                    const statusMatch = !this.statusFilter ||
                        (item.assignmentStatus?.toLowerCase() === this.statusFilter.toLowerCase());

                    const accMatch = !this.accFilterReturn ||
                        (item.AccessoriesName?.toLowerCase() === this.accFilterReturn.toLowerCase());

                    const brandMatch = !this.brandFilterReturn ||
                        (item.Brand?.toLowerCase() === this.brandFilterReturn.toLowerCase());


                    return matchesSearch && statusMatch && accMatch && brandMatch;
                });

                this.updatePagination();
            },

            updatePagination() {
                this.currentPage = Math.max(1, Math.min(this.currentPage, this.totalPages));
            },

            get noResults() {
                return this.filteredHistory.length === 0;
            },

            get totalPages() {
                const itemsPerPage = Number(this.itemsPerPage);
                return Math.ceil(this.filteredHistory.length / itemsPerPage) || 1;
            },

            get paginatedItems() {
                const itemsPerPage = Number(this.itemsPerPage);
                const start = (this.currentPage - 1) * itemsPerPage;
                return this.filteredHistory.slice(start, start + itemsPerPage);
            }
        }
    }
</script>