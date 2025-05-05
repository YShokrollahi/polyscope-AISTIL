# Polyscope-AISTIL

AI-Polyscope latest version
deploy the visulaization on t6

## Features

- Convert high-resolution pathology slides to DZI format  
- View slides with smooth zooming and panning  
- Create synchronized multi-view displays of multiple slides  
- Simple web-based dashboard  
- Command-line processing support  
- Compatible with SVS, TIFF, NDPI, and other common pathology slide formats
- Docker support for containerized deployment

## Requirements

- PHP 7.4 or higher  
- `vips` for DZI conversion  
- Modern web browser with JavaScript enabled  
- Docker (for containerized deployment)

## Installation

### Clone the repository:
```bash
git clone https://github.com/your-repo/polyscope-AISTIL.git
cd polyscope-AISTIL
```

### Download the OpenSeadragon library:
```bash
# Download OpenSeadragon (if required separately)
```

## Using Docker (Recommended)

Using Docker is the easiest way to run Polyscope-AISTIL without installing dependencies.

### Building the Docker Image

```bash
# Build the Docker image
docker build -t polyscope-aistil/polyscope:latest .

# If you encounter build cache issues, use --no-cache
docker build --no-cache -t polyscope-aistil/polyscope:latest .
```

### Running with Docker

```bash
# Process slides with Docker
docker run --rm \
  -v /path/to/input:/data/input:cached \
  -v /path/to/output:/data/output:delegated \
  polyscope-aistil/polyscope:latest \
  process /data/input/slide_folder /data/output "Slide Analysis"
```

Replace `/path/to/input` and `/path/to/output` with your actual paths. The slide folder should contain:
- The SVS file (e.g., `slide.svs`)
- A `0_autoqc/maps_qc` directory with QC maps (e.g., `slide.svs_map_QC.png`)
- A `6_tme_seg/mask_ss1_512_postprocessed` directory with TME segmentation (e.g., `slide.svs_Ss1.png`)
- A `4_5_stitch_output_segformer` directory with classification files (e.g., `slide.svs_classification_stitched.tif`)

### Accessing the Processed Slides

After processing, you can access the multi-zoom viewer at:
```
http://localhost:8000/output/slide_name/multizoom/index.html
```

### Running the Web Interface with Docker

```bash
# Start the web server
docker run --rm -p 8000:80 \
  -v /path/to/input:/data/input:cached \
  -v /path/to/output:/data/output:delegated \
  polyscope-aistil/polyscope:latest \
  web
```

Then access the web interface at `http://localhost:8000/`

## Process a group of slides (raw, classification, TME seg, QC) and create multi-zoom view
```bash
# Process a group of slides (raw, classification, TME seg, QC) and create multi-zoom view
./scripts/process_slide_group.sh input/slide_folder www/output "Slide Analysis"

# The final output will generate a URL like:
# http://localhost:8000/output/slide_folder/multizoom/index.html
```
### This command will:

1. Take a folder containing all your slide files (raw SVS, classification TIFF, TME segmentation, and QC images)
2. Process each file to create DZI versions
3. Generate a multi-zoom view that shows all 4 images in a 2x2 layout
4. Return a URL where you can access the multi-zoom view

## File Structure Requirements

For proper processing, your files should be organized as follows:

```
input/
└── slide_name/
    ├── slide_name.svs                   # Main SVS file
    ├── 0_autoqc/
    │   └── maps_qc/
    │       └── slide_name.svs_map_QC.png  # QC map image
    ├── 4_5_stitch_output_segformer/
    │   └── slide_name.svs_classification_stitched.tif  # Classification image
    └── 6_tme_seg/
        └── mask_ss1_512_postprocessed/
            └── slide_name.svs_Ss1.png     # TME segmentation image
```

Note that the auxiliary files include the `.svs` extension in their filenames (e.g., `slide_name.svs_map_QC.png`).

## Processing Slides

### Using the Web Interface:

1. Place slide files in the `input/` directory.  
2. Navigate to the dashboard.  
3. Select the slides you want to process.  
4. Click **"Process Selected Files"**.  

### After Processing, You Can:

- View individual slides by clicking on their links.  
- Create a synchronized multi-view display by clicking **"Create Multi-Zoom View"**.  

## Manual Processing (Command Line)
You can also process slides directly via the command line:

```bash
# Process a single slide
php -r "require_once 'src/core/dzi_generator.php'; convertToDZI('input/slide.svs', 'www/output');"

# Create a multi-zoom view after processing slides
php -r "require_once 'src/core/multizoom.php'; createMultizoom(glob('www/output/*/*.dzi'), 'www/output/multizoom');"
```

## Project Structure

```
polyscope-AISTIL/
├── config/                 # Configuration files
├── input/                  # Input directory for slides
├── src/
│   ├── core/               # Core functionality
│   │   ├── dzi_generator.php     # DZI conversion
│   │   ├── multizoom.php         # Multi-view creation
│   │   └── process_slide_group.php  # Slide group processing
│   └── utils/              # Utility functions
│       └── logger.php      # Logging utilities
├── scripts/                # Helper scripts
├── templates/              # HTML templates
├── www/
│   ├── api/                # API endpoints for web interface
│   ├── css/                # CSS styles
│   ├── js/                 # JavaScript and OpenSeadragon
│   ├── output/             # Output directory for processed slides
│   └── index.php           # Web dashboard
├── Dockerfile              # Docker configuration
├── docker-entrypoint.sh    # Docker entrypoint script
└── README.md
```

## How It Works

### DZI Conversion:

- Input slides are processed using the `vips` library to create Deep Zoom Image (DZI) format.  
- This creates a pyramid of tiles at different resolution levels.  
- DZI format allows efficient viewing of extremely large images.  

### Viewer Interface:

- OpenSeadragon is used to display DZI files with smooth zooming and panning.  
- Each slide gets its own viewer interface.  

### Multi-View Synchronization:

- Multiple slides can be viewed side-by-side in a synchronized interface.  
- The **"Sync Views Now"** button aligns all viewers to the same position and zoom level.  

## Troubleshooting

### Docker Issues:

- **Build failures:** Try using `--no-cache` when building the Docker image
- **Mount problems:** Ensure your paths are correctly specified in the `-v` volume mount options
- **Permission issues:** Check that Docker has read/write access to your input/output directories
- **Network issues:** Verify the host port is available when mapping to container port 80

### Processing Issues:

- **Missing vips:** Make sure `vips` is installed and available in your `PATH`.  
- **Processing errors:** Check the logs in the slide's output directory (`process.log`).  
- **Images not showing:** Verify that the DZI files were created successfully.  
- **OpenSeadragon errors:** Check the browser console for JavaScript errors.
- **Filename issues:** Ensure auxiliary files follow the correct naming pattern (include `.svs` in filename).

### Common Docker Commands:

```bash
# View logs of running container
docker logs [container_id]

# Shell into running container
docker exec -it [container_id] bash

# Check Docker disk usage
docker system df

# Clean up unused Docker resources
docker system prune -a
```

## License
MIT

## Acknowledgements

- [OpenSeadragon](https://openseadragon.github.io/) for the deep zoom viewer.  
- [vips](https://libvips.github.io/libvips/) for fast image processing.
