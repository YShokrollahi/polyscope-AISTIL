#!/bin/bash
# Process a slide group and create a multi-zoom view

# Check if input parameters are provided
if [ "$#" -lt 2 ]; then
    echo "Usage: $0 <input_folder> <output_dir> [multizoom_title]"
    exit 1
fi

INPUT_FOLDER="$1"
OUTPUT_DIR="$2"
MULTIZOOM_TITLE="${3:-Multi-Zoom View}"

# Check if input folder exists
if [ ! -d "$INPUT_FOLDER" ]; then
    echo "Error: Input folder '$INPUT_FOLDER' does not exist."
    exit 1
fi

# Create output directory if it doesn't exist
mkdir -p "$OUTPUT_DIR"

# Move to the root directory of the application
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
cd "$SCRIPT_DIR/.."

# Execute the PHP script
RESULT=$(php -r "require_once 'src/core/dzi_generator.php'; require_once 'src/core/multizoom.php'; require_once 'src/core/process_slide_group.php'; \$result = processSlideGroup('$INPUT_FOLDER', '$OUTPUT_DIR', ['multizoomTitle' => '$MULTIZOOM_TITLE']); echo \$result['fullUrl'];")

echo "Processing complete!"
echo "Multi-zoom URL: $RESULT"