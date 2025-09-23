<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($title) ? htmlspecialchars($title) : 'Unauthorized'; ?></title>
    <?php echo function_exists('vite_tags') ? vite_tags() : ''; ?>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto py-16 px-4">
        <div class="max-w-md mx-auto bg-white rounded-lg shadow-md overflow-hidden">
            <div class="py-8 px-6">
                <div class="text-center">
                    <h1 class="text-6xl font-bold text-yellow-600 mb-4">401</h1>
                    <h2 class="text-2xl font-semibold text-gray-800 mb-2">Unauthorized</h2>
                    <p class="text-gray-600 mb-6">
                        You must be logged in to access this page.
                    </p>
                    <div class="flex items-center justify-center gap-3">
                        <a href="/login" class="inline-block px-5 py-3 bg-blue-500 text-white font-medium rounded-md hover:bg-blue-600 transition">Login</a>
                        <a href="/" class="inline-block px-5 py-3 bg-gray-100 text-gray-800 font-medium rounded-md hover:bg-gray-200 transition">Home</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>