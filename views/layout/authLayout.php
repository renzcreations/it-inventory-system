<!DOCTYPE html>
<html lang="en" class="h-full bg-stone-200">

<head>
    <title><?= $_ENV['APP_NAME'] ?> | <?= $title ?? '' ?></title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon"
        href="https://res.cloudinary.com/dfgrpa88v/image/upload/v1743643743/dsddvcdwxbhfrxuqaz1v.png"
        type="image/x-icon">
    <link rel="stylesheet" href="/src/css/output.css">
    <link rel="stylesheet" href="/src/css/style.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <script src="/src/js/script.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.9/dist/chart.umd.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <!-- Font Awesome Icons -->
    <script src="https://kit.fontawesome.com/e81967d7b9.js"></script>
</head>

<body>
    <header class="header">
        <h1 class="sr-only">HPL IT Inventory</h1>
        <div class="header_content overflow-hidden">
            <a href="/" class="logo flex text-white gap-2 items-center">
                <img src="https://res.cloudinary.com/dfgrpa88v/image/upload/v1743643743/dsddvcdwxbhfrxuqaz1v.png"
                    alt="HPL IT Inventory" class="filter invert size-20" />
                <span class="lg:text-lg text-xs">HPL IT Inventory</span>
            </a>
            <nav class="nav">
                <ul class="nav_list">
                    <li class="nav_item">
                        <a href="/dashboard" class="nav_link">Dashboard</a>
                    </li>
                    <li class="nav_item">
                        <a href="/employee" class="nav_link">Employee</a>
                    </li>
                    <li class="nav_item">
                        <a href="/parts" class="nav_link">Parts</a>
                    </li>
                    <li class="nav_item">
                        <a href="/accessories" class="nav_link">Accessories</a>
                    </li>
                    <li class="nav_item">
                        <a href="/build" class="nav_link">Build</a>
                    </li>
                    <li class="nav_item">
                        <a href="/computer" class="nav_link">Computer</a>
                    </li>
                    <li class="nav_item">
                        <ul class="xl:block hidden">
                            <select name="user_menu" id="user_menu" class="px-4 py-2 border bg-gray-100">
                                <option class="bg-gray-400" disabled selected>
                                    <?= $_SESSION['name'] ?? 'Administrator' ?>
                                </option>
                                <option value="/profile">Profile</option>
                                <hr>
                                <option value="/logout" class="text-red-500">Logout</option>
                            </select>
                        </ul>
                        <hr class="xl:hidden block border-gray-100 w-[100vw] mb-5">
                        <ul class="xl:hidden flex flex-col gap-4 justify-center items-center">
                            <li><a href="/profile" class="nav_link">Profile</a></li>
                            <li><a href="/logout" class="nav_link !text-red-500">Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </nav>
            <div class="hamburger">
                <div class="bar"></div>
                <div class="bar"></div>
                <div class="bar"></div>
            </div>
        </div>
    </header>
    <main class="flex-1 min-h-screen">
        <?php require_once __DIR__ . '../../components/alert.php' ?>
        <?= $content ?>
    </main>

    <footer>
        <div class="bg-black text-gray-300 text-sm text-center p-2 ">
            @Copyright 2025 | HPL Game Design Corps. Inventory System
        </div>
    </footer>
    <script>
        document.getElementById('user_menu').addEventListener('change', function () {
            if (this.value) {
                window.location.href = this.value;
            }
        });
    </script>
</body>

</html>