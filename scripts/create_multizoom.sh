#!/bin/bash
# Script to create multizoom views from processed slides

# Get the directory where the script is located
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"

# Default paths
OUTPUT_DIR="${1:-$PROJECT_DIR/www/output}"
MULTIZOOM_DIR="${2:-$PROJECT_DIR/www/output/multizoom}"

# Check if the output directory exists
if [ ! -d "$OUTPUT_DIR" ]; then
    echo "Output directory does not exist: $OUTPUT_DIR"
    exit 1
fi

# Create the multizoom directory if it doesn't exist
if [ ! -d "$MULTIZOOM_DIR" ]; then
    mkdir -p "$MULTIZOOM_DIR"
fi

# Find all DZI files
echo "Looking for DZI files in $OUTPUT_DIR"
DZI_FILES=$(find "$OUTPUT_DIR" -type f -name "*_deepzoom.dzi" | sort)

if [ -z "$DZI_FILES" ]; then
    echo "No DZI files found in $OUTPUT_DIR"
    exit 1
fi

# Count the number of DZI files
DZI_COUNT=$(echo "$DZI_FILES" | wc -l | tr -d ' ')
echo "Found $DZI_COUNT DZI files"

# Create a name for the multizoom view
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
MULTIZOOM_NAME="multizoom_$TIMESTAMP"
MULTIZOOM_OUTPUT_DIR="$MULTIZOOM_DIR/$MULTIZOOM_NAME"

echo "Creating multizoom view: $MULTIZOOM_NAME"
echo "Output directory: $MULTIZOOM_OUTPUT_DIR"

# Create the multizoom directory
mkdir -p "$MULTIZOOM_OUTPUT_DIR"

# Create a file with DZI paths
DZI_LIST_FILE="$MULTIZOOM_OUTPUT_DIR/dzi_list.txt"
echo "$DZI_FILES" > "$DZI_LIST_FILE"

# Call the PHP script to create multizoom
php -r "
    require_once '$PROJECT_DIR/src/core/multizoom.php';
    \$dziFiles = file('$DZI_LIST_FILE', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    \$options = [
        'title' => '$MULTIZOOM_NAME',
        'syncViews' => true
    ];
    \$result = createMultizoom(\$dziFiles, '$MULTIZOOM_OUTPUT_DIR', \$options);
    echo 'Multizoom creation result: ';
    echo json_encode(\$result, JSON_PRETTY_PRINT);
"

# Check if creation was successful
if [ -f "$MULTIZOOM_OUTPUT_DIR/index.html" ]; then
    echo "Successfully created multizoom view at: $MULTIZOOM_OUTPUT_DIR/index.html"
else
    echo "Failed to create multizoom view"
fi

echo "Processing complete."