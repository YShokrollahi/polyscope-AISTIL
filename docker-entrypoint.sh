#!/bin/bash
set -e

# If the first argument is "bash", "sh" or starts with a slash, run it directly
if [ "$1" = "bash" ] || [ "$1" = "sh" ] || [ "${1:0:1}" = "/" ]; then
  exec "$@"
fi

# Define default values
MODE=${1:-"web"}
INPUT_DIR=${2:-"/data/input"}
OUTPUT_DIR=${3:-"/data/output"}
SLIDE_TITLE=${4:-"Slide Analysis"}

# Check if running in processing mode
if [ "$MODE" = "process" ]; then
  echo "🔍 Processing slide source: $INPUT_DIR"
  echo "🔍 Output directory: $OUTPUT_DIR"
  
  # Clear any previous debug logs
  rm -f /tmp/debug.log
  
  # Find the SVS file within the input directory
  echo "🔍 Searching for SVS files in $INPUT_DIR..."
  SVS_FILE=$(find "$INPUT_DIR" -name "*.svs" -type f | head -1)
  
  if [ -z "$SVS_FILE" ]; then
    echo "❌ Error: No .svs file found in input directory!"
    exit 1
  fi
  
  echo "🔍 Found SVS file: $SVS_FILE"
  SVS_FILENAME=$(basename "$SVS_FILE")
  SVS_BASENAME="${SVS_FILENAME%.svs}"
  SVS_DIR=$(dirname "$SVS_FILE")
  
  echo "🔍 SVS Basename: $SVS_BASENAME"
  echo "🔍 SVS Directory: $SVS_DIR"
  
  # Debug: List contents of key directories
  echo "📂 Input directory contents:"
  ls -la "$INPUT_DIR"
  
  # Verify we have the directory structure
  echo "🔍 Checking for required subdirectories..."
  
  # Three key directories we need
  QC_DIR=""
  TME_DIR=""
  CLASS_DIR=""
  
  # Find the 0_autoqc directory
  QC_DIRS=$(find "$INPUT_DIR" -type d -name "0_autoqc" | head -1)
  if [ -n "$QC_DIRS" ]; then
    QC_DIR="$QC_DIRS"
    echo "✅ Found QC directory: $QC_DIR"
  else
    echo "❌ Missing QC directory (0_autoqc)"
  fi
  
  # Find the 6_tme_seg directory
  TME_DIRS=$(find "$INPUT_DIR" -type d -name "6_tme_seg" | head -1)
  if [ -n "$TME_DIRS" ]; then
    TME_DIR="$TME_DIRS"
    echo "✅ Found TME directory: $TME_DIR"
  else
    echo "❌ Missing TME directory (6_tme_seg)"
  fi
  
  # Find the 4_5_stitch_output_segformer directory
  CLASS_DIRS=$(find "$INPUT_DIR" -type d -name "4_5_stitch_output_segformer" | head -1)
  if [ -n "$CLASS_DIRS" ]; then
    CLASS_DIR="$CLASS_DIRS"
    echo "✅ Found classification directory: $CLASS_DIR"
  else
    echo "❌ Missing classification directory (4_5_stitch_output_segformer)"
  fi
  
  # Create multiple file pattern possibilities based on SVS basename
  # For pilot_image_A.svs, we could have:
  # - pilot_image_A_map_QC.png
  # - pilot_image_A.svs_map_QC.png
  
  # Set up file search patterns
  QC_PATTERNS=(
    "${SVS_BASENAME}_map_QC.png"
    "${SVS_BASENAME}.svs_map_QC.png"
  )
  
  TME_PATTERNS=(
    "${SVS_BASENAME}_Ss1.png"
    "${SVS_BASENAME}.svs_Ss1.png"
  )
  
  CLASS_PATTERNS=(
    "${SVS_BASENAME}_classification_stitched.tif"
    "${SVS_BASENAME}.svs_classification_stitched.tif"
  )
  
  # Find all relevant files
  echo "🔍 Searching for specific files..."
  
  # Search for QC file
  QC_FILE=""
  if [ -n "$QC_DIR" ]; then
    for pattern in "${QC_PATTERNS[@]}"; do
      FOUND_FILES=$(find "$QC_DIR" -name "$pattern" -type f)
      if [ -n "$FOUND_FILES" ]; then
        QC_FILE=$(echo "$FOUND_FILES" | head -1)
        echo "✅ Found QC file: $QC_FILE"
        break
      fi
    done
  fi
  
  if [ -z "$QC_FILE" ]; then
    # Try a more general search if not found in the expected location
    for pattern in "${QC_PATTERNS[@]}"; do
      FOUND_FILES=$(find "$INPUT_DIR" -name "$pattern" -type f)
      if [ -n "$FOUND_FILES" ]; then
        QC_FILE=$(echo "$FOUND_FILES" | head -1)
        echo "✅ Found QC file through deep search: $QC_FILE"
        break
      fi
    done
  fi
  
  if [ -z "$QC_FILE" ]; then
    echo "❌ Could not find QC file with patterns: ${QC_PATTERNS[*]}"
  fi
  
  # Search for TME file
  TME_FILE=""
  if [ -n "$TME_DIR" ]; then
    for pattern in "${TME_PATTERNS[@]}"; do
      FOUND_FILES=$(find "$TME_DIR" -name "$pattern" -type f)
      if [ -n "$FOUND_FILES" ]; then
        TME_FILE=$(echo "$FOUND_FILES" | head -1)
        echo "✅ Found TME file: $TME_FILE"
        break
      fi
    done
  fi
  
  if [ -z "$TME_FILE" ]; then
    # Try a more general search if not found in the expected location
    for pattern in "${TME_PATTERNS[@]}"; do
      FOUND_FILES=$(find "$INPUT_DIR" -name "$pattern" -type f)
      if [ -n "$FOUND_FILES" ]; then
        TME_FILE=$(echo "$FOUND_FILES" | head -1)
        echo "✅ Found TME file through deep search: $TME_FILE"
        break
      fi
    done
  fi
  
  if [ -z "$TME_FILE" ]; then
    echo "❌ Could not find TME file with patterns: ${TME_PATTERNS[*]}"
  fi
  
  # Search for Classification file
  CLASS_FILE=""
  if [ -n "$CLASS_DIR" ]; then
    for pattern in "${CLASS_PATTERNS[@]}"; do
      FOUND_FILES=$(find "$CLASS_DIR" -name "$pattern" -type f)
      if [ -n "$FOUND_FILES" ]; then
        CLASS_FILE=$(echo "$FOUND_FILES" | head -1)
        echo "✅ Found Classification file: $CLASS_FILE"
        break
      fi
    done
  fi
  
  if [ -z "$CLASS_FILE" ]; then
    # Try a more general search if not found in the expected location
    for pattern in "${CLASS_PATTERNS[@]}"; do
      FOUND_FILES=$(find "$INPUT_DIR" -name "$pattern" -type f)
      if [ -n "$FOUND_FILES" ]; then
        CLASS_FILE=$(echo "$FOUND_FILES" | head -1)
        echo "✅ Found Classification file through deep search: $CLASS_FILE"
        break
      fi
    done
  fi
  
  if [ -z "$CLASS_FILE" ]; then
    echo "❌ Could not find Classification file with patterns: ${CLASS_PATTERNS[*]}"
  fi
  
  # Make sure the output directory exists
  mkdir -p "$OUTPUT_DIR"
  
  # Run the processing script
  cd /var/www/html
  echo "📝 Running slide group processing with SVS file: $SVS_FILE"
  
  # Create a temporary PHP script to pass all our paths
  cat > /tmp/process_with_paths.php <<EOL
