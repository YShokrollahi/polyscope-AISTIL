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
    echo "🔍 Processing slide directory: $INPUT_DIR"
    echo "🔍 Output directory: $OUTPUT_DIR"
    
    # Find the actual .svs file in the input directory
    SVS_FILE=$(find "$INPUT_DIR" -name "*.svs" -type f | head -1)
    
    if [ -z "$SVS_FILE" ]; then
        echo "❌ Error: No .svs file found in input directory!"
        exit 1
    fi
    
    echo "🔍 Found SVS file: $SVS_FILE"
    
    # Make sure the output directory exists
    mkdir -p "$OUTPUT_DIR"
    
    # Run the direct conversion
    cd /var/www/html
    echo "📝 Running DZI conversion on SVS file..."
    php -r "require_once 'src/core/dzi_generator.php'; \$result = convertToDZI('$SVS_FILE', '$OUTPUT_DIR'); var_dump(\$result);"
    
    # Also run conversions on the classification file and TME seg file if they exist
    TIFF_FILE=$(find "$INPUT_DIR" -name "*_classification_stitched.tif" | head -1)
    if [ -n "$TIFF_FILE" ]; then
        echo "📝 Running DZI conversion on classification file: $TIFF_FILE"
        php -r "require_once 'src/core/dzi_generator.php'; \$result = convertToDZI('$TIFF_FILE', '$OUTPUT_DIR'); var_dump(\$result);"
    fi
    
    TME_FILE=$(find "$INPUT_DIR" -path "*/6_tme_seg/mask_ss1_512_postprocessed/*_Ss1.png" | head -1)
    if [ -n "$TME_FILE" ]; then
        echo "📝 Running DZI conversion on TME segmentation file: $TME_FILE"
        php -r "require_once 'src/core/dzi_generator.php'; \$result = convertToDZI('$TME_FILE', '$OUTPUT_DIR'); var_dump(\$result);"
    fi
    
    QC_FILE=$(find "$INPUT_DIR" -path "*/0_autoqc/maps_qc/*_map_QC.png" | head -1)
    if [ -n "$QC_FILE" ]; then
        echo "📝 Running DZI conversion on QC map file: $QC_FILE"
        php -r "require_once 'src/core/dzi_generator.php'; \$result = convertToDZI('$QC_FILE', '$OUTPUT_DIR'); var_dump(\$result);"
    fi
    
    # Create multizoom view if we have multiple DZI files
    DZI_FILES=$(find "$OUTPUT_DIR" -name "*.dzi" | tr '\n' ' ')
    if [ -n "$DZI_FILES" ]; then
        echo "📝 Creating multizoom view from DZI files..."
        MULTIZOOM_DIR="$OUTPUT_DIR/multizoom"
        mkdir -p "$MULTIZOOM_DIR"
        
        php -r "require_once 'src/core/multizoom.php'; \$dziFiles = explode(' ', '$DZI_FILES'); \$dziFiles = array_filter(\$dziFiles); \$result = createMultizoom(\$dziFiles, '$MULTIZOOM_DIR', ['title' => '$SLIDE_TITLE']); var_dump(\$result);"
    fi
    
    echo "✅ Processing complete. Results saved to $OUTPUT_DIR"
    exit 0
fi

# If not in processing mode, start Apache for web interface
echo "🌐 Starting web server..."
exec apache2-foreground