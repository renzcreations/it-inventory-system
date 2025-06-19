<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="stylesheet" href="src/css/output.css" />
  <link rel="stylesheet" href="src/css/style.css" />
  <script src="src/js/script.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.9/dist/chart.umd.min.js"></script>
  <!-- Font Awesome Icons -->
  <script src="https://kit.fontawesome.com/e81967d7b9.js"></script>
  <title>404 - Page Not Found</title>
</head>

<body class="bg-gray-50">
  <div class="flex flex-col items-center justify-center px-4 py-8 lg:py-48">
    <div class="max-w-md text-center">
      <!-- Error Graphic -->
      <div class="mb-8 mx-auto w-48">
        <div class="bg-amber-500 text-white rounded-full w-24 h-24 flex items-center justify-center mx-auto mb-4">
          <span class="text-5xl font-bold">!</span>
        </div>
        <div class="border-t-4 border-black w-24 mx-auto"></div>
      </div>

      <!-- Error Content -->
      <div class="space-y-4">
        <h1 class="text-4xl font-bold text-gray-900 mb-2">404 Error</h1>
        <p class="text-lg text-gray-600">
          The page you're looking for can't be found
        </p>
        <p class="text-gray-500">
          It may have been moved or removed entirely
        </p>
      </div>

      <!-- Action Button -->
      <div class="mt-8">
        <a href="<?php echo (isset($_SESSION['login']) && $_SESSION['login'] === false) ? '/' : '/dashboard'; ?>"
          class="inline-flex items-center px-6 py-3 bg-gray-900 text-white rounded-lg hover:bg-gray-800 transition-colors duration-200 font-medium">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd"
              d="M10 18a8 8 0 100-16 8 8 0 000 16zm.707-10.293a1 1 0 00-1.414-1.414l-3 3a1 1 0 000 1.414l3 3a1 1 0 001.414-1.414L9.414 11H13a1 1 0 100-2H9.414l1.293-1.293z"
              clip-rule="evenodd" />
          </svg>
          Return Home
        </a>
      </div>
    </div>
  </div>
</body>

</html>