<?php
require_once 'src/core/dzi_generator.php';
require_once 'src/core/multizoom.php';
require_once 'src/core/process_slide_group.php';

// Path information from bash script
\$svsFile = '$SVS_FILE';
\$svsBasename = '$SVS_BASENAME';
\$qcFile = '$QC_FILE';
\$tmeFile = '$TME_FILE';
\$classFile = '$CLASS_FILE';
\$parentDir = '$INPUT_DIR';
\$outputDir = '$OUTPUT_DIR';
\$slideTitle = '$SLIDE_TITLE';

// Create files array with only files that exist
\$filesToProcess = [];

if (file_exists(\$svsFile)) {
    \$filesToProcess[] = [
        'path' => \$svsFile,
        'title' => 'Raw Image (' . \$svsBasename . ')'
    ];
}

if (file_exists(\$qcFile)) {
    \$filesToProcess[] = [
        'path' => \$qcFile,
        'title' => 'QutoQC'
    ];
}

if (file_exists(\$tmeFile)) {
    \$filesToProcess[] = [
        'path' => \$tmeFile,
        'title' => 'TMESeg'
    ];
}

if (file_exists(\$classFile)) {
    \$filesToProcess[] = [
        'path' => \$classFile,
        'title' => 'Cell Classification'
    ];
}

