<div class="min-h-screen p-6" data-aos="fade-up" data-aos-anchor-placement="top-bottom" data-aos-duration="1000">

    <div class="navigation-buttons mt-5 ">
        <div class="computerNav mt-3 p-2 bg-gray-500 rounded-t-lg">
            <p class="text-white">Menu Buttons</p>
            <i class="fa-solid fa-caret-down text-white"></i>
        </div>
        <nav class="overflow-hidden border-b-3 shadow-lg bg-gray-200" id="computerBtns">
            <ul class="flex md:flex-row flex-col gap-5 justify-between items-center px-5 py-3 uppercase">
                <li><a href="#" class="computer_nav" id="backBtn">Parts History</a></li>
                <li><a href="#" class="computer_nav" id="addDataBtn">Add Parts Data</a></li>
                <li><a href="#" class="computer_nav" id="installBtn">Update Computer</a></li>
            </ul>
        </nav>
    </div>


    <!-- parts data -->
    <div id="parts_table" class="bg-white rounded-b-xl shadow-sm" x-data="genericTable()"
        data-items='<?= htmlspecialchars(json_encode($parts_data), ENT_QUOTES, 'UTF-8', true) ?>'
        data-filters='{"status": "", "partType": ""}'>
        <div class="p-6 border-b border-gray-200">
            <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4">
                <h2 class="text-2xl font-bold text-gray-800">Parts Management</h2>
                <div class="w-full lg:max-w-3xl space-y-4">
                    <input type="text" placeholder="Search parts and peripherals..."
                        class="w-full px-4 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-amber-500"
                        x-model="search">

                    <div class="grid grid-cols-1 md:grid-cols-4 gap-2">
                        <select x-model="currentFilters.PartStatus"
                            class="w-full px-4 py-2 border border-gray-300 rounded text-sm focus:ring-2 focus:ring-amber-500">
                            <option value="">All Status</option>
                            <option value="Available">Available</option>
                            <option value="In Use">In Use</option>
                            <option value="Defective">Defective</option>
                        </select>

                        <select x-model="currentFilters.PartType"
                            class="w-full px-4 py-2 border border-gray-300 rounded text-sm focus:ring-2 focus:ring-amber-500">
                            <option value="">All Types</option>
                            <?php foreach ($types as $type): ?>
                                <option value="<?= htmlspecialchars($type['PartType']) ?>">
                                    <?= htmlspecialchars($type['PartType']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <select x-model="orderDirection"
                            class="w-full px-4 py-2 border border-gray-300 rounded text-sm focus:ring-2 focus:ring-amber-500">
                            <option value="">Sort Parts ID By</option>
                            <option value="asc">Ascending</option>
                            <option value="desc">Descending</option>
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

        <!-- table -->
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 text-sm font-medium text-gray-600">
                    <tr>
                        <th class="px-6 py-4 text-left">PR No.</th>
                        <th class="px-6 py-4 text-left">Parts ID</th>
                        <th class="px-6 py-4 text-left">Parts</th>
                        <th class="px-6 py-4 text-left">Assigned To</th>
                        <th class="px-6 py-4 text-left">Status</th>
                        <th class="px-6 py-4 text-left">Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 text-sm">
                    <template x-if="!initialized">
                        <tr>
                            <td colspan="4" class="text-center py-4">Loading data...</td>
                        </tr>
                    </template>
                    <template x-for="(item, index) in paginatedItems" :key="`parts-${item.PartID}-${index}`">
                        <tr class="hover:bg-gray-100 border-b">
                            <td :class="'px-6 py-4 ' + (item.PRNumber ? 'uppercase font-bold' : 'text-xs break-normal')"
                                x-text="item.PRNumber ?? 'No Purchase Requisition No. | Old Data'"></td>
                            <td class="px-6 py-4 uppercase" x-text="item.uniqueID"></td>
                            <td class="px-6 py-4">
                                <div class=" flex items-center justify-between">
                                    <div>
                                        <span class="font-medium text-gray-800" x-text="item.PartType"></span> -
                                        <span class="font-bold" x-text="item.Brand"></span>
                                        <span class="font-bold" x-text="item.Model"></span>
                                        (<span class="text-gray-600 text-sm italic"
                                            x-text="item.SerialNumber ?? 'No Serial Number'"></span>)
                                    </div>

                                    <a x-bind:href="`/parts/${encodeURIComponent(item.PartID)}`"
                                        class="text-red-600 hover:text-red-700">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                        </svg>
                                    </a>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div
                                    :class="item.PCName !== null ? 'flex justify-between items-center' : 'text-center'">
                                    <span
                                        x-text="item.HistoryStatus === 'Assigned' ? `${item.FirstName} ${item.LastName || ''}` : 'Unassigned'"></span>
                                    <a x-bind:href="`/computer/specifications/${encodeURIComponent(item.PCName)}`">
                                        <button
                                            :class="item.PCName !== null ? 'bg-green-500 rounded-xl p-2 text-sm' : ''"
                                            x-text="item.PCName ?? ''"></button>
                                    </a>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <form action="/parts/defective" method="post">

                                    <input type="hidden" name="id" :value="item.PartID">
                                    <input type="hidden" name="name" :value="item.Brand">
                                    <select name="Status"
                                        class="part_status px-3 py-1 border border-gray-300 rounded focus:ring-2 focus:ring-amber-500"
                                        :disabled="item.HistoryStatus === 'Defective'"
                                        :class="item.HistoryStatus === 'Defective' ? 'cursor-not-allowed' : ''">
                                        <option disabled selected
                                            x-text="item.PartStatus ? item.PartStatus : 'Available'">
                                        </option>
                                        <option value="Defective">Defective</option>
                                    </select>
                                </form>
                            </td>
                            <td class="px-6 py-4"
                                x-text="item.created_at || item.updated_at ? new Date(item.created_at ?? item.updated_at).toLocaleString('en-US', { month: 'long', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit', hour12: true }) : 'N/A'">
                            </td>
                        </tr>
                    </template>

                    <template x-if="noResults">
                        <tr>
                            <td class="px-6 py-4 text-center italic text-gray-400" colspan="7">
                                No parts found.
                            </td>
                        </tr>
                    </template>
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

    <!-- add parts -->
    <div class="bg-white rounded-b-xl shadow-sm grid md:grid-cols-2 grid-cols-1" id="addParts_content">
        <div class="p-6 border-b border-gray-200">
            <div class="flex justify-between items-center">
                <h1 class="md:text-2xl text-md font-bold text-gray-800 mb-8">Add Part/s Information</h1>
                <span id="prBtn"
                    class="select-none bg-amber-200 px-4 py-2 rounded-lg hover:bg-amber-300 transition-colors text-sm font-medium cursor-pointer">Add
                    Purchase Requisition No.
                </span>
            </div>
            <form action="/parts/create" method="post" id="addPartsData" class="flex flex-col gap-3">

                <input type="text" name="PRNumber" id="PRNumber" placeholder="Input Purchase Requisition  No."
                    class="w-full px-4 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-amber-500 hidden"
                    value="<?= $_SESSION['old_input']['PRNumber'] ?? '' ?>">
                <select name="PartType" id="PartType"
                    class="w-full px-4 py-2 border border-gray-300 rounded text-sm focus:ring-2 focus:ring-amber-500"
                    required autofocus>
                    <option class="bg-gray-400" disabled <?= empty($_SESSION['old_input']['PartType']) ? 'selected' : '' ?>>
                        -- Select Part Type --</option>
                    <option value="Processor" <?= ($_SESSION['old_input']['PartType'] ?? '') === 'Processor' ? 'selected' : '' ?>>Processor</option>
                    <option value="Motherboard" <?= ($_SESSION['old_input']['PartType'] ?? '') === 'Motherboard' ? 'selected' : '' ?>>Motherboard</option>
                    <option value="GPU" <?= ($_SESSION['old_input']['PartType'] ?? '') === 'GPU' ? 'selected' : '' ?>>GPU
                    </option>
                    <option value="RAM" <?= ($_SESSION['old_input']['PartType'] ?? '') === 'RAM' ? 'selected' : '' ?>>RAM
                    </option>
                    <option value="HDD" <?= ($_SESSION['old_input']['PartType'] ?? '') === 'HDD' ? 'selected' : '' ?>>HDD
                    </option>
                    <option value="SSD" <?= ($_SESSION['old_input']['PartType'] ?? '') === 'SSD' ? 'selected' : '' ?>>SSD
                    </option>
                    <option value="Monitor" <?= ($_SESSION['old_input']['PartType'] ?? '') === 'Monitor' ? 'selected' : '' ?>>
                        Monitor</option>
                    <option value="Pen Display" <?= ($_SESSION['old_input']['PartType'] ?? '') === 'Pen Display' ? 'selected' : '' ?>>Pen Display</option>
                    <option value="Pen Tablet" <?= ($_SESSION['old_input']['PartType'] ?? '') === 'Pen Tablet' ? 'selected' : '' ?>>Pen Tablet</option>
                    <option value="Power Supply" <?= ($_SESSION['old_input']['PartType'] ?? '') === 'Power Supply' ? 'selected' : '' ?>>Power Supply</option>
                </select>

                <input type="text" name="Brand" id="Brand" placeholder="Brand"
                    class="w-full px-4 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-amber-500"
                    value="<?= $_SESSION['old_input']['Brand'] ?? '' ?>">
                <input type="text" name="Model" id="Model" placeholder="Model"
                    class="w-full px-4 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-amber-500" required
                    value="<?= $_SESSION['old_input']['Model'] ?? '' ?>">
                <input type="text" name="SerialNumber" id="SerialNumber" placeholder="Serial Number"
                    class="w-full px-4 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-amber-500"
                    value="<?= $_SESSION['old_input']['SerialNumber'] ?? '' ?>">
                <button class="bg-black text-white px-6 py-3 hover:bg-gray-800 transition-colors lg:w-auto w-full"
                    name="addPart"> Add </button>
            </form>
        </div>
        <div class="overflow-x-auto mt-8">
            <table class="w-full">
                <thead class="bg-amber-300 text-sm font-medium">
                    <tr>
                        <th colspan="4" class="px-6 py-4 text-center md:text-lg text-md ">Added Parts</th>
                    </tr>
                </thead>
                <thead class="bg-gray-50 text-sm font-medium text-gray-600">
                    <tr>
                        <th class="px-6 py-4 text-left">PR No.</th>
                        <th class="px-6 py-4 text-left">ID</th>
                        <th class="px-6 py-4 text-left">Parts</th>
                        <th class="px-6 py-4 text-left">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 text-sm">
                    <?php if (!empty($temp_part)): ?>
                        <?php foreach ($temp_part as $data): ?>
                            <?php
                            $PRNumber = htmlspecialchars($data['PRNumber'] ?? '');
                            $PartID = htmlspecialchars($data['PartID']);
                            $PartType = htmlspecialchars($data['PartType']);
                            $Brand = htmlspecialchars($data['Brand']);
                            $Model = htmlspecialchars($data['Model']);
                            $SerialNumber = htmlspecialchars($data['SerialNumber']);
                            ?>
                            <tr>
                                <td class="px-6 py-4"><?= $PRNumber ?></td>
                                <td class="px-6 py-4"><?= $PartID ?></td>
                                <td class="px-6 py-4">
                                    <span class="font-bold">
                                        <?= $PartType . ' ' . $Brand . ' ' . $Model ?>
                                    </span>
                                    <span class="text-xs italic">(<?= $SerialNumber ?>) </span>
                                </td>
                                <td class="px-6 py-4">
                                    <form action="/admin/remove-temp-part" method="post" id="removeTemp">

                                        <input type="hidden" name="id" value="<?= $PartID ?>">
                                        <input type="hidden" name="name" value="<?= $Brand ?>">
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
                            <td colspan="4" class="px-6 py-4 text-center italic text-gray-400">No parts found.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <div class="py-5 px-2">
                <form action="/parts/store" method="post" id="tempParts">
                    <?php if (!empty($temp_part)): ?>
                        <?php foreach ($temp_part as $data): ?>
                            <input type="hidden" name="PRNumber[]" value="<?= htmlspecialchars($data['PRNumber'] ?? '') ?>">
                            <input type="hidden" name="PartID[]" value="<?= htmlspecialchars($data['PartID']) ?>">
                            <input type="hidden" name="PartType[]" value="<?= htmlspecialchars($data['PartType']) ?>">
                            <input type="hidden" name="Brand[]" value="<?= htmlspecialchars($data['Brand']) ?>">
                            <input type="hidden" name="Model[]" value="<?= htmlspecialchars($data['Model']) ?>">
                            <input type="hidden" name="SerialNumber[]" value="<?= htmlspecialchars($data['SerialNumber']) ?>">
                        <?php endforeach; ?>
                        <button type="submit" class="bg-black py-2 w-full text-white hover:opacity-80"
                            id="addToParts">Submit</button>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>


    <!-- install content -->
    <div class="bg-white rounded-b-xl shadow-sm grid md:grid-cols-2 grid-cols-1" id="install_content">
        <div class="p-6 border-b border-gray-200">
            <div class="flex justify-between items-center mb-8">
                <h1 class="md:text-2xl text-md font-bold text-gray-800">UPDATE A COMPUTER</h1>
                <form action="/computer/reset" method="post" id="resetForm">
                    <button type="submit"
                        class="bg-red-700 px-4 py-2 text-white hover:opacity-80 transition ease-in-out duration-150 rounded-2xl"
                        name="resetBtn">
                        Reset
                    </button>
                </form>
            </div>
            <form action="/computer/check" method="post" id="updatePC" class="flex flex-col gap-2">
                <input type="text" name="PCName" id="PCName" placeholder="Search for Computer Name"
                    class="w-full px-4 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-amber-500">
                <button type="submit"
                    class="flex-1 bg-black text-white px-6 py-3 rounded hover:bg-gray-800 transition-colors"
                    name="buildPC">
                    Locate Computer
                </button>
            </form>

            <!-- Temporary parts list -->
            <div class="overflow-x-auto mt-8">
                <table class="w-full">
                    <thead class="bg-amber-300 text-sm font-medium">
                        <?php if (!empty($tempPC)): ?>
                            <tr>
                                <td colspan="2" class="px-6 py-4 text-center md:text-lg text-md">
                                    <?= htmlspecialchars($tempPC['PCName']) ?>
                                </td>
                            </tr>
                        <?php else: ?>
                            <tr>
                                <td colspan="2" class="px-6 py-4 text-center md:text-lg text-md italic">
                                    Search for computer first
                                </td>
                            </tr>
                        <?php endif; ?>
                        <tr class="bg-gray-50 text-sm font-medium text-gray-600">
                            <th class="px-6 py-4 text-left">Part/s</th>
                            <th class="px-6 py-4 text-left">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 text-sm">
                        <?php if (!empty($tempPart)): ?>
                            <?php foreach ($tempPart as $data): ?>
                                <tr class="hover:bg-gray-100">
                                    <td class="px-6 py-4 text-left">
                                        <span class="font-medium text-gray-800">
                                            <?= htmlspecialchars($data['PartType'] . ' ' . $data['Brand'] . ' ' . $data['Model']) ?>
                                        </span>
                                        <span class="text-xs italic">(<?= htmlspecialchars($data['SerialNumber']) ?>)</span>
                                    </td>
                                    <td class="px-6 py-4 text-left">
                                        <form action="/computer/delete" method="post" id="removePart">

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
                            <tr>
                                <td colspan="2" class="px-6 py-4 text-left">
                                    <form action="/computer/update" method="post" id="update-pc">
                                        <?php if (!empty($tempPC)): ?>
                                            <input type="hidden" name="PCID[]" value="<?= htmlspecialchars($tempPC['PCID']) ?>">
                                            <input type="hidden" name="PCName"
                                                value="<?= htmlspecialchars($tempPC['PCName']) ?>">
                                        <?php endif; ?>
                                        <?php foreach ($tempPart as $data): ?>
                                            <input type="hidden" name="PartID[]"
                                                value="<?= htmlspecialchars($data['PartID']) ?>">
                                        <?php endforeach; ?>
                                        <button type="submit" class="bg-black px-6 py-2 text-white hover:opacity-80 w-full"
                                            id="updateComputer"> Update Computer </button>
                                    </form>
                                </td>
                            </tr>
                        <?php else: ?>
                            <tr>
                                <td class="px-6 py-4 text-center italic text-gray-400" colspan="2">
                                    Add parts to continue.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- install_parts_data section -->
        <div id="install_parts_data" x-data="genericTable('install')" class="bg-white rounded-xl shadow-sm"
            data-items='<?= htmlspecialchars(json_encode($parts_available), ENT_QUOTES, 'UTF-8', true) ?>'
            data-filters='{"partType": ""}'>
            <div class="p-6 border-b border-gray-200 flex justify-center items-center">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-2">
                    <input type="text" placeholder="Search Parts..."
                        class="w-full px-4 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-amber-500"
                        x-model="search">
                    <select x-model="currentFilters.PartType"
                        class="w-full px-4 py-2 border border-gray-300 rounded text-sm focus:ring-2 focus:ring-amber-500">
                        <option value="">All Types</option>
                        <?php foreach ($types as $type): ?>
                            <option value="<?= htmlspecialchars($type['PartType']) ?>">
                                <?= htmlspecialchars($type['PartType']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <select x-model="itemsPerPage" @change="handleItemsPerPageChange"
                        class="w-full px-4 py-2 border border-gray-300 rounded text-sm focus:ring-2 focus:ring-amber-500">
                        <option :value="15">15/page</option>
                        <option :value="25">25/page</option>
                        <option :value="50">50/page</option>
                    </select>
                </div>
            </div>

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
                        <template x-if="!initialized">
                            <tr>
                                <td colspan="4" class="text-center py-4">Loading data...</td>
                            </tr>
                        </template>
                        <template x-for="(item, index) in paginatedItems" :key="`install-${item.PartID}-${index}`">
                            <tr class="hover:bg-gray-100">
                                <td class="px-6 py-4 text-left " x-text="item.PartID"></td>
                                <td class="px-6 py-4 text-left ">
                                    <span class="font-bold" x-text="item.PartType"></span> -
                                    <span class="font-bold" x-text="item.Brand"></span>
                                    <span class="font-bold" x-text="item.Model"></span>
                                    (<span class="font-medium text-gray-800 italic"
                                        x-text="item.SerialNumber ?? 'No Serial Number'"></span>)
                                </td>
                                <td class="px-6 py-4 text-left">
                                    <form action="/computer/add" method="post">
                                        <input type="hidden" name="PartID" :value="item.PartID">
                                        <input type="hidden" name="PartType" :value="item.PartType">
                                        <input type="hidden" name="Brand" :value="item.Brand">
                                        <input type="hidden" name="Model" :value="item.Model">
                                        <input type="hidden" name="SerialNumber"
                                            :value="item.SerialNumber ?? 'No Serial Number'">
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

                        <template x-if="noResults">
                            <tr>
                                <td class="px-6 py-4 text-center italic text-gray-400" colspan="7">
                                    No parts found.
                                </td>
                            </tr>
                        </template>
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
    </div>
</div>
<script>
    function genericTable(tableType = 'default') {
        return {
            tableType: tableType,
            search: '',
            currentFilters: {},
            currentPage: 1,
            itemsPerPage: 15,
            allItems: [],
            originalItems: [],
            filteredItems: [],
            orderDirection: '',
            noResults: false,
            initialized: false,

            init() {
                try {
                    this.allItems = this.$el.dataset.items ?
                        JSON.parse(this.$el.dataset.items) : [];

                    if (!Array.isArray(this.allItems)) {
                        console.error('Invalid data format for', this.tableType);
                        this.allItems = [];
                    }

                    this.originalItems = [...this.allItems];

                    this.currentFilters = this.$el.dataset.filters ?
                        JSON.parse(this.$el.dataset.filters) : {};

                    this.allItems = this.allItems.map(item => ({
                        PartID: item.PartID || `no-id-${Math.random().toString(36).substr(2, 9)}`,
                        ...item
                    }));

                    this.initialized = true;
                } catch (e) {
                    console.error(`Error initializing ${this.tableType} table:`, e);
                    this.allItems = [];
                    this.originalItems = [];
                    this.currentFilters = {};
                }

                this.$watch('search', () => this.filterItems());
                this.$watch('currentFilters', () => this.filterItems(), { deep: true });
                this.$watch('itemsPerPage', () => this.handleItemsPerPageChange());
                this.$watch('orderDirection', () => this.filterItems());

                this.filterItems();
            },

            handleItemsPerPageChange() {
                this.currentPage = 1; // Reset to first page when items per page changes
                this.filterItems();
            },

            filterItems() {
                if (!this.initialized) return;

                this.filteredItems = this.originalItems.filter(item => {
                    const matchesSearch = !this.search ||
                        Object.values(item).some(val =>
                            String(val).toLowerCase().includes(this.search.toLowerCase())
                        );

                    const matchesFilters = Object.entries(this.currentFilters)
                        .every(([key, value]) => {
                            if (!value) return true;
                            const itemValue = item[key]?.toString().toLowerCase();
                            return itemValue === value.toLowerCase();
                        });

                    return matchesSearch && matchesFilters;
                });

                this.sortItems();
            },

            sortItems() {
                if (!this.initialized) return;

                if (this.orderDirection) {
                    this.filteredItems.sort((a, b) => {
                        if (a.uniqueID < b.uniqueID) return this.orderDirection === 'asc' ? -1 : 1;
                        if (a.uniqueID > b.uniqueID) return this.orderDirection === 'asc' ? 1 : -1;
                        return 0;
                    });
                }

                this.updatePagination();
                this.noResults = this.filteredItems.length === 0;
            },

            updatePagination() {
                if (!this.initialized) return;
                this.currentPage = Math.max(1, Math.min(this.currentPage, this.totalPages));
            },

            get totalPages() {
                if (!this.initialized) return 1;
                return Math.ceil(this.filteredItems.length / parseInt(this.itemsPerPage, 10)) || 1;
            },

            get paginatedItems() {
                if (!this.initialized || !Array.isArray(this.filteredItems)) return [];
                const perPage = parseInt(this.itemsPerPage, 10);
                const start = (this.currentPage - 1) * perPage;
                return this.filteredItems.slice(start, start + perPage);
            }
        }
    }

</script>