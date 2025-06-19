<?php if ($user['status'] === 'Main Admin' || $user['type'] === 'Administrator'): ?>
    <div class="py-6 px-4" data-aos="zoom-out-right">
        <a href="/users">
            <button
                class="bg-amber-500 px-6 py-2 rounded-lg font-medium text-gray-900 hover:bg-amber-600 transition-colors duration-200 lg:w-50 w-full">
                Invite Users
            </button>
        </a>
    </div>
<?php endif; ?>

<div class="px-4 lg:px-8 grid lg:grid-cols-2 gap-6 my-5">
    <!-- Profile Section -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6" data-aos="fade-right" data-aos-anchor-placement="top-right" data-aos-duration="2000">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold text-gray-900">Profile Settings</h2>
            <a href="/backup?manual=true">
                <button
                    class="bg-red-500 text-white px-4 py-2 rounded-lg hover:bg-red-600 transition-colors duration-200"
                    onclick="setTimeout(() => { window.location.href = '/profile'; }, 300);">
                    Backup Database
                </button>
            </a>
        </div>

        <form action="/profile/update" method="post" id="nameForm" class="space-y-6">

            <div>
                <label class="block text-sm font-medium text-gray-600 mb-2">Full Name</label>
                <input type="text" name="name" id="name" value="<?= $user['name'] ?>"
                    class="w-full px-4 py-2.5 border border-gray-200 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-transparent">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-600 mb-2">Username</label>
                <input type="text" name="username" id="username" value="<?= $user['username'] ?>"
                    class="w-full px-4 py-2.5 border border-gray-200 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-transparent">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-600 mb-2">Email Address</label>
                <input type="email" name="email" id="user_email" value="<?= $user['email'] ?>"
                    class="w-full px-4 py-2.5 border border-gray-200 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-transparent">
            </div>

            <button type="submit"
                class="w-full bg-gray-900 text-white py-2.5 px-6 rounded-lg hover:bg-gray-800 transition-colors duration-200">
                Save Changes
            </button>
        </form>

        <hr class="my-8 border-gray-100">

        <form action="/profile/password" method="post" id="passwordForm" class="space-y-6">
            <div>
                <label class="block text-sm font-medium text-gray-600 mb-2">Current Password</label>
                <input type="password" name="oldPassword" id="oldPassword"
                    class="w-full px-4 py-2.5 border border-gray-200 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-transparent">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-600 mb-2">New Password</label>
                <input type="password" name="password" id="password"
                    class="w-full px-4 py-2.5 border border-gray-200 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-transparent">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-600 mb-2">Confirm New Password</label>
                <input type="password" name="confirmPassword" id="confirmPassword"
                    class="w-full px-4 py-2.5 border border-gray-200 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-transparent">
            </div>

            <button type="submit"
                class="w-full bg-gray-900 text-white py-2.5 px-6 rounded-lg hover:bg-gray-800 transition-colors duration-200">
                Update Password
            </button>
        </form>
    </div>

    <!-- Company Profile & Signature Section -->
    <div class="space-y-6" data-aos="fade-left" data-aos-anchor-placement="top-left" data-aos-duration="2000">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6" >
            <h2 class="text-2xl font-bold text-gray-900 mb-6">Company Profile</h2>

            <?php if (!empty($data)): ?>
                <?php foreach ($data as $row): ?>
                    <form action="/company/update" method="post" id="companyForm" class="space-y-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-600 mb-2">Company Address</label>
                            <input type="text" name="address" id="address" value="<?= $row['address'] ?>"
                                class="w-full px-4 py-2.5 border border-gray-200 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-transparent">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-600 mb-2">Company Email</label>
                            <input type="email" name="email" id="email" value="<?= $row['email'] ?>"
                                class="w-full px-4 py-2.5 border border-gray-200 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-transparent">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-600 mb-2">Contact Number</label>
                            <input type="number" name="contact" id="contact" value="<?= $row['contact'] ?>"
                                class="w-full px-4 py-2.5 border border-gray-200 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-transparent"
                                maxlength="11">
                        </div>

                        <button type="submit"
                            class="w-full bg-gray-900 text-white py-2.5 px-6 rounded-lg hover:bg-gray-800 transition-colors duration-200">
                            Update Company Info
                        </button>
                    </form>
                <?php endforeach; ?>
            <?php else: ?>
                <form action="/company/add" method="post" id="companyForm" class="space-y-6">

                    <div>
                        <label class="block text-sm font-medium text-gray-600 mb-2">Company Address</label>
                        <input type="text" name="company_address" id="company_address"
                            class="w-full px-4 py-2.5 border border-gray-200 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-transparent"
                            value="<?= $_SESSION['company_old_input']['company_address'] ?? '' ?>">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-600 mb-2">Company Email</label>
                        <input type="email" name="company_email" id="company_email"
                            class="w-full px-4 py-2.5 border border-gray-200 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-transparent"
                            value="<?= $_SESSION['company_old_input']['company_email'] ?? '' ?>">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-600 mb-2">Contact Number</label>
                        <input type="number" name="company_contact" id="company_contact"
                            class="w-full px-4 py-2.5 border border-gray-200 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-transparent"
                            value="<?= $_SESSION['company_old_input']['company_contact'] ?? '' ?>" maxlength="11">
                    </div>

                    <button type="submit"
                        class="w-full bg-gray-900 text-white py-2.5 px-6 rounded-lg hover:bg-gray-800 transition-colors duration-200">
                        Save Company Info
                    </button>
                </form>
            <?php endif; ?>
        </div>

        <!-- Signature Upload -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">Digital Signature</h2>

            <form action="/profile/signature" method="post" id="signatureForm" class="space-y-6"
                enctype="multipart/form-data">
                <input type="hidden" name="username" value="<?= $user['username'] ?>">
                <input type="file" accept=".jpeg, .jpg, .png, .webp" id="signature" name="signature"
                    class="hidden signature-image">

                <div
                    class="border-2 border-dashed border-gray-200 rounded-xl p-6 text-center cursor-pointer hover:border-amber-500 transition-colors duration-200 signature-upload">
                    <?php if ($user['signature'] !== null): ?>
                        <img src="<?= $user['signature'] ?>" class="previewImage w-48 h-32 object-contain mx-auto">
                    <?php else: ?>
                        <img src="https://res.cloudinary.com/dfgrpa88v/image/upload/v1743643743/dlgarvgpfnmqrlp4arhn.png"
                            class="previewImage w-48 h-32 object-contain mx-auto">
                    <?php endif; ?>
                    <p class="text-sm text-gray-500 mt-4">Click to upload new signature</p>
                </div>

                <button type="submit"
                    class="w-full bg-gray-900 text-white py-2.5 px-6 rounded-lg hover:bg-gray-800 transition-colors duration-200">
                    Upload Signature
                </button>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", () => {
        previewImage();
        setupPhoneNumberValidation();
    });

    function previewImage() {
        const fileInput = document.getElementById("signature");
        const previewImage = document.querySelector(".previewImage");
        const uploadContainer = document.querySelector(".signature-upload");
        const defaultImage = "https://res.cloudinary.com/dfgrpa88v/image/upload/v1743643743/dlgarvgpfnmqrlp4arhn.png";

        if (!fileInput || !previewImage) return;

        // Handle container click
        uploadContainer.addEventListener("click", (e) => {
            e.preventDefault();
            fileInput.click();
        });

        // Handle file selection
        fileInput.addEventListener("change", (event) => {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    previewImage.src = e.target.result;
                    uploadContainer.classList.remove("border-dashed");
                    uploadContainer.classList.add("border-solid", "border-amber-500");
                };
                reader.readAsDataURL(file);
            } else {
                previewImage.src = defaultImage;
                uploadContainer.classList.add("border-dashed");
                uploadContainer.classList.remove("border-solid", "border-amber-500");
            }
        });
    }

    function setupPhoneNumberValidation() {
        const phoneInputs = document.querySelectorAll("#company_contact, #contact");

        phoneInputs.forEach(input => {
            input.addEventListener("input", (event) => {
                let value = event.target.value.replace(/\D/g, '');
                event.target.value = value.slice(0, 11);
            });
        });
    }
</script>