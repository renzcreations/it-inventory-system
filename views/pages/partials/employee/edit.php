<div class="min-h-[70vh] flex items-center justify-center px-4 py-8">
    <div class="w-full max-w-2xl bg-white rounded-xl shadow-sm border border-gray-100 p-8">
        <?php if (!empty($viewEmployee)): ?>
            <?php foreach ($viewEmployee as $data): ?>
                <!-- Header Section -->
                <div class="mb-8 text-center">
                    <h1 class="text-2xl font-bold text-gray-900 mb-2">
                        Update <?= htmlspecialchars($data['FirstName']) ?>'s Profile
                    </h1>
                    <p class="text-sm text-gray-600">Employee Information Management</p>
                </div>

                <!-- Update Form -->
                <form action="/employee/update" method="post" class="space-y-6">
                    <input type="hidden" name="originalID" id="originalID" value="<?= $data['id'] ?>">

                    <div class="space-y-2">
                        <label class="text-sm font-medium text-gray-600">Employee Number</label>
                        <input type="text" name="EmployeeID" id="EmployeeID"
                            value="<?= htmlspecialchars($_SESSION['old_input']['EmployeeID'] ?? $data['EmployeeID']) ?>"
                            class="w-full px-4 py-2.5 border border-gray-200 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-transparent">
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-2">
                            <label class="text-sm font-medium text-gray-600">First Name</label>
                            <input type="text" name="FirstName" id="FirstName"
                                value="<?= htmlspecialchars($_SESSION['old_input']['FirstName'] ?? $data['FirstName']) ?>"
                                class="w-full px-4 py-2.5 border border-gray-200 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-transparent">
                        </div>

                        <div class="space-y-2">
                            <label class="text-sm font-medium text-gray-600">Last Name</label>
                            <input type="text" name="LastName" id="LastName"
                                value="<?= htmlspecialchars($_SESSION['old_input']['LastName'] ?? $data['LastName']) ?>"
                                class="w-full px-4 py-2.5 border border-gray-200 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-transparent">
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label class="text-sm font-medium text-gray-600">Email Address</label>
                        <input type="email" name="Email" id="Email"
                            value="<?= htmlspecialchars($_SESSION['old_input']['Email'] ?? $data['Email']) ?>"
                            class="w-full px-4 py-2.5 border border-gray-200 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-transparent">
                    </div>

                    <!-- Department Section -->
                    <div class="space-y-4">
                        <p class="text-sm text-gray-600 italic flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-amber-500" viewBox="0 0 20 20"
                                fill="currentColor">
                                <path fill-rule="evenodd"
                                    d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                                    clip-rule="evenodd" />
                            </svg>
                            Enter new department or select from existing
                        </p>

                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                            <input type="text" name="inputDepartment" id="Department" placeholder="Department name" required
                                value="<?= htmlspecialchars($_SESSION['old_input']['Department'] ?? $data['Department']) ?>"
                                class="w-full px-4 py-2.5 border border-gray-200 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-transparent">

                            <select name="selectDepartment"
                                class="w-full px-4 py-2.5 border border-gray-200 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-transparent">
                                <option value="" class="text-gray-400">Select existing department</option>
                                <?php if (!empty($dept)): ?>
                                    <?php foreach ($dept as $filter): ?>
                                        <option value="<?= htmlspecialchars($filter['Department']) ?>"
                                            <?= (isset($_SESSION['old_input']['Department']) && $_SESSION['old_input']['Department'] === $filter['Department']) || $data['Department'] === $filter['Department'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($filter['Department']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mt-8">
                        <button type="submit" id="updateBtn"
                            class="w-full bg-gray-900 text-white py-2.5 px-6 rounded-lg hover:bg-gray-800 transition-colors duration-200 font-medium">
                            Save Changes
                        </button>
                        <a href="/employee"
                            class="w-full bg-red-600 text-white py-2.5 px-6 rounded-lg hover:bg-red-700 transition-colors duration-200 font-medium text-center">
                            Cancel
                        </a>
                    </div>
                </form>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="text-center p-8">
                <p class="text-gray-500 italic">No employee records found</p>
            </div>
        <?php endif; ?>
    </div>
</div>