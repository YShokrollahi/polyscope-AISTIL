<!DOCTYPE html>
<html>
<head>
    <title>AI-Polyscope Dashboard</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }
        .section {
            margin-bottom: 30px;
        }
        .card {
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 15px;
            margin-bottom: 15px;
            background-color: white;
        }
        .button {
            background-color: #4CAF50;
            border: none;
            color: white;
            padding: 10px 20px;
            text-align: center;
            text-decoration: none;
            display: inline-block;
            font-size: 16px;
            margin: 4px 2px;
            cursor: pointer;
            border-radius: 4px;
        }
        .file-list {
            list-style-type: none;
            padding: 0;
        }
        .file-item {
            padding: 10px;
            border-bottom: 1px solid #eee;
        }
        .file-item:hover {
            background-color: #f9f9f9;
        }
        .status-success {
            color: green;
        }
        .status-error {
            color: red;
        }
        .status-pending {
            color: orange;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>AI-Polyscope Dashboard</h1>
        
        <div class="section">
            <h2>Input Files</h2>
            <div class="card">
                <p>Select files to process:</p>
                <div class="file-list">
                    <?php
                    $inputDir = __DIR__ . '/../input';
                    $files = glob($inputDir . '/*.*');
                    
                    echo "<!-- Debug: Input dir = $inputDir -->";
                    echo "<!-- Debug: Current dir = " . getcwd() . " -->";
                    echo "<!-- Debug: Files found = " . count($files) . " -->";
                    
                    if (empty($files)) {
                        echo "<p>No files found in input directory. Please add files to the 'input' folder.</p>";
                        echo "<p>Looking in: " . realpath($inputDir) . "</p>";
                    } else {
                        echo "<ul class='file-list'>";
                        foreach ($files as $file) {
                            $filename = basename($file);
                            echo "<li class='file-item'>";
                            echo "<input type='checkbox' name='files[]' value='$file' id='$filename'>";
                            echo "<label for='$filename'> $filename</label>";
                            echo "</li>";
                        }
                        echo "</ul>";
                        echo "<button class='button' onclick='processSelected()'>Process Selected Files</button>";
                    }
                    ?>
                </div>
            </div>
        </div>
        
        <div class="section">
            <h2>Processed Slides</h2>
            <div class="card">
                <?php
                $outputDir = __DIR__ . '/output';
                $processedDirs = glob($outputDir . '/*', GLOB_ONLYDIR);
                
                echo "<!-- Debug: Output dir = $outputDir -->";
                echo "<!-- Debug: Processed dirs = " . count($processedDirs) . " -->";
                
                if (empty($processedDirs)) {
                    echo "<p>No processed slides found. Process slides to see them here.</p>";
                } else {
                    echo "<ul class='file-list'>";
                    foreach ($processedDirs as $dir) {
                        $dirname = basename($dir);
                        $statusFile = "$dir/status.json";
                        $status = "unknown";
                        $viewerPath = "";
                        
                        if (file_exists($statusFile)) {
                            $statusData = json_decode(file_get_contents($statusFile), true);
                            $status = $statusData['status'] ?? 'unknown';
                            $viewerPath = $statusData['viewerPath'] ?? '';
                        }
                        
                        $statusClass = '';
                        switch ($status) {
                            case 'success':
                                $statusClass = 'status-success';
                                break;
                            case 'error':
                                $statusClass = 'status-error';
                                break;
                            default:
                                $statusClass = 'status-pending';
                        }
                        
                        echo "<li class='file-item'>";
                        echo "$dirname - <span class='$statusClass'>$status</span>";
                        
                        if ($status == 'success' && !empty($viewerPath)) {
                            $relativeViewerPath = str_replace(__DIR__ . '/', '', $viewerPath);
                            echo " <a href='$relativeViewerPath' target='_blank'>View</a>";
                        }
                        
                        echo "</li>";
                    }
                    echo "</ul>";
                }
                ?>
                
                <?php if (!empty($processedDirs)): ?>
                <button class='button' onclick='createMultizoom()'>Create Multi-Zoom View</button>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="section">
            <h2>Multi-Zoom Views</h2>
            <div class="card">
                <?php
                $multizoomDir = __DIR__ . '/output/multizoom';
                
                echo "<!-- Debug: Multizoom dir = $multizoomDir -->";
                
                if (is_dir($multizoomDir)) {
                    $multizoomFiles = glob($multizoomDir . '/*.html');
                    
                    if (empty($multizoomFiles)) {
                        echo "<p>No multi-zoom views found. Create a multi-zoom view to see it here.</p>";
                    } else {
                        echo "<ul class='file-list'>";
                        foreach ($multizoomFiles as $file) {
                            $filename = basename($file);
                            $relativePath = str_replace(__DIR__ . '/', '', $file);
                            echo "<li class='file-item'>";
                            echo "<a href='$relativePath' target='_blank'>$filename</a>";
                            echo "</li>";
                        }
                        echo "</ul>";
                    }
                } else {
                    echo "<p>No multi-zoom views found. Create a multi-zoom view to see it here.</p>";
                }
                ?>
            </div>
        </div>
    </div>
    
    <script>
        function processSelected() {
            const checkboxes = document.querySelectorAll('input[name="files[]"]:checked');
            const files = Array.from(checkboxes).map(cb => cb.value);
            
            if (files.length === 0) {
                alert('Please select at least one file to process.');
                return;
            }
            
            if (confirm('Process ' + files.length + ' selected files?')) {
                // Show a processing message
                const processingDiv = document.createElement('div');
                processingDiv.id = 'processing-message';
                processingDiv.style.position = 'fixed';
                processingDiv.style.top = '50%';
                processingDiv.style.left = '50%';
                processingDiv.style.transform = 'translate(-50%, -50%)';
                processingDiv.style.padding = '20px';
                processingDiv.style.backgroundColor = 'rgba(0, 0, 0, 0.8)';
                processingDiv.style.color = 'white';
                processingDiv.style.borderRadius = '5px';
                processingDiv.style.zIndex = '1000';
                processingDiv.innerHTML = '<p>Processing files... Please wait.</p>';
                document.body.appendChild(processingDiv);
                
                // Create a form for POST submission
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'api/process.php';
                form.style.display = 'none';
                
                // Add files as input
                const filesInput = document.createElement('input');
                filesInput.type = 'hidden';
                filesInput.name = 'files';
                filesInput.value = JSON.stringify(files);
                form.appendChild(filesInput);
                
                // Add action as input
                const actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'action';
                actionInput.value = 'process';
                form.appendChild(actionInput);
                
                // Append form to body and submit
                document.body.appendChild(form);
                form.submit();
            }
        }

        function createMultizoom() {
            if (confirm('Create a multi-zoom view of all processed slides?')) {
                // Show a processing message
                const processingDiv = document.createElement('div');
                processingDiv.id = 'processing-message';
                processingDiv.style.position = 'fixed';
                processingDiv.style.top = '50%';
                processingDiv.style.left = '50%';
                processingDiv.style.transform = 'translate(-50%, -50%)';
                processingDiv.style.padding = '20px';
                processingDiv.style.backgroundColor = 'rgba(0, 0, 0, 0.8)';
                processingDiv.style.color = 'white';
                processingDiv.style.borderRadius = '5px';
                processingDiv.style.zIndex = '1000';
                processingDiv.innerHTML = '<p>Creating multi-zoom view... Please wait.</p>';
                document.body.appendChild(processingDiv);
                
                // Create a form for POST submission
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'api/process.php';
                form.style.display = 'none';
                
                // Add action as input
                const actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'action';
                actionInput.value = 'multizoom';
                form.appendChild(actionInput);
                
                // Append form to body and submit
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>
</html>