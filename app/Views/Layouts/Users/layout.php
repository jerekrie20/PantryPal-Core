<?php
// This layout file expects a $title variable for the page title,
// and a $content variable containing the main HTML content for the page.
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($title) ? htmlspecialchars($title) . ' · ' : '' ?>PantryPal</title>
    <?php
    if (!function_exists('vite_tags')) {
        function vite_tags() { /* Placeholder */ }
    }
    echo vite_tags();
    ?>
</head>
<body class="bg-bg-page text-text-base font-body antialiased">
<div class="min-h-screen flex flex-col">
    <?php require_once VIEW_PATH . '/Layouts/Users/header.php'; ?>

    <main class="container mx-auto p-4 sm:p-6 lg:p-8 flex-grow">
        <?php echo $content; ?>
    </main>

    <?php require_once VIEW_PATH . '/Layouts/Users/footer.php'; ?>
</div>

<?php
// Include AI Chat Widget on all authenticated pages
require_once VIEW_PATH . '/Components/ai_chat.php';
?>
</body>
</html>
