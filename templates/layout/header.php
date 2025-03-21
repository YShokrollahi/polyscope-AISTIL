<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - AI-Polyscope</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/styles.css">
    <!-- Add Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
</head>
<body>
    <?php if ($flashMessage): ?>
    <div class="flash-message" id="flash-message">
        <div class="flash-content">
            <?php echo $flashMessage; ?>
            <button class="close-flash" onclick="document.getElementById('flash-message').style.display='none';">&times;</button>
        </div>
    </div>
    <script>
        // Auto-hide flash message after 5 seconds
        setTimeout(function() {
            var flashMsg = document.getElementById('flash-message');
            if (flashMsg) {
                flashMsg.style.display = 'none';
            }
        }, 5000);
    </script>
    <?php endif; ?>