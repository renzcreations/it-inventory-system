<div class="min-h-screen p-6 space-y-6" data-aos="fade-up" data-aos-anchor-placement="top-bottom" data-aos-duration="1000">
    <!-- Action Controls -->
    <div class="flex flex-col lg:flex-row gap-4">
        <button class="bg-black text-white px-6 py-2 hover:bg-gray-800 transition-colors lg:w-auto w-full open-modal"
            id="open-modal">
            Register New Employee
        </button>

        <form action="/employee/upload" method="post" enctype="multipart/form-data" class="lg:w-auto w-full">
            <div class="flex md:flex-row flex-col shadow-sm">
                <input type="file" name="tsv" id="tsv" accept=".tsv"
                    class="bg-white border border-gray-300 px-4 py-2 flex-grow focus:outline-none focus:ring-2 focus:ring-amber-500">
                <button type="submit" class="bg-black text-white px-6 py-2 hover:bg-gray-800 transition-colors">
                    Upload TSV
                </button>
            </div>
        </form>
    </div>

    <!-- Modal -->
    <div class="fixed top-0 inset-0 bg-black/50 hidden min-h-screen z-1" id="overlay"></div>
    <div class="fixed top-0 inset-0 items-center justify-center hidden z-2" id="modal">
        <div class="bg-white p-8 rounded-xl shadow-xl max-w-2xl w-full mx-4">
            <form action="/employee/register" method="post" class="space-y-6">
                <h2 class="text-2xl font-bold text-gray-800 border-b border-gray-200 pb-4">
                    REGISTER NEW EMPLOYEE
                </h2>

                <div class="space-y-4">
                    <input type="text" name="EmployeeID" id="EmployeeID"
                        placeholder="Employee ID (Type 'N/A' if unavailable)"
                        class="w-full px-4 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-amber-500 focus:border-transparent"
                        value="<?= $_SESSION['old_input']['EmployeeID'] ?? '' ?>">

                    <div class="grid md:grid-cols-2 gap-4">
                        <input type="text" name="FirstName" id="FirstName" placeholder="First Name"
                            class="w-full px-4 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-amber-500"
                            value="<?= $_SESSION['old_input']['FirstName'] ?? '' ?>">

                        <input type="text" name="LastName" id="LastName" placeholder="Last Name"
                            class="w-full px-4 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-amber-500"
                            value="<?= $_SESSION['old_input']['LastName'] ?? '' ?>">
                    </div>

                    <input type="email" name="Email" id="Email" placeholder="Email Address"
                        class="w-full px-4 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-amber-500"
                        value="<?= $_SESSION['old_input']['Email'] ?? '' ?>">

                    <select name="WorkStatus"
                        class="w-full px-4 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-amber-500">
                        <option value="">Work Status</option>
                        <option value="WFH">WFH</option>
                        <option value="TEMP WFH">TEMP WFH</option>
                        <option value="ON-SITE">ON-SITE</option>
                    </select>

                    <div class="space-y-4">
                        <h3 class="text-sm font-medium text-gray-600">Department Information</h3>
                        <div class="grid md:grid-cols-2 gap-4">
                            <input type="text" name="inputDepartment" id="Department" placeholder="Department Name"
                                class="w-full px-4 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-amber-500"
                                value="<?= $_SESSION['old_input']['Department'] ?? '' ?>">

                            <select name="selectDepartment"
                                class="w-full px-4 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-amber-500">
                                <option value="">Select Existing Department</option>
                                <?php if (!empty($deptFilter)): ?>
                                    <?php foreach ($deptFilter as $data): ?>
                                        <option value="<?= $data['Department'] ?>"><?= $data['Department'] ?></option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="flex gap-4 pt-4">
                    <button type="submit"
                        class="flex-1 bg-black text-white px-6 py-3 rounded hover:bg-gray-800 transition-colors">
                        Submit
                    </button>
                    <button type="button"
                        class="flex-1 bg-red-600 text-white px-6 py-3 rounded hover:bg-red-700 transition-colors">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Employee Table Section -->
    <div class="bg-white rounded-xl shadow-sm" x-data="employeeTable()" id="employeeTable">
        <div class="p-6 border-b border-gray-200">
            <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4">
                <h2 class="text-2xl font-bold text-gray-800">Employee Management</h2>
                <div class="w-full lg:max-w-3xl space-y-4">
                    <input type="text" placeholder="Search Employee..."
                        class="w-full px-4 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-amber-500"
                        x-model="search">

                    <div class="grid grid-cols-1 md:grid-cols-4 gap-2">
                        <select x-model="statusFilter"
                            class="w-full px-4 py-2 border border-gray-300 rounded text-sm focus:ring-2 focus:ring-amber-500">
                            <option value="">All Statuses</option>
                            <option value="Active">Active</option>
                            <option value="Resigned">Resigned</option>
                        </select>

                        <select x-model="signStatus"
                            class="w-full px-4 py-2 border border-gray-300 rounded text-sm focus:ring-2 focus:ring-amber-500">
                            <option value="">All Sign Statuses</option>
                            <option value="Signed">Signed</option>
                            <option value="Unsigned">Unsigned</option>
                        </select>

                        <select x-model="deptFilter"
                            class="w-full px-4 py-2 border border-gray-300 rounded text-sm focus:ring-2 focus:ring-amber-500">
                            <option value="">All Departments</option>
                            <?php if (!empty($deptFilter)): ?>
                                <?php foreach ($deptFilter as $data): ?>
                                    <option value="<?= $data['Department'] ?>"><?= $data['Department'] ?></option>
                                <?php endforeach; ?>
                            <?php endif; ?>
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

        <!-- Table -->
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 text-sm font-medium text-gray-600">
                    <tr>
                        <th class="px-6 py-4 text-left">Employee Information</th>
                        <th class="px-6 py-4 text-left">Department</th>
                        <th class="px-6 py-4 text-left">Work Status</th>
                        <!-- <th class="px-6 py-4 text-left">Action</th> -->
                        <th class="px-6 py-4 text-left">Document</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 text-sm">
                    <template x-for="employee in paginatedItems" :key="employee.EmployeeID">
                        <tr
                            :class="{'bg-red-100': employee.Status === 'Resigned', 'hover:bg-gray-50': employee.Status !== 'Resigned'}">
                            <td class="px-6 py-4">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-gray-600 text-sm" x-text="'(' + employee.EmployeeID + ')'">
                                        </p>
                                        <p class="font-medium text-gray-800"
                                            x-text="employee.FirstName + ' ' + employee.LastName"></p>

                                        <p class="text-gray-500 text-sm" x-text="employee.Email"></p>
                                    </div>
                                    <a :href="employee.Status !== 'Resigned' ? `/employee/${encodeURIComponent(employee.EmployeeID)}` : '#'"
                                        class="text-red-600 hover:text-red-700">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                        </svg>
                                    </a>
                                </div>
                            </td>
                            <td class="px-6 py-4" x-text="employee.Department"></td>
                            <!-- <td class="px-6 py-4">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium"
                                    :class="{
                                          'bg-gray-100 text-gray-800': employee.WorkStatus === 'ON-SITE',
                                          'bg-amber-100 text-amber-800': employee.WorkStatus.includes('WFH')
                                      }" x-text="employee.WorkStatus"></span>
                            </td> -->
                            <td class="px-6 py-4">
                                <form action="/employee/resigned" method="post" class="inline">
                                    <input type="hidden" name="employee_status" value="1">
                                    <input type="hidden" name="EmployeeID" :value="employee.EmployeeID">
                                    <input type="hidden" name="name"
                                        :value="employee.FirstName + ' ' + employee.LastName">
                                    <select name="Status"
                                        class="status px-3 py-1 border border-gray-300 rounded focus:ring-2 focus:ring-amber-500"
                                        :disabled="['Resigned', 'Terminated'].includes(employee.Status)">
                                        <option readonly selected x-text="employee.Status"></option>
                                        <option value="Resigned">Resigned</option>
                                    </select>
                                </form>
                            </td>
                            <td class="px-6 py-4">
                                <a :href="employee.Status !== 'Resigned' ? `/employee/custody/${encodeURIComponent(employee.EmployeeID)}` : '#'"
                                    class="inline-flex items-center gap-2 px-4 py-2 bg-black text-white rounded hover:bg-gray-800 transition-colors"
                                    :class="{'opacity-50 cursor-not-allowed': employee.Status === 'Resigned'}">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    <span class="text-sm">PDF</span>
                                </a>
                            </td>
                        </tr>
                    </template>

                    <tr x-show="noResults">
                        <td class="px-6 py-8 text-center text-gray-500 italic" colspan="5">
                            <span
                                x-text="employees.length === 0 ? 'No employees found in the database.' : 'No matching employees found.'"></span>
                        </td>
                    </tr>
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
    document.addEventListener('alpine:init', () => {
        Alpine.data('employeeTable', () => ({
            search: '',
            statusFilter: '',
            signStatus: '',
            deptFilter: '',
            currentPage: 1,
            itemsPerPage: 15,
            filteredEmployees: [],
            employees: <?= json_encode($employees) ?>,

            init() {
                this.filterEmployees();
                ['search', 'statusFilter', 'signStatus', 'deptFilter'].forEach(filter => {
                    this.$watch(filter, () => {
                        this.currentPage = 1;
                        this.filterEmployees();
                    });
                });
            },

            filterEmployees() {
                this.filteredEmployees = this.employees.filter(emp => {
                    const searchTerm = this.search.toLowerCase();
                    const matchesSearch = !searchTerm ||
                        emp.EmployeeID.toLowerCase().includes(searchTerm) ||
                        `${emp.FirstName} ${emp.LastName}`.toLowerCase().includes(searchTerm);

                    const matchesStatus = !this.statusFilter ||
                        emp.Status.toLowerCase() === this.statusFilter.toLowerCase();

                    const matchesSignStatus = !this.signStatus ||
                        (this.signStatus === 'Signed' && emp.Signature !== null) ||
                        (this.signStatus === 'Unsigned' && emp.Signature === null);

                    const matchesDept = !this.deptFilter ||
                        emp.Department.toLowerCase() === this.deptFilter.toLowerCase();

                    return matchesSearch && matchesStatus && matchesSignStatus && matchesDept;
                });
            },

            handleItemsPerPageChange() {
                this.currentPage = 1;
                this.filterEmployees();
            },

            get noResults() {
                return this.filteredEmployees.length === 0;
            },

            get totalPages() {
                return Math.ceil(this.filteredEmployees.length / this.itemsPerPage) || 1;
            },

            get paginatedItems() {
                const start = (this.currentPage - 1) * this.itemsPerPage;
                return this.filteredEmployees.slice(start, start + this.itemsPerPage);
            }
        }));
    });
</script>