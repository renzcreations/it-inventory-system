<div class="min-h-[70vh] flex items-center justify-center px-4 py-8">
    <div class="w-full max-w-2xl bg-white rounded-xl shadow-sm border border-gray-100 p-8">
        <?php if (!empty($viewPart)): ?>
            <?php foreach ($viewPart as $data): ?>
                <!-- Header Section -->
                <div class="mb-8 text-center">
                    <h1 class="text-2xl font-bold text-gray-900 uppercase mb-2">
                        Update <?= htmlspecialchars($data['uniqueID']) ?>
                    </h1>
                    <p class="text-sm text-gray-600">Component Information</p>
                </div>

                <!-- Update Form -->
                <form action="/parts/update" method="post" class="space-y-6">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                    <input type="hidden" name="PartID" id="PartID" value="<?= $data['PartID'] ?>">

                    <!-- Disabled Part Type -->
                    <div class="space-y-2">
                        <label class="text-sm font-medium text-gray-600">Component Type</label>
                        <input type="text" name="PartType" id="PartType" value="<?= $data['PartType'] ?>"
                            class="w-full px-4 py-2.5 border border-gray-200 rounded-lg bg-gray-100 cursor-not-allowed"
                            disabled>
                    </div>

                    <!-- Editable Fields -->
                    <div class="space-y-2">
                        <label class="text-sm font-medium text-gray-600">Unique Identifier</label>
                        <input type="text" name="uniqueID" id="uniqueID" value="<?= $data['uniqueID'] ?>"
                            class="w-full px-4 py-2.5 border border-gray-200 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-transparent uppercase">
                    </div>

                    <div class="space-y-2">
                        <label class="text-sm font-medium text-gray-600">Brand</label>
                        <input type="text" name="Brand" id="Brand" value="<?= $data['Brand'] ?>"
                            class="w-full px-4 py-2.5 border border-gray-200 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-transparent">
                    </div>

                    <div class="space-y-2">
                        <label class="text-sm font-medium text-gray-600">Model</label>
                        <input type="text" name="Model" id="Model" value="<?= $data['Model'] ?>"
                            class="w-full px-4 py-2.5 border border-gray-200 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-transparent">
                    </div>

                    <div class="space-y-2">
                        <label class="text-sm font-medium text-gray-600">Serial Number</label>
                        <input type="text" name="SerialNumber" id="SerialNumber" value="<?= $data['SerialNumber'] ?>"
                            class="w-full px-4 py-2.5 border border-gray-200 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-transparent">
                    </div>

                    <!-- Action Buttons -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mt-8">
                        <button type="submit" id="updateParts"
                            class="w-full bg-gray-900 text-white py-2.5 px-6 rounded-lg hover:bg-gray-800 transition-colors duration-200 font-medium">
                            Save Changes
                        </button>
                        <a href="/parts"
                            class="w-full bg-red-600 text-white py-2.5 px-6 rounded-lg hover:bg-red-700 transition-colors duration-200 font-medium text-center">
                            Cancel
                        </a>
                    </div>
                </form>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="text-center p-8">
                <p class="text-gray-500 italic">No component information available</p>
            </div>
        <?php endif; ?>
    </div>
</div>