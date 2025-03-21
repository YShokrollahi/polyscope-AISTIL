#!/bin/bash
# Script to convert slides to DZI format

# Get the directory where the script is located
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"

# Default paths
INPUT_DIR="${1:-$PROJECT_DIR/input}"
OUTPUT_DIR="${2:-$PROJECT_DIR/www/output}"

# Check if the input directory exists
if [ ! -d "$INPUT_DIR" ]; then
    echo "Input directory does not exist: $INPUT_DIR"
    exit 1
fi

# Create the output directory if it doesn't exist
if [ ! -d "$OUTPUT_DIR" ]; then
    mkdir -p "$OUTPUT_DIR"
fi

# Find all image files
echo "Looking for image files in $INPUT_DIR"
IMAGE_FILES=$(find "$INPUT_DIR" -type f \( -name "*.svs" -o -name "*.tif" -o -name "*.tiff" -o -name "*.ndpi" -o -name "*.jpg" -o -name "*.png" \))

if [ -z "$IMAGE_FILES" ]; then
    echo "No image files found in $INPUT_DIR"
    exit 1
fi

# Process each file
echo "Found $(echo "$IMAGE_FILES" | wc -l | tr -d ' ') files to process"
for FILE in $IMAGE_FILES; do
    FILENAME=$(basename "$FILE")
    BASENAME=$(basename "$FILE" | cut -d. -f1)
    SLIDE_OUTPUT_DIR="$OUTPUT_DIR/$BASENAME"
    
    echo "Processing $FILENAME..."
    echo "Output directory: $SLIDE_OUTPUT_DIR"
    
    # Create the output directory for this slide
    mkdir -p "$SLIDE_OUTPUT_DIR"
    
    # Call the PHP script to process the file
    php -r "
        require_once '$PROJECT_DIR/src/core/dzi_generator.php';
        \$result = convertToDZI('$FILE', '$SLIDE_OUTPUT_DIR');
        echo 'Processing result: ';
        echo json_encode(\$result, JSON_PRETTY_PRINT);
    "
    
    # Check if processing was successful
    if [ -f "$SLIDE_OUTPUT_DIR/${BASENAME}_deepzoom.dzi" ]; then
        echo "Successfully generated DZI for $FILENAME"
    else
        echo "Failed to generate DZI for $FILENAME"
    fi
    
    echo -e "\n"
done

echo "Processing complete."