// Custom function to process with explicit file paths
function processWithExplicitPaths(\$svsFile, \$svsBasename, \$filesToProcess, \$outputDir, \$options) {
    // Use the SVS basename for the output directory
    \$workingDir = \$outputDir . '/' . \$svsBasename;
    if (!file_exists(\$workingDir)) {
        mkdir(\$workingDir, 0755, true);
    }
    
    // Start logging
    \$logFile = \$workingDir . '/process.log';
    file_put_contents(\$logFile, "Processing with explicit file paths\n");
    file_put_contents(\$logFile, "Started: " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);
    file_put_contents(\$logFile, "SVS File: \$svsFile\n", FILE_APPEND);
    file_put_contents(\$logFile, "Output directory: \$workingDir\n", FILE_APPEND);
    
    // Debug log all file paths to verify they're correct
    file_put_contents(\$logFile, "Files to process:\n", FILE_APPEND);
    foreach (\$filesToProcess as \$index => \$fileInfo) {
        file_put_contents(\$logFile, "File \$index: {\$fileInfo['path']} (exists: " . (file_exists(\$fileInfo['path']) ? 'yes' : 'no') . ")\n", FILE_APPEND);
    }
    
    // Process each file
    \$dziFiles = [];
    \$processedFiles = [];
    
    foreach (\$filesToProcess as \$fileInfo) {
        \$filePath = \$fileInfo['path'];
        \$fileTitle = \$fileInfo['title'];
        
        // Check if file exists
        if (!file_exists(\$filePath)) {
            file_put_contents(\$logFile, "Warning: File not found: \$filePath\n", FILE_APPEND);
            continue;
        }
        
        // Process the file
        file_put_contents(\$logFile, "Processing file: \$filePath\n", FILE_APPEND);
        
        // Handle TIF files specially - some systems may have issues with them
        \$convertOptions = [
            'tileSize' => \$options['tileSize'] ?? 254,
            'overlap' => \$options['overlap'] ?? 1,
            'quality' => \$options['quality'] ?? 90,
            'debug' => true // Enable debug for all files to help troubleshoot
        ];
        
        \$result = convertToDZI(\$filePath, \$workingDir, \$convertOptions);
        
        if (\$result['status'] === 'success') {
            \$dziFiles[] = \$result['dziPath'];
            \$processedFiles[] = [
                'title' => \$fileTitle,
                'dziPath' => \$result['dziPath'],
                'viewerPath' => \$result['viewerPath']
            ];
            file_put_contents(\$logFile, "Successfully processed: \$filePath\n", FILE_APPEND);
        } else {
            file_put_contents(\$logFile, "Failed to process: \$filePath\n", FILE_APPEND);
            file_put_contents(\$logFile, "Error: " . (\$result['error'] ?? 'Unknown error') . "\n", FILE_APPEND);
        }
    }
    
    // Create the multizoom view if we have processed files
    if (count(\$dziFiles) > 0) {
        // Create multizoom directory
        \$multizoomDir = \$workingDir . '/multizoom';
        if (!file_exists(\$multizoomDir)) {
            mkdir(\$multizoomDir, 0755, true);
        }
        
        // Create the multizoom view
        \$multizoomTitle = \$options['multizoomTitle'] ?? \$svsBasename . ' - Slide Analysis';
        \$multizoomResult = createMultizoom(\$dziFiles, \$multizoomDir, [
            'title' => \$multizoomTitle,
            'syncViews' => true
        ]);
        
        // Generate the access URL
        \$multizoomUrl = "output/\$svsBasename/multizoom/index.html";
        \$fullUrl = "http://" . (\$_SERVER['HTTP_HOST'] ?? 'localhost:8000') . "/\$multizoomUrl";
        
        // Save result information
        \$resultInfo = [
            'slideName' => \$svsBasename,
            'processedFiles' => \$processedFiles,
            'multizoomPath' => \$multizoomResult['htmlPath'],
            'multizoomUrl' => \$multizoomUrl,
            'fullUrl' => \$fullUrl,
            'completedAt' => date('Y-m-d H:i:s')
        ];
        
        file_put_contents(\$workingDir . '/result.json', json_encode(\$resultInfo, JSON_PRETTY_PRINT));
        file_put_contents(\$logFile, "Multizoom created successfully at: \$multizoomDir\n", FILE_APPEND);
        file_put_contents(\$logFile, "Access URL: \$fullUrl\n", FILE_APPEND);
        
        return \$resultInfo;
    } else {
        \$errorMsg = "No files were successfully processed to DZI format.";
        file_put_contents(\$logFile, "Error: \$errorMsg\n", FILE_APPEND);
        
        return [
            'status' => 'error',
            'error' => \$errorMsg,
            'slideName' => \$svsBasename,
            'completedAt' => date('Y-m-d H:i:s')
        ];
    }
}

// Process using the detected paths with explicit file handling
\$result = processWithExplicitPaths(
    \$svsFile, 
    \$svsBasename, 
    \$filesToProcess,
    \$outputDir, 
    [
        'multizoomTitle' => \$slideTitle,
        'tileSize' => 254,
        'overlap' => 1,
        'quality' => 90
    ]
);

print_r(\$result);
EOL
  
  # Execute the custom PHP script
  RESULT=$(php /tmp/process_with_paths.php)
  
  echo "PHP Script Result:"
  echo "$RESULT"
  
  # Extract the output folder name from the results
  OUTPUT_BASENAME=$(echo "$RESULT" | grep -o 'slideName] => [^)]*' | cut -d'>' -f2 | tr -d ' ')
  echo "📝 Output basename: $OUTPUT_BASENAME"
  
  # Debug: List generated files in output directory
  echo "📂 Generated files in output directory:"
  find "$OUTPUT_DIR" -type f | head -50  # Limit to 50 files for readability
  
  # Ensure JavaScript files are present in the multizoom directory
  MULTIZOOM_DIR="$OUTPUT_DIR/$OUTPUT_BASENAME/multizoom"
  JS_DIR="$MULTIZOOM_DIR/js"
  
  echo "📝 Using multizoom directory: $MULTIZOOM_DIR"
  
  # Create js directory if it doesn't exist
  mkdir -p "$JS_DIR/images"
  
  # Copy OpenSeadragon files with error checking
  echo "📝 Copying OpenSeadragon JavaScript files..."
  if [ -f "/var/www/html/templates/js/openseadragon.min.js" ]; then
    cp -f "/var/www/html/templates/js/openseadragon.min.js" "$JS_DIR/"
    echo "✅ Copied OpenSeadragon JavaScript from templates"
  else
    echo "⚠️ Warning: OpenSeadragon JS not found in templates"
    # Download it as a fallback
    curl -L -o "$JS_DIR/openseadragon.min.js" \
      "https://cdn.jsdelivr.net/npm/openseadragon@3.0.0/build/openseadragon/openseadragon.min.js"
    echo "✅ Downloaded OpenSeadragon JavaScript from CDN"
  fi
  
  # Copy image files with error checking
  if [ -d "/var/www/html/templates/js/images" ] && [ "$(ls -A /var/www/html/templates/js/images)" ]; then
    cp -f /var/www/html/templates/js/images/* "$JS_DIR/images/" 2>/dev/null || true
    echo "✅ Copied OpenSeadragon images from templates"
  else
    echo "⚠️ Warning: OpenSeadragon images not found in templates"
    # Download basic images as fallback
    for img in home.png fullpage.png zoomin.png zoomout.png; do
      curl -L -o "$JS_DIR/images/$img" \
        "https://raw.githubusercontent.com/openseadragon/openseadragon/master/images/$img"
    done
    echo "✅ Downloaded OpenSeadragon images from GitHub"
  fi
  
  # Verify files were copied correctly
  if [ -f "$JS_DIR/openseadragon.min.js" ]; then
    echo "✅ OpenSeadragon JavaScript is available at: $JS_DIR/openseadragon.min.js"
  else
    echo "❌ ERROR: Failed to copy or download OpenSeadragon JavaScript"
  fi
  
  # Count image files to verify
  IMAGE_COUNT=$(ls -1 "$JS_DIR/images/" 2>/dev/null | wc -l)
  echo "✅ $IMAGE_COUNT OpenSeadragon image files available in $JS_DIR/images/"
  
  # Check for DZI files generated
  DZI_COUNT=$(find "$OUTPUT_DIR" -name "*.dzi" 2>/dev/null | wc -l)
  echo "📊 Found $DZI_COUNT DZI files in output directory"
  
  # Look for process.log file for debugging
  PROCESS_LOG="$OUTPUT_DIR/$OUTPUT_BASENAME/process.log"
  if [ -f "$PROCESS_LOG" ]; then
    echo "📜 Contents of process.log:"
    cat "$PROCESS_LOG"
  else
    echo "❌ Process log not found: $PROCESS_LOG"
    
    # Search for any process.log
    FOUND_LOGS=$(find "$OUTPUT_DIR" -name "process.log")
    if [ -n "$FOUND_LOGS" ]; then
      echo "📜 Found process logs in other locations:"
      echo "$FOUND_LOGS"
      
      # Show contents of first found log
      FIRST_LOG=$(echo "$FOUND_LOGS" | head -1)
      echo "Contents of $FIRST_LOG:"
      cat "$FIRST_LOG"
    fi
  fi
  
  # If debug.log exists, show its contents
  if [ -f "/tmp/debug.log" ]; then
    echo "📜 Contents of debug log:"
    cat "/tmp/debug.log"
  fi
  
  # Output success message with the correct URL
  echo "Processing complete!"
  echo "Multi-zoom URL: http://localhost:8000/output/$OUTPUT_BASENAME/multizoom/index.html"
  echo "✅ Processing complete. Results saved to $OUTPUT_DIR"
  exit 0
fi

# If not in processing mode, start Apache for web interface
echo "🌐 Starting web server..."
exec apache2-foreground