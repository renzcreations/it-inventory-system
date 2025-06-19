<div class="flex items-center justify-center px-4 py-8 lg:py-25">
    <div class="w-full max-w-md bg-white rounded-xl shadow-sm border border-gray-100 p-8">
        <!-- Header Section -->
        <div class="mb-8 text-center">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Admin Registration</h1>
            <p class="text-sm text-gray-600 italic flex items-center justify-center gap-1">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-amber-500" viewBox="0 0 20 20"
                    fill="currentColor">
                    <path fill-rule="evenodd"
                        d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                        clip-rule="evenodd" />
                </svg>
                IT Administrators only
            </p>
        </div>
        <!-- Registration Form -->
        <form action="/user-register" method="post" id="registerAdmin" class="space-y-6">
            <input type="hidden" name="email_code" value="<?= $_SESSION['code'] ?>">
            <div class="space-y-2">
                <label class="text-sm font-medium text-gray-600">Full Name</label>
                <input type="text" name="name" id="name"
                    class="w-full px-4 py-2.5 border border-gray-200 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-transparent"
                    placeholder="Last Name, First Name" value="<?= $_SESSION['register_old_input']['name'] ?? '' ?>">
            </div>

            <div class="space-y-2">
                <label class="text-sm font-medium text-gray-600">Email Address</label>
                <input type="email" name="email" id="email"
                    class="w-full px-4 py-2.5 border border-gray-200 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-transparent"
                    placeholder="username@example.com" value="<?= $_SESSION['register_old_input']['email'] ?? '' ?>">
            </div>

            <div class="space-y-2">
                <label class="text-sm font-medium text-gray-600">Username</label>
                <input type="text" name="username" id="username"
                    class="w-full px-4 py-2.5 border border-gray-200 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-transparent"
                    placeholder="Enter username" value="<?= $_SESSION['register_old_input']['username'] ?? '' ?>">
            </div>

            <div class="space-y-2">
                <label class="text-sm font-medium text-gray-600">Password</label>
                <input type="password" name="password" id="password"
                    class="w-full px-4 py-2.5 border border-gray-200 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-transparent"
                    placeholder="••••••••">
            </div>

            <div class="space-y-2">
                <label class="text-sm font-medium text-gray-600">Confirm Password</label>
                <input type="password" name="confirmPassword" id="confirmPassword"
                    class="w-full px-4 py-2.5 border border-gray-200 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-transparent"
                    placeholder="••••••••">
            </div>

            <button type="submit"
                class="w-full bg-amber-500 text-gray-900 py-2.5 px-6 rounded-lg hover:bg-amber-600 transition-colors duration-200 font-medium shadow-sm hover:shadow-md">
                Create Admin Account
            </button>
        </form>
    </div>
</div>