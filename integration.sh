#!/bin/bash
############################################
# === Use Case Example ===
############################################
# Usage:
# Arguments (2, no name):
#   Full path to the input slide (.svs file)
#   a number between 0-100
#
# Example usage:
#   ./trigger_aistil_polyscope.sh /../pilot_image_A.svs 90
############################################
# Functions, dont touch
############################################
parse_args() {
    if [[ $# -lt 2 ]]; then
        echo "Usage: $0 <SLIDEPATH> <Ki67>"
        echo "Error: Two arguments are required."
        return 1
    fi
    SLIDEPATH="$1"
    Ki67="$2"
    # Validate SLIDEPATH
    if [[ ! -f "$SLIDEPATH" ]]; then
        echo "Error: '$SLIDEPATH' is not a valid file."
        return 1
    fi
    if [[ "$SLIDEPATH" != *.svs ]]; then
        echo "Error: '$SLIDEPATH' does not have a .svs extension."
        return 1
    fi
    # Validate Ki67 is an integer between 0 and 100
    if ! [[ "$Ki67" =~ ^[0-9]+$ ]]; then
        echo "Error: Ki67 value '$Ki67' is not a valid integer."
        return 1
    fi
    if (( Ki67 < 0 || Ki67 > 100 )); then
        echo "Error: Ki67 value '$Ki67' is out of range (0-100)."
        return 1
    fi
    export SLIDEPATH Ki67
}

run_aistil() {
  local container="$1"
  local root_dir="$2"
  local slide_path="$3"
  local output_dir="$4"
  local ki67="$5"
  local pattern="*.svs"
  local step="all"
  input_dir=$(dirname "$slide_path")
  file_name=$(basename "$slide_path")
  echo ""
  echo "🚀 Running AI-sTIL on: $slide_path"
  echo ""
  docker run --rm \
    --gpus '"device=1"' \
    --shm-size=256G \
    --cpuset-cpus=0-31 \
    --user "$(id -u):$(id -g)" \
    -v "$root_dir":/root_dir \
    -v "$input_dir":/input_dir \
    -v "$output_dir":/output_dir \
    -w /root_dir/aiStil-gpu-repo/src \
    "$container" \
    /bin/bash -c "
      echo '---- check mounts inside the container ----'
      echo 'contents of /input_dir:'
      ls /input_dir
      echo 'checking /output_dir:'
      if [ -d /output_dir ]; then
          echo '✅ output directory (/output_dir) exists'
      else
          echo '⚠️ WARNING: output directory (/output_dir) does not exist!'
      fi
      echo '🔹 Step 1-5';
      /usr/bin/python3 /root_dir/aiStil-gpu-repo/src/main_aitil.py \
          -d /input_dir/$file_name -o /output_dir \
          -p \"$pattern\" --step \"$step\" --ki67 \"$ki67\" --stitched_tiff;
      echo '🔹 Step 6-7';
      /usr/bin/python3 /root_dir/aiStil-gpu-repo/src/main_tmeseg.py \
          -d /input_dir/$file_name -o /output_dir \
          -p \"$pattern\" --step \"$step\" --ki67 \"$ki67\" --stitched_tiff
    "
}

run_autoqc() {
  local container="$1"
  local root_dir="$2"
  local slide_path="$3"
  local output_dir="$4"
  input_dir=$(dirname "$slide_path")
  file_name=$(basename "$slide_path")
  echo "🚀 Running AutoQC on: $slide_path"
  docker run --rm \
    --user "$(id -u):$(id -g)" \
    --gpus '"device=1"' \
    --shm-size=256G \
    --cpuset-cpus=0-31 \
    -v "$root_dir":/root_dir \
    -v "$input_dir":/input_dir \
    -v "$output_dir":/output_dir \
    -w /root_dir/autoQC-repo/src \
    "$container" \
    python3 /root_dir/autoQC-repo/src/main.py \
      --slide_folder /input_dir/$file_name --output_dir /output_dir
}

run_polyscope() {
  local container="$1"
  local input_dir="$2"
  local output_dir="$3"
  local slide_name=$(basename "$input_dir")
  
  echo ""
  echo "🚀 Running Polyscope visualization on: $input_dir"
  echo ""
  
  # Create output directory if it doesn't exist
  mkdir -p "$output_dir"
  
  docker run --rm \
    --user "$(id -u):$(id -g)" \
    -v "$input_dir":/data/input \
    -v "$output_dir":/data/output \
    "$container" \
    process /data/input /data/output "$slide_name Analysis"
    
  echo ""
  echo "✅ Polyscope processing complete"
  
  # Check if multizoom was created (correct path with slide_name subdirectory)
  if [ -f "$output_dir/$slide_name/multizoom/index.html" ]; then
    echo "🔗 Results available at: $output_dir/$slide_name/multizoom/index.html"
  else
    echo "⚠️ Multizoom view not found. Check individual slide viewers at: $output_dir"
  fi
  
  echo ""
  echo "You can view the results by starting a web server in the output directory:"
  echo "cd $output_dir && python -m http.server 8000"
  echo "Then open a browser and navigate to: http://localhost:8000/$slide_name/multizoom/index.html"
  echo ""
}

############################################
# === Global Configuration, dont touch ===
############################################
AISTIL_CONTAINER=hpcharbor.mdanderson.edu/sranjbar/aistil:tf2.15-cuda12.2
AUTOQC_CONTAINER=hpcharbor.mdanderson.edu/sranjbar/grandqc:latest
POLYSCOPE_CONTAINER=hpcharbor.mdanderson.edu/polyscope-aistil/polyscope:latest
ROOT_DIR=/rsrch9/home/plm/idso_fa1_pathology/TIER1/aitil_clia
############################################
# === Set your output directory ===
OUTPUT_DIR="$ROOT_DIR/pchen6-test/aistil-web-outputs/"
POLYSCOPE_OUTPUT_DIR="$ROOT_DIR/pchen6-test/polyscope-outputs/"

# === Main Script ===
parse_args "$@"
echo "📄 Slide: $SLIDEPATH | Ki67: $Ki67"

# Create a processing directory for this slide
SLIDE_NAME=$(basename "$SLIDEPATH")
PROC_DIR="$OUTPUT_DIR/$SLIDE_NAME"
mkdir -p "$PROC_DIR"

# Run the pipeline
run_autoqc "$AUTOQC_CONTAINER" "$ROOT_DIR" "$SLIDEPATH" "$PROC_DIR"
run_aistil "$AISTIL_CONTAINER" "$ROOT_DIR" "$SLIDEPATH" "$PROC_DIR" "$Ki67"
run_polyscope "$POLYSCOPE_CONTAINER" "$PROC_DIR" "$POLYSCOPE_OUTPUT_DIR"