<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI-Polyscope Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <header class="dashboard-header">
        <div class="container">
            <div>
                <h1>AI-Polyscope</h1>
                <div class="subtitle">Interactive Multi-Zoom Slide Processing System</div>
            </div>
            <div>
                <a href="#" id="refresh-btn" class="button button-small" title="Refresh">
                    <i class="fas fa-sync-alt"></i>
                </a>
                <a href="#" class="button button-small" title="Settings">
                    <i class="fas fa-cog"></i>
                </a>
            </div>
        </div>
    </header>
    
    <div class="container">
        <?php if (isset($flashMessage)): ?>
        <div class="alert alert-<?php echo $flashMessage['type']; ?>">
            <?php echo $flashMessage['message']; ?>
        </div>
        <?php endif; ?>