<<<<<<< HEAD
# Polyscope-AISTIL

AI-Polyscope is a lightweight web-based viewer for large pathology slides with AI annotations and segmentation overlays. It converts slides to Deep Zoom Image (DZI) format and provides synchronizable multi-view capabilities.

## Features

- Convert high-resolution pathology slides to DZI format  
- View slides with smooth zooming and panning  
- Create synchronized multi-view displays of multiple slides  
- Simple web-based dashboard  
- Command-line processing support  
- Compatible with SVS, TIFF, NDPI, and other common pathology slide formats  

## Requirements

- PHP 7.4 or higher  
- `vips` for DZI conversion  
- Modern web browser with JavaScript enabled  

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
│   │   ├── dzi_generator.php  # DZI conversion
│   │   └── multizoom.php      # Multi-view creation
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

## Usage

### Running the Web Interface

Start the PHP development server:
```bash
php -S localhost:8000 -t www/
```

## Troubleshooting

- **Missing vips:** Make sure `vips` is installed and available in your `PATH`.  
- **Processing errors:** Check the logs in the slide's output directory.  
- **Images not showing:** Verify that the DZI files were created successfully.  
- **OpenSeadragon errors:** Check the browser console for JavaScript errors.  

## License
MIT

## Acknowledgements

- [OpenSeadragon](https://openseadragon.github.io/) for the deep zoom viewer.  
- [vips](https://libvips.github.io/libvips/) for fast image processing.

=======
# polyscope-AISTIL
>>>>>>> parent of e225dd0 (readme)
