<!-- Sidebar Navigation -->
<div class="sidebar">
    <div class="sidebar-header">
        <h2>AI-Polyscope</h2>
    </div>
    <ul class="sidebar-menu">
        <li>
            <a href="index.php" <?php echo $view === 'dashboard' ? 'class="active"' : ''; ?>>
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
        </li>
        <li>
            <a href="index.php?view=slides" <?php echo $view === 'slides' ? 'class="active"' : ''; ?>>
                <i class="fas fa-microscope"></i> Slide Manager
            </a>
        </li>
        <li>
            <a href="index.php?view=multizoom" <?php echo $view === 'multizoom' ? 'class="active"' : ''; ?>>
                <i class="fas fa-layer-group"></i> Multi-Zoom Views
            </a>
        </li>
        <li>
            <a href="index.php?view=settings" <?php echo $view === 'settings' ? 'class="active"' : ''; ?>>
                <i class="fas fa-cog"></i> Settings
            </a>
        </li>
    </ul>
    
    <div class="sidebar-footer">
        <a href="https://github.com/yourusername/polyscope-AISTIL" target="_blank">
            <i class="fab fa-github"></i> GitHub
        </a>
        <span class="version">v1.0.0</span>
    </div>
</div>

<!-- Mobile Menu Toggle -->
<div class="mobile-toggle">
    <button class="menu-toggle">
        <i class="fas fa-bars"></i>
    </button>
</div>