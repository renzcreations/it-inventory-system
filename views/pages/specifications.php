<div class="px-4 lg:px-8 py-6" data-aos="fade-up" data-aos-anchor-placement="top-bottom" data-aos-duration="1000">
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
        <?php if (!empty($data)): ?>
            <?php foreach ($data as $item): ?>
                <h1 class="text-3xl font-bold text-gray-900"><?= htmlspecialchars($item['PCName']) ?> Specifications</h1>
            <?php endforeach; ?>
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
                        <th class="px-6 py-4 text-right uppercase text-sm font-semibold">Actions</th>
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
                                <td class="px-6 py-4 float-right">
                                    <form action="/computer/uninstall" method="post" class="inline">
                                        <input type="hidden" name="remove-part" value="1">
                                        <input type="hidden" name="Status" value="Available">
                                        <input type="hidden" name="HistoryStatus" value="Returned">
                                        <input type="hidden" name="PartID" value="<?= htmlspecialchars($data['PartID']) ?>">
                                        <input type="hidden" name="PCName" value="<?= htmlspecialchars($data['PCName']) ?>">
                                        <input type="hidden" name="Brand" value="<?= htmlspecialchars($data['Brand']) ?>">

                                        <button type="submit"
                                            class="flex bg-red-600 text-white p-2 rounded hover:bg-red-700 transition-colors"
                                            name="removeTemp" title="Mark Defective">
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
                            <td colspan="6" class="px-6 py-8 text-center text-gray-500 italic">
                                No parts found in this system
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>