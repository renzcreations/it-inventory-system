<div class="min-h-screen p-8">
    <div class="max-w-7xl mx-auto grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left Column -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Reports Section -->
            <div class="bg-white rounded-xl shadow-sm p-6" data-aos="fade-up" data-aos-anchor-placement="top-bottom" data-aos-duration="2000">
                <div class="flex md:flex-row flex-col items-center justify-between mb-6">
                    <h2 class="text-xl font-semibold text-gray-800">Inventory Reports</h2>
                    <form action="/generate-report" method="post" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                        <button
                            class="bg-amber-200 px-4 py-2 rounded-lg hover:bg-amber-300 disabled:cursor-not-allowed disabled:hover:bg-amber-200 disabled:opacity-50 transition-colors text-sm font-medium"
                            disabled>
                            Generate Report
                        </button>
                    </form>
                </div>

                <!-- Parts Card -->
                <div class="bg-gray-50 rounded-lg p-4 mb-6">
                    <div class="flex md:flex-row flex-col items-center justify-between mb-4">
                        <h3 class="font-medium text-gray-700">Parts Overview</h3>
                        <a href="/parts" class="text-amber-600 hover:text-amber-800 text-sm font-medium">
                            View Details →
                        </a>
                    </div>
                    <?php if (isset($availableCount, $inUseCount, $defectiveCount)): ?>
                        <div class="chart-container relative h-[300px] w-full mb-5">
                            <canvas id="partsChart"></canvas>
                        </div>
                        <div class="grid md:grid-cols-3 grid-cols-1 gap-4 text-center">
                            <div class="bg-white p-4 rounded-lg shadow-xs">
                                <p class="text-2xl font-semibold text-gray-800"><?= $availableCount ?></p>
                                <p class="text-sm text-gray-500 mt-1">Available</p>
                            </div>
                            <div class="bg-white p-4 rounded-lg shadow-xs">
                                <p class="text-2xl font-semibold text-gray-800"><?= $inUseCount ?></p>
                                <p class="text-sm text-gray-500 mt-1">In Use</p>
                            </div>
                            <div class="bg-white p-4 rounded-lg shadow-xs">
                                <p class="text-2xl font-semibold text-gray-800"><?= $defectiveCount ?></p>
                                <p class="text-sm text-gray-500 mt-1">Defective</p>
                            </div>
                        </div>
                        <script>
                            // Parts Bar Chart
                            const partsCtx = document.getElementById('partsChart').getContext('2d');
                            new Chart(partsCtx, {
                                type: 'bar',
                                data: {
                                    labels: ['Available', 'In Use', 'Defective'],
                                    datasets: [{
                                        label: 'Parts Count',
                                        data: [
                                            <?= (int) $availableCount ?>,
                                            <?= (int) $inUseCount ?>,
                                            <?= (int) $defectiveCount ?>
                                        ],
                                        backgroundColor: [
                                            '#84cc16',
                                            '#fcd34d',
                                            '#dc2626'
                                        ],
                                        borderColor: [
                                            '#84cc16',
                                            '#fcd34d',
                                            '#dc2626'
                                        ],
                                        borderWidth: 1
                                    }]
                                },
                                options: {
                                    responsive: true,
                                    maintainAspectRatio: false,
                                    animations: {
                                        y: {
                                            duration: 4000,
                                            easing: 'easeOutBounce'
                                        }
                                    },
                                    plugins: {
                                        legend: { display: false }
                                    }
                                }
                            });
                        </script>
                    <?php else: ?>
                        <div class="col-span-3 text-gray-400 py-4">No parts data available</div>
                    <?php endif; ?>
                </div>

                <!-- Accessories Table -->
                <div class="border-t pt-6">
                    <div class="flex md:flex-row flex-col items-center justify-between mb-4">
                        <h3 class="font-medium text-gray-700">Accessories Summary</h3>
                        <a href="/accessories" class="text-amber-600 hover:text-amber-800 text-sm font-medium">
                            View Details →
                        </a>
                    </div>
                    <div class="overflow-x-auto rounded-lg">
                        <table class="w-full">
                            <thead class="bg-gray-50 text-left text-sm font-medium text-gray-500">
                                <tr>
                                    <th class="px-4 py-3">Accessory</th>
                                    <th class="px-4 py-3">In Stock</th>
                                    <th class="px-4 py-3">Used</th>
                                    <th class="px-4 py-3">Defective</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php if (!empty($accessories)): ?>
                                    <?php foreach ($accessories as $item): ?>
                                        <tr class="hover:bg-gray-50 transition-colors">
                                            <td class="px-4 py-3 font-medium text-gray-800">
                                                <?= htmlspecialchars($item['AccessoriesName']) ?>
                                            </td>
                                            <td class="px-4 py-3"><?= htmlspecialchars($item['totalQty']) ?></td>
                                            <td class="px-4 py-3"><?= htmlspecialchars($item['totalAssigned'] ?? '-') ?></td>
                                            <td class="px-4 py-3"><?= htmlspecialchars($item['totalDefective'] ?? '-') ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="px-4 py-6 text-center text-gray-400">No accessories found
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Signed Reports -->
            <div class="bg-white rounded-xl shadow-sm p-6"  data-aos="fade-up" data-aos-anchor-placement="top-bottom" data-aos-duration="2000">
                <div class="flex md:flex-row flex-col items-center justify-between mb-6">
                    <h2 class="text-xl font-semibold text-gray-800">Document Status</h2>
                    <a href="/employee" class="text-amber-600 hover:text-amber-800 text-sm font-medium">
                        View Details →
                    </a>
                </div>
                <?php if (isset($signedCount, $unsignedCount)): ?>
                    <div class="chart-container relative h-[300px] w-full mb-5">
                        <canvas id="documentsChart"></canvas>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="bg-amber-50 p-4 rounded-lg text-center">
                            <p class="text-2xl font-semibold text-amber-800"><?= $signedCount ?></p>
                            <p class="text-sm text-amber-600 mt-1">Signed Documents</p>
                        </div>
                        <div class="bg-red-50 p-4 rounded-lg text-center">
                            <p class="text-2xl font-semibold text-red-800"><?= $unsignedCount ?></p>
                            <p class="text-sm text-red-600 mt-1">Pending Signatures</p>
                        </div>
                    </div>
                    <script>
                        // Documents Doughnut Chart
                        const docsCtx = document.getElementById('documentsChart').getContext('2d');
                        new Chart(docsCtx, {
                            type: 'doughnut',
                            data: {
                                labels: ['Signed', 'Unsigned'],
                                datasets: [{
                                    label: 'Documents',
                                    data: [<?= $signedCount ?>, <?= $unsignedCount ?>],
                                    backgroundColor: [
                                        '#fcd34d',
                                        '#dc2626'
                                    ],
                                    borderColor: [
                                        '#fcd34d',
                                        '#dc2626'
                                    ],
                                    borderWidth: 1
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                animations: {
                                    tension: {
                                        duration: 4000,
                                        easing: 'easeInOutQuart',
                                        from: 1,
                                        to: 0,
                                        loop: false
                                    },
                                    animateRotate: true,
                                    animateScale: true
                                },
                                plugins: {
                                    legend: {
                                        display: false,
                                    },
                                    tooltip: {
                                        callbacks: {
                                            label: function (context) {
                                                return context.label + ': ' + context.raw;
                                            }
                                        }
                                    }
                                }
                            }
                        });
                    </script>
                <?php else: ?>
                    <div class="col-span-2 text-gray-400 py-4">No signature data available</div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Right Column - Notifications -->
        <div class="bg-white rounded-xl shadow-sm p-6 h-fit" data-aos="fade-left" data-aos-anchor-placement="top-left" data-aos-duration="2000">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-xl font-semibold text-gray-800">Notifications</h2>
                <a href="/employee" class="text-amber-600 hover:text-amber-800 text-sm font-medium">
                    View All →
                </a>
            </div>

            <div class="space-y-3">
                <?php if (!empty($employees)): ?>
                    <?php $hasNotifications = false; ?>
                    <?php foreach ($employees as $employee): ?>
                        <?php if (!empty($employee['Signature'])): ?>
                            <div class="flex items-start p-4 bg-gray-50 rounded-lg border-l-4 border-amber-500">
                                <div class="ml-3">
                                    <p class="text-sm font-medium text-gray-800">
                                        <?= htmlspecialchars($employee['FirstName'] . ' ' . $employee['LastName']) ?>
                                    </p>
                                    <p class="text-sm text-gray-600 mt-1">
                                        Signed employment equipment agreement
                                    </p>
                                    <time class="text-xs text-gray-400 mt-1 block">
                                        <?= date('M j, Y', strtotime($employee['signature_upload_date'])) ?>
                                    </time>
                                </div>
                            </div>
                            <?php $hasNotifications = true; ?>
                        <?php endif; ?>
                    <?php endforeach; ?>

                    <?php if (!$hasNotifications): ?>
                        <div class="text-center p-4 text-gray-400">
                            No recent notifications
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="text-center p-4 text-gray-400">
                        No notifications available
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>