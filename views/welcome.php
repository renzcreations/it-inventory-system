<div class="flex items-center justify-center px-4 py-8 lg:py-25" data-aos="fade-up" data-aos-anchor-placement="top-bottom" data-aos-duration="1000">
    <div class="w-full max-w-md bg-white rounded-xl shadow-sm border border-gray-100 p-8">
        <!-- Header Section -->
        <div class="mb-8 text-center">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Employee Access</h1>
            <p class="text-sm text-gray-600">Verify your employment details</p>
        </div>

        <!-- Access Form -->
        <form action="/employee/login" method="post" id="employeeAccess" class="space-y-6">

            <div class="space-y-2">
                <label class="text-sm font-medium text-gray-600">Email Address</label>
                <input type="email" name="email" id="email"
                    class="w-full px-4 py-2.5 border border-gray-200 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-transparent"
                    placeholder="name@email.com"  value="<?= $_SESSION['guest_old_input']['email'] ?? '' ?>">
            </div>

            <div class="space-y-2">
                <label class="text-sm font-medium text-gray-600">Employee ID</label>
                <input type="number" name="EmployeeID" id="EmployeeID"
                    class="w-full px-4 py-2.5 border border-gray-200 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-transparent"
                    placeholder="Enter your employee ID" >
            </div>

            <button type="submit"
                class="w-full bg-amber-500 text-gray-900 py-2.5 px-6 rounded-lg hover:bg-amber-600 transition-colors duration-200 font-medium shadow-sm hover:shadow-md">
                Verify Access
            </button>
        </form>
    </div>
</div>