<style>
    @media print {
        body * {
            visibility: hidden;
        }

        #content_print,
        #content_print * {
            visibility: visible;
        }

        #choices * {
            display: none;
        }

        #content_print {
            position: absolute;
            top: 0;
            left: 0;
            width: 95%;
        }
    }
</style>
<?php if (isset($_SESSION['login']) && $_SESSION['login'] === true): ?>
    <div class="fixed top-4 left-4 right-4 flex justify-between z-10">
        <a href="/employee"
            class="bg-red-600 text-white px-6 py-2 rounded-lg hover:bg-red-700 transition-colors duration-200">
            Go Back
        </a>
        <button class="bg-gray-800 text-white px-6 py-2 rounded-lg hover:bg-gray-700" id="printBtn">
            Generate PDF
        </button>
    </div>
<?php endif; ?>

<div class="min-h-screen flex items-center justify-center px-4 py-5" id="content_print" data-aos="fade-up"
    data-aos-anchor-placement="top-bottom" data-aos-duration="1000">
    <div class="w-full max-w-7xl bg-white rounded-xl shadow-sm border border-gray-100 p-8">
        <!-- Company Header -->
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-gray-900 uppercase">HPL Gamedesign Corporation</h1>
            <div class="mt-4 text-gray-600">
                <?php if (!empty($result) && isset($result->address) && isset($result->email) && isset($result->contact)): ?>
                    <p><?= htmlspecialchars($result->address) ?></p>
                    <p class="mt-2">
                        <?= htmlspecialchars($result->email) ?> •
                        <?= htmlspecialchars($result->contact) ?> •
                        (02) 8 808 6920
                    </p>
                <?php else: ?>
                    <p>27th Floor, IBM Plaza Building, E. Rodriguez Jr. Avenue, Eastwood, Quezon City, 1110</p>
                    <p class="mt-2">
                        admin@hplgamedesign.com • 09202773422 • (02) 8 808 6920
                    </p>
                <?php endif; ?>
            </div>
        </div>

        <hr class="border-gray-200 my-8">

        <!-- Agreement Content -->
        <div class="space-y-6 text-gray-700">
            <h2 class="text-2xl font-bold text-center text-gray-900 mb-6">EMPLOYEE EQUIPMENT AGREEMENT</h2>

            <p>I, <span class="font-semibold underline">
                    <?= !empty($name) ? htmlspecialchars($name) : 'No Data Found' ?>
                </span>, hereby acknowledge and agree to the following terms and conditions regarding the equipment
                supplied to me by HPL Gamedesign Corporation, referred to as the Company:
            </p>

            <div>
                <h3 class="font-semibold text-amber-600 mb-2">Equipment Care and Responsibility:</h3>
                <p>
                    I agree to take proper care of all equipment supplied to me by the Company. This includes, but
                    is not limited to, laptops, cell phones, monitors, software licenses, or any other
                    company-provided equipment deemed necessary by Company management for the performance of my job
                    duties. Proper care entails safeguarding the equipment from damage and ensuring its maintenance
                    in good working condition.
                </p>
            </div>

            <div>
                <h3 class="font-semibold text-amber-600 mb-2">Equipment Return Policy:</h3>
                <p>
                    Upon termination of my employment, whether by resignation or termination, I understand and agree
                    to return all Company-supplied equipment within the specified time-frames:
                </p>
                <ul class="ml-5 mt-3 space-y-3 list-disc">
                    <li>
                        All employees, including those working remotely or on temporary work-from-home arrangements,
                        are required to return all issued equipment within 72 hours.
                    </li>
                    <li>
                        Following resignation, all issued equipment must be returned within 24 hours.
                    </li>
                </ul>
            </div>

            <div>
                <h3 class="font-semibold text-amber-600 mb-2">Condition of Returned Equipment:</h3>
                <p>
                    I acknowledge that all equipment must be returned in proper working order. Any damage to or
                    malfunction of the equipment beyond normal wear and tear may result in financial responsibility
                    on my part.
                </p>
            </div>

            <div>
                <h3 class="font-semibold text-amber-600 mb-2">Business Use Only:</h3>
                <p>
                    I understand and agree that any equipment provided by the Company is to be used solely for
                    business purposes and shall not be used for personal activities or non-work-related endeavors.
                </p>
            </div>

            <div>
                <h3 class="font-semibold text-amber-600 mb-2">Consequences of Non-Compliance:</h3>
                <p>
                    Failure to return any equipment supplied by the Company after the termination of my employment
                    may be considered theft and may result in criminal prosecution by the Company. Additionally, I
                    acknowledge that failure to comply with the terms of this agreement may lead to disciplinary
                    action, including potential legal consequences.
                </p>
            </div>

            <div>
                <h3 class="font-semibold text-amber-600 mb-2">Termination Conditions:</h3>
                <p>
                    The terms of this agreement apply regardless of the circumstances of termination, including
                    resignation, termination for cause, or termination without cause.
                </p>
            </div>
        </div>

        <!-- Equipment Table -->
        <div class="mt-8 overflow-x-auto rounded-lg border border-gray-200 shadow-sm">
            <table class="w-full">
                <thead>
                    <tr class="bg-black text-white">
                        <th colspan="5" class="px-6 py-4 text-left font-semibold">
                            <?= !empty($PCName) ? htmlspecialchars($PCName) : 'No System Specified' ?>
                        </th>
                    </tr>
                    <tr class="bg-gray-900 text-gray-100">
                        <th class="px-6 py-3 text-sm font-medium">Part ID</th>
                        <th class="px-6 py-3 text-sm font-medium">Type</th>
                        <th class="px-6 py-3 text-sm font-medium">Brand</th>
                        <th class="px-6 py-3 text-sm font-medium">Model</th>
                        <th class="px-6 py-3 text-sm font-medium">Serial</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php if (!empty($parts)): ?>
                        <?php foreach ($parts as $part): ?>
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 font-mono font-semibold text-amber-700 bg-amber-50 uppercase">
                                    <?= htmlspecialchars($part['uniqueID']) ?>
                                </td>
                                <td class="px-6 py-4"><?= htmlspecialchars($part['PartType']) ?></td>
                                <td class="px-6 py-4"><?= htmlspecialchars($part['Brand']) ?></td>
                                <td class="px-6 py-4"><?= htmlspecialchars($part['Model']) ?></td>
                                <td class="px-6 py-4 font-mono text-gray-600 break-all">
                                    <?= htmlspecialchars($part['SerialNumber']) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>

                        <!-- Accessories Section -->
                        <tr class="bg-amber-50">
                            <td colspan="5" class="px-6 py-3 border-t-2 border-amber-200">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-semibold text-amber-800">ACCESSORIES</span>
                                    <?php if (!empty($items)): ?>
                                        <div class="flex gap-4">
                                            <?php foreach ($items as $accessory): ?>
                                                <span
                                                    class="px-3 py-1 bg-white rounded-full text-sm shadow-sm border border-amber-200 flex items-center gap-3">
                                                    <?= htmlspecialchars($accessory['AccessoriesName'] . ' - ' . $accessory['PRNumber']) ?>
                                                    <form action="/accessories/delete" method="post">
                                                        <input type="hidden" name="Status" value="Returned">
                                                        <input type="hidden" name="EmployeeID" value="<?= $EmployeeID ?>">
                                                        <input type="hidden" name="AccessoriesID"
                                                            value="<?= $accessory['AccessoriesID'] ?>">
                                                        <input type="hidden" name="PRNumber" value="<?= $accessory['PRNumber'] ?>">
                                                        <input type="hidden" name="Brand" value="<?= $accessory['Brand'] ?>">
                                                        <input type="hidden" name="AccessoriesName"
                                                            value="<?= $accessory['AccessoriesName'] ?>">
                                                        <button type="submit" class="cursor-pointer">
                                                            <i class="fa-solid fa-circle-xmark text-red-500"></i>
                                                        </button>
                                                    </form>
                                                </span>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-sm text-amber-600 italic">No accessories assigned</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center text-gray-500 italic">
                                No equipment assigned for <?= htmlspecialchars($name) ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if (isset($_SESSION['login']) && $_SESSION['login'] === true): ?>

            <?php if (!empty($parts)): ?>
                <!-- Accessories Form -->
                <div class="mt-6 p-6 bg-gray-50 rounded-lg border border-gray-200">
                    <form action="/accessories/assign" method="post" id="assignedAccessories" class="space-y-4">

                        <?php if (empty($grouped)): ?>
                            <span class="italic text-gray-400">No accessories found. Please add accessories <a
                                    href='/accessories'>here</a> to continue.</span>
                        <?php else: ?>
                            <div class="flex md:flex-row flex-col gap-4 justify-center items-center text-center">
                                <?php foreach ($grouped as $accessoriesName => $items): ?>
                                    <select name="Accessories[<?= $accessoriesName ?>]"
                                        class="w-full px-4 py-2.5 border border-gray-200 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-transparen">
                                        <option value="">-- Select <?= $accessoriesName ?> --</option>
                                        <?php foreach ($items as $item): ?>
                                            <option value="<?= $item['PRNumber'] ?>">
                                                <?= $item['Brand'] . ' - ' . $item['PRNumber'] . ' (' . $item['Qty'] . ')' ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($EmployeeID)): ?>
                            <input type="hidden" name="EmployeeID" value="<?= htmlspecialchars($EmployeeID) ?>">
                        <?php else: ?>
                            <div class="bg-red-50 p-3 rounded-lg border border-red-200">
                                <p class="text-sm text-red-600">No Employee ID found - please refresh the page</p>
                            </div>
                        <?php endif; ?>
                        <button type="submit"
                            class="w-full bg-amber-600 text-white py-2.5 rounded-lg hover:bg-amber-700 transition-colors">
                            Update Accessories
                        </button>
                    </form>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <!-- Signature Section -->
        <div class="mt-8 grid grid-cols-1 md:grid-cols-2 gap-8">
            <div class="space-y-4">
                <div class="flex justify-between items-center text-sm">
                    <span>Date Released:</span>
                    <span class="font-semibold">
                        <?= !empty($assignedDate) ? htmlspecialchars($assignedDate) : 'N/A' ?>
                    </span>
                </div>
                <div class="flex justify-between items-center text-sm">
                    <span>Department:</span>
                    <span class="font-semibold">
                        <?= !empty($department) ? htmlspecialchars($department) : 'N/A' ?>
                    </span>
                </div>
            </div>

            <div class="space-y-4">
                <div class="flex justify-end gap-2">
                    <?php $statuses = ['NEW HIRE', 'WFH', 'TEMP WFH']; ?>
                    <?php foreach ($statuses as $s): ?>
                        <span class="px-3 py-1 text-sm rounded-full 
                                  <?= $s === $status ? 'bg-amber-500 text-white' : 'bg-gray-100' ?>">
                            <?= $s ?>
                        </span>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Signature Fields -->
        <div class="mt-8 grid grid-cols-1 md:grid-cols-2 gap-8 border-t border-gray-200 pt-8">
            <div class="text-center">
                <div class="h-24 flex flex-col justify-center border-gray-400 mb-4 px-2">
                    <?php if ($Signature !== null): ?>
                        <img src="<?= $Signature ?>" class="h-full w-full mx-auto object-contain rounded shadow"
                            alt="Signature">
                    <?php else: ?>
                        <form action="/employee/signature" method="post" id="signatureForm"
                            class="flex flex-col gap-2 justify-center items-center w-full" enctype="multipart/form-data">
                            <input type="hidden" name="EmployeeID" value="<?= htmlspecialchars($EmployeeID) ?>">
                            <input type="hidden" name="name" value="<?= htmlspecialchars($name) ?>">

                            <!-- Remove the nested structure and simplify the label -->
                            <div
                                class="w-full flex flex-col items-center justify-center border-2 border-dashed border-gray-400 rounded-lg p-2 hover:border-amber-500 transition-colors">
                                <img src="https://res.cloudinary.com/dfgrpa88v/image/upload/v1743643743/dlgarvgpfnmqrlp4arhn.png"
                                    class="previewImage max-h-20 object-contain mb-1 opacity-80 hover:opacity-100 transition"
                                    alt="Click to upload">
                                <span class="text-xs text-gray-500 px-2">Click to upload signature</span>
                                <input type="file" accept=".jpeg, .jpg, .png, .webp" id="signature" name="signature"
                                    class="hidden">
                            </div>

                            <button type="submit"
                                class="px-4 py-1.5 text-sm bg-black text-white rounded hover:opacity-80 w-full mt-1"
                                id="uploadBtn">Upload</button>
                        </form>
                    <?php endif; ?>
                </div>
                <p class="font-semibold border-t mt-8"><?= htmlspecialchars($name) ?></p>
                <p class="text-sm text-gray-500">Employee Signature</p>
            </div>

            <div class="text-center">
                <div class="h-24 mb-4">
                    <?php if (!empty($administrator)): ?>
                        <img src="<?= $administrator['signature'] ?>" class="h-full mx-auto">
                    <?php endif; ?>
                </div>
                <p class="font-semibold border-t mt-8">
                    <?= $administrator['name'] ?? 'No Administrator' ?>
                </p>
                <p class="text-sm text-gray-500">IT Personnel Signature</p>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", () => {
        // Print functionality
        document.getElementById("printBtn")?.addEventListener("click", () => window.print());

        // Signature preview
        const previewImage = () => {
            const fileInput = document.getElementById("signature");
            const previewImage = document.querySelector(".previewImage");
            if (!fileInput || !previewImage) return;

            previewImage.addEventListener("click", () => fileInput.click());

            fileInput.addEventListener("change", (event) => {
                const file = event.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = (e) => previewImage.src = e.target.result;
                    reader.readAsDataURL(file);
                }
            });
        };
        previewImage();
    });
</script>