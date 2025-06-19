<div class="px-4 lg:px-8 py-6">
    <!-- Back Button -->
    <div class="mb-8">
        <a href="/computer"
            class="inline-flex items-center text-red-600 hover:text-red-700 transition-colors duration-200 font-medium">
            <i class="fa-solid fa-arrow-left mr-2"></i>
            Back to Computer Page
        </a>
    </div>

    <!-- Title Section -->
    <div class="mb-8">
        <?php if (!empty($PCName)): ?>
            <h1 class="text-3xl font-bold text-gray-900"><?= htmlspecialchars($PCName) ?> Returned Custody Details
            </h1>
        <?php else: ?>
            <h1 class="text-3xl font-bold text-gray-900">(No Computer Name) Returned Custody Details
            </h1>
        <?php endif; ?>
    </div>

    <!-- Parts Table -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-900 text-white">
                    <tr>
                        <th class="px-6 py-4 text-left uppercase text-sm font-semibold">Part ID</th>
                        <th class="px-6 py-4 text-left uppercase text-sm font-semibold">Type</th>
                        <th class="px-6 py-4 text-left uppercase text-sm font-semibold">Brand</th>
                        <th class="px-6 py-4 text-left uppercase text-sm font-semibold">Model</th>
                        <th class="px-6 py-4 text-left uppercase text-sm font-semibold">Serial Number</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php if (!empty($parts)): ?>
                        <?php foreach ($parts as $data): ?>
                            <tr class="hover:bg-gray-50 transition-colors duration-200">
                                <td class="px-6 py-4 text-sm text-gray-900 font-medium uppercase">
                                    <?= htmlspecialchars($data['uniqueID']) ?>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600"><?= htmlspecialchars($data['PartType']) ?></td>
                                <td class="px-6 py-4 text-sm text-gray-600"><?= htmlspecialchars($data['Brand']) ?></td>
                                <td class="px-6 py-4 text-sm text-gray-600"><?= htmlspecialchars($data['Model']) ?></td>
                                <td class="px-6 py-4 text-sm text-gray-600">
                                    <?= htmlspecialchars($data['SerialNumber'] ?? 'No Serial Number') ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-gray-500 italic">
                                No parts found in this system
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
            <?php if (!empty($accessories)): ?>
                <hr>
                <h2 class="text-xl font-bold text-gray-900 text-center uppercase mt-5">Accessories</h2>
                <div class="flex gap-4 items-center justify-center my-5">
                    <?php foreach ($accessories as $accessory): ?>
                        <span
                            class="px-3 py-1 bg-white rounded-full text-sm shadow-sm border border-amber-200 flex items-center gap-3">
                            <?= htmlspecialchars($accessory['AccessoriesName'] . ' (' . $accessory['Brand'] . ') - ' . $accessory['AccessoriesPRNumber']) ?>
                        </span>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="text-sm text-amber-600 italic text-center my-5">No accessories assigned</p>
            <?php endif; ?>
        </div>
    </div>
</div>