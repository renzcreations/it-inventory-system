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
                    <?php if (!isset($_SESSION['login']) || $_SESSION['login'] === false): ?>
                        <li class="nav_item">
                            <a href="/login" class="nav_link">Login</a>
                        </li>
                    <?php else: ?>
                        <li class="nav_item">
                            <a href="/dashboard" class="nav_link">Dashboard</a>
                        </li>
                    <?php endif; ?>
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
        <div class="flex flex-col items-center justify-center bg-gray-800 overflow-hidden">
            <!-- Banner -->
            <img src="https://res.cloudinary.com/dfgrpa88v/image/upload/v1743643743/fwxt0w4jy4f7xxdwvbln.jpg" alt=""
                class="w-full md:h-[50px] object-cover">
        </div>
        <?php require_once __DIR__ . '../../components/alert.php' ?>
        <?= $content ?>
    </main>
    <footer>
        <div class="bg-black text-gray-300 text-sm text-center p-2 ">
            @Copyright 2025 | HPL Game Design Corps. Inventory System
        </div>
    </footer>
</body>

</html>