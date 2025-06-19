<div class="px-4 py-6 lg:px-8 grid lg:grid-cols-2 gap-6">
    <!-- Invite Form Section -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6" data-aos="fade-right" data-aos-anchor-placement="top-right" data-aos-duration="2000">
        <div class="text-center mb-8">
            <img src="https://res.cloudinary.com/dfgrpa88v/image/upload/v1743643743/dlgarvgpfnmqrlp4arhn.png"
                class="w-32 mx-auto mb-6" alt="logo" loading="eager">
            <h2 class="text-2xl font-bold text-gray-900 mb-4">Invite New User</h2>
        </div>

        <form action="/users/invite" method="post" id="code" class="space-y-6">
            <div>
                <input type="email" name="email" id="email"
                    class="w-full px-4 py-2.5 border border-gray-200 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-transparent"
                    placeholder="username@example.com" required value="<?= $_SESSION['send_code_old_input']['email'] ?? '' ?>">
            </div>

            <button type="submit" name="register"
                class="w-full bg-amber-500 text-gray-900 py-2.5 px-6 rounded-lg hover:bg-amber-600 transition-colors duration-200 font-medium">
                Send Invitation
            </button>
        </form>
    </div>

    <!-- Registered Users Section -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6" data-aos="fade-left" data-aos-anchor-placement="top-left" data-aos-duration="2000">
        <h2 class="text-2xl font-bold text-gray-900 mb-6">Registered Users</h2>

        <div class="space-y-4">
            <?php if (!empty($users)): ?>
                <?php foreach ($users as $data): ?>
                    <div class="bg-gray-50 rounded-lg p-4 flex flex-col md:flex-row items-center gap-4">
                        <div class="flex-1">
                            <div class="flex lg:items-start items-center lg:justify-start justify-between lg:mb-1 mb-6">
                                <p class="font-medium text-gray-900"><?= htmlspecialchars($data['name']) ?></p>
                                <span class="ml-2 text-sm bg-amber-100 text-amber-800 px-2 py-1 rounded-full">
                                    <?= htmlspecialchars($data['username']) ?>
                                </span>
                            </div>
                            <p class="text-sm text-gray-600 break-all"><?= htmlspecialchars($data['email']) ?></p>
                        </div>

                        <form action="/users/update" method="post" class="w-full md:w-auto">
                            <input type="hidden" name="username" value="<?= htmlspecialchars($data['username']) ?>">

                            <select name="type" id="type"
                                class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-transparent"
                                onchange="this.form.submit()">
                                <option value="<?= htmlspecialchars($data['type']) ?>" selected>
                                    <?= htmlspecialchars($data['type']) ?>
                                </option>
                                <option
                                    value="<?= htmlspecialchars($data['type'] === 'Administrator' ? 'Support' : 'Administrator') ?>">
                                    <?= htmlspecialchars($data['type'] === 'Administrator' ? 'Support' : 'Administrator') ?>
                                </option>
                            </select>
                        </form>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="text-center p-6">
                    <p class="text-gray-500 italic">No registered users found</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Invited Users Section -->
<div class="px-4 lg:px-8 mt-6"  data-aos="fade-up" data-aos-anchor-placement="top-bottom" data-aos-duration="2000">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h2 class="text-2xl font-bold text-gray-900 mb-6">Pending Invitations</h2>

        <div class="space-y-4">
            <?php if (!empty($invited)): ?>
                <?php foreach ($invited as $item): ?>
                    <div class="bg-gray-50 rounded-lg p-4 flex flex-col md:flex-row items-center gap-4">
                        <div class="flex-1">
                            <p class="font-medium text-gray-900 break-all"><?= htmlspecialchars($item['email']) ?></p>
                            <p class="text-sm text-gray-600">
                                Invited <?= htmlspecialchars(date("M j, Y g:i A", strtotime($item['created_at']))) ?>
                            </p>
                        </div>

                        <div class="flex items-center gap-3 w-full md:w-auto">
                            <form action="/users/reinvite" method="post" class="flex items-center gap-2">
                                <input type="hidden" name="email" value="<?= $item['email'] ?>">

                                <span class="bg-amber-100 text-amber-800 px-3 py-1 rounded-lg text-sm break-all">
                                    <?= htmlspecialchars($item['email_code']) ?>
                                </span>
                                <button type="submit"
                                    class="text-amber-600 hover:text-amber-700 transition-colors duration-200">
                                    <i class="fa-solid fa-arrows-rotate"></i>
                                </button>
                            </form>

                            <form action="/users/remove" method="post">
                                <input type="hidden" name="email" value="<?= $item['email'] ?>">

                                <button type="submit" class="text-red-500 hover:text-red-600 transition-colors duration-200">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="text-center p-6">
                    <p class="text-gray-500 italic">No pending invitations</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", () => {
        // Add instant form submission for role select dropdowns
        document.querySelectorAll('select[name="type"]').forEach(select => {
            select.addEventListener('change', function () {
                this.form.submit();
            });
        });
    });
</script>