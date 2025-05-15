#!/usr/bin/env python3
"""
TIFF Conversion Tester
Tests various approaches to convert a problematic TIFF file
"""

import os
import sys
import subprocess
import time
import shutil
from datetime import datetime

def test_tiff_conversion(file_path, output_dir=None):
    """Test different methods to convert a problematic TIFF file"""
    # Default to creating output in the same directory as the input file
    if output_dir is None:
        output_dir = os.path.dirname(file_path) + "/conversion_test_" + str(int(time.time()))
    
    # Create debug log for detailed tracing
    debug_log = os.path.join(os.path.dirname(file_path), "debug_trace.log")
    with open(debug_log, "w") as debug:
        debug.write(f"Starting detailed debug trace\n")
        debug.write(f"File path: {file_path}\n")
        debug.write(f"Output directory: {output_dir}\n")
        debug.write(f"Current working directory: {os.getcwd()}\n")
        
        # Check file details
        try:
            file_stat = os.stat(file_path)
            debug.write(f"File exists: Yes\n")
            debug.write(f"File size: {file_stat.st_size} bytes\n")
            debug.write(f"File permissions: {oct(file_stat.st_mode)}\n")
        except Exception as e:
            debug.write(f"Error checking file: {str(e)}\n")
        
        # Log system info
        debug.write("\nSystem Information:\n")
        try:
            # Check vips version
            vips_version = subprocess.run("vips --version", shell=True, capture_output=True, text=True)
            debug.write(f"VIPS version: {vips_version.stdout.strip()}\n")
        except Exception as e:
            debug.write(f"Could not get VIPS version: {str(e)}\n")
    
    # Create output directory and log file
    os.makedirs(output_dir, exist_ok=True)
    log_file = os.path.join(output_dir, "conversion_test.log")
    
    with open(log_file, "w") as log:
        log.write(f"TIFF Conversion Test Report\n")
        log.write(f"========================\n")
        log.write(f"File: {file_path}\n")
        log.write(f"Test time: {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}\n\n")
        
        if not os.path.exists(file_path):
            log.write("ERROR: File does not exist!\n")
            print(f"ERROR: File does not exist: {file_path}")
            return
        
        # Get base filename for temporary files
        base_filename = os.path.splitext(os.path.basename(file_path))[0]
        
        # Define conversion approaches to try
        approaches = [
            {
                "name": "Direct vips with memory settings",
                "cmd": f"vips --vips-concurrency=1 --vips-cache-max=0 copy \"{file_path}\" \"{output_dir}/{base_filename}_1.png\""
            },
            {
                "name": "Downscale first",
                "cmd": f"vips resize \"{file_path}\" \"{output_dir}/{base_filename}_2.png\" 0.5"
            },
            {
                "name": "Specific loader options",
                "cmd": f"vips copy \"{file_path}[shrink=2,autorotate=0]\" \"{output_dir}/{base_filename}_3.png\""
            },
            {
                "name": "Extract first page",
                "cmd": f"vips copy \"{file_path}[page=0]\" \"{output_dir}/{base_filename}_4.png\""
            },
            {
                "name": "GDAL conversion",
                "cmd": f"gdal_translate -of PNG \"{file_path}\" \"{output_dir}/{base_filename}_5.png\""
            },
            {
                "name": "Attempt DZI creation directly",
                "cmd": f"vips dzsave \"{file_path}\" \"{output_dir}/{base_filename}_dz\" --tile-size=254 --overlap=1"
            },
            {
                "name": "Use tiffload with explicit options",
                "cmd": f"vips tiffload \"{file_path}[autorotate=0]\" \"{output_dir}/{base_filename}_6.v\" && vips copy \"{output_dir}/{base_filename}_6.v\" \"{output_dir}/{base_filename}_6.png\""
            },
            {
                "name": "Use magickload if available",
                "cmd": f"vips magickload \"{file_path}\" \"{output_dir}/{base_filename}_7.v\" && vips copy \"{output_dir}/{base_filename}_7.v\" \"{output_dir}/{base_filename}_7.png\""
            }
        ]
        
        # Store successful approaches
        successful = []
        
        # Debug file details
        with open(debug_log, "a") as debug:
            debug.write("\nFile details before tests:\n")
            try:
                file_details = subprocess.run(f"file \"{file_path}\"", shell=True, capture_output=True, text=True)
                debug.write(f"File command output: {file_details.stdout}\n")
                
                # Try to get specific TIFF information
                try:
                    tiff_info = subprocess.run(f"tiffinfo \"{file_path}\"", shell=True, capture_output=True, text=True)
                    if tiff_info.returncode == 0:
                        debug.write(f"TIFF Info:\n{tiff_info.stdout}\n")
                    else:
                        debug.write(f"tiffinfo failed: {tiff_info.stderr}\n")
                except Exception as e:
                    debug.write(f"Error running tiffinfo: {str(e)}\n")
            except Exception as e:
                debug.write(f"Error getting file details: {str(e)}\n")
        
        # Test each approach
        for i, approach in enumerate(approaches):
            log.write(f"\nApproach {i+1}: {approach['name']}\n")
            log.write(f"Command: {approach['cmd']}\n")
            
            # Also log to debug file
            with open(debug_log, "a") as debug:
                debug.write(f"\nTrying approach {i+1}: {approach['name']}\n")
                debug.write(f"Command: {approach['cmd']}\n")
            
            try:
                start_time = time.time()
                result = subprocess.run(approach['cmd'], shell=True, capture_output=True, text=True)
                elapsed = time.time() - start_time
                
                log.write(f"Time: {elapsed:.2f} seconds\n")
                log.write(f"Return code: {result.returncode}\n")
                
                # Also log to debug file
                with open(debug_log, "a") as debug:
                    debug.write(f"Time: {elapsed:.2f} seconds\n")
                    debug.write(f"Return code: {result.returncode}\n")
                    if result.stdout:
                        debug.write(f"Output: {result.stdout}\n")
                    if result.stderr:
                        debug.write(f"Error: {result.stderr}\n")
                
                if result.returncode == 0:
                    log.write("Result: SUCCESS\n")
                    successful.append(i+1)
                    if result.stdout:
                        log.write(f"Output: {result.stdout}\n")
                    
                    # If this is a successful conversion to PNG, try to create DZI from it
                    if approach['name'] != "Attempt DZI creation directly" and "png" in approach['cmd']:
                        out_png = approach['cmd'].split(" ")[-1].strip('"')
                        
                        # Log successful file details
                        with open(debug_log, "a") as debug:
                            debug.write(f"\nSuccessfully created file: {out_png}\n")
                            try:
                                png_stat = os.stat(out_png)
                                debug.write(f"PNG file size: {png_stat.st_size} bytes\n")
                                png_details = subprocess.run(f"file \"{out_png}\"", shell=True, capture_output=True, text=True)
                                debug.write(f"PNG file type: {png_details.stdout}\n")
                            except Exception as e:
                                debug.write(f"Error checking PNG file: {str(e)}\n")
                        
                        if os.path.exists(out_png):
                            dzi_cmd = f"vips dzsave \"{out_png}\" \"{output_dir}/{base_filename}_from_{i+1}_dz\" --tile-size=254 --overlap=1"
                            log.write(f"\nTrying DZI from successful conversion:\n")
                            log.write(f"Command: {dzi_cmd}\n")
                            
                            with open(debug_log, "a") as debug:
                                debug.write(f"\nTrying DZI creation:\n")
                                debug.write(f"Command: {dzi_cmd}\n")
                            
                            dzi_result = subprocess.run(dzi_cmd, shell=True, capture_output=True, text=True)
                            log.write(f"Return code: {dzi_result.returncode}\n")
                            
                            with open(debug_log, "a") as debug:
                                debug.write(f"DZI return code: {dzi_result.returncode}\n")
                                if dzi_result.stdout:
                                    debug.write(f"DZI output: {dzi_result.stdout}\n")
                                if dzi_result.stderr:
                                    debug.write(f"DZI error: {dzi_result.stderr}\n")
                                
                                if dzi_result.returncode == 0:
                                    dzi_path = f"{output_dir}/{base_filename}_from_{i+1}_dz.dzi"
                                    debug.write(f"DZI path: {dzi_path}\n")
                                    if os.path.exists(dzi_path):
                                        debug.write(f"DZI file exists: Yes\n")
                                        # List DZI directory contents
                                        dzi_dir = f"{output_dir}/{base_filename}_from_{i+1}_dz_files"
                                        if os.path.exists(dzi_dir):
                                            files = os.listdir(dzi_dir)
                                            debug.write(f"DZI directory contents: {files}\n")
                            
                            if dzi_result.returncode == 0:
                                log.write("DZI creation: SUCCESS\n")
                                successful.append(f"{i+1}_dzi")
                            else:
                                log.write("DZI creation: FAILED\n")
                                if dzi_result.stderr:
                                    log.write(f"Error: {dzi_result.stderr}\n")
                else:
                    log.write("Result: FAILED\n")
                    if result.stderr:
                        log.write(f"Error: {result.stderr}\n")
                    if result.stdout:
                        log.write(f"Output: {result.stdout}\n")
            except Exception as e:
                log.write(f"Exception: {str(e)}\n")
                with open(debug_log, "a") as debug:
                    debug.write(f"Exception: {str(e)}\n")
        
        # Check file system details after all tests
        with open(debug_log, "a") as debug:
            debug.write("\nOutput directory after tests:\n")
            try:
                if os.path.exists(output_dir):
                    files = os.listdir(output_dir)
                    debug.write(f"Files in output directory: {files}\n")
                    
                    # Check sizes of any PNGs
                    for file in files:
                        if file.endswith('.png'):
                            file_path = os.path.join(output_dir, file)
                            try:
                                file_size = os.path.getsize(file_path)
                                debug.write(f"File {file}: {file_size} bytes\n")
                            except:
                                debug.write(f"Could not get size for {file}\n")
            except Exception as e:
                debug.write(f"Error listing files: {str(e)}\n")
        
        # Summary
        log.write("\n\n--- Summary ---\n")
        log.write(f"Tested {len(approaches)} approaches for converting problematic TIFF\n")
        
        if successful:
            log.write("\nSuccessful approaches:\n")
            for success in successful:
                if isinstance(success, int):
                    log.write(f"- Approach {success}: {approaches[success-1]['name']}\n")
                else:
                    log.write(f"- DZI creation from approach {success.split('_')[0]}\n")
            
            # Copy the most successful PNG to a standard location for PHP to use
            best_approach = None
            for s in successful:
                if isinstance(s, str) and s.endswith('_dzi'):
                    best_approach = int(s.split('_')[0])
                    break
            
            if best_approach is None and successful:
                if isinstance(successful[0], int):
                    best_approach = successful[0]
            
            if best_approach:
                try:
                    png_path = f"{output_dir}/{base_filename}_{best_approach}.png"
                    standard_png = f"{output_dir}/{base_filename}_best.png"
                    if os.path.exists(png_path):
                        shutil.copy(png_path, standard_png)
                        log.write(f"\nCopied best approach ({best_approach}) to {standard_png}\n")
                        
                        with open(debug_log, "a") as debug:
                            debug.write(f"\nCopied best approach ({best_approach}) to {standard_png}\n")
                except Exception as e:
                    log.write(f"Error copying best approach: {str(e)}\n")
                    with open(debug_log, "a") as debug:
                        debug.write(f"Error copying best approach: {str(e)}\n")
            
            log.write("\nRecommended approach for PHP code:\n")
            if "6_dzi" in successful or "7_dzi" in successful or "5_dzi" in successful:
                # If any of the specialized loaders worked well for DZI, recommend that
                successful_dzi = next((s for s in successful if isinstance(s, str) and s.endswith("_dzi")), None)
                approach_num = int(successful_dzi.split("_")[0])
                log.write(f"Use approach {approach_num}: {approaches[approach_num-1]['name']} followed by dzsave\n")
                log.write(f"Command: {approaches[approach_num-1]['cmd']}\n")
                
                # Also log exact PHP command for easy copy-paste
                php_cmd = f'$convertCommand = "' + approaches[approach_num-1]['cmd'].replace(output_dir, "$workingDir").replace(file_path, "$inputFile").split(' "')[1] + '";'
                log.write(f"\nPHP Command: {php_cmd}\n")
                
            elif 6 in successful:
                log.write(f"Use tiffload with explicit options:\n{approaches[5]['cmd']}\n")
            elif 5 in successful:
                log.write(f"Use GDAL conversion:\n{approaches[4]['cmd']}\n")
            elif 1 in successful:
                log.write(f"Use direct vips with memory settings:\n{approaches[0]['cmd']}\n")
            else:
                # Just use the first successful approach
                first_success = successful[0]
                if isinstance(first_success, int):
                    log.write(f"Use approach {first_success}: {approaches[first_success-1]['name']}\n")
                    log.write(f"Command: {approaches[first_success-1]['cmd']}\n")
        else:
            log.write("\nNo successful conversion methods found.\n")
            log.write("The TIFF file may be corrupted or in a format not supported by available tools.\n")
        
        log.write(f"\nTest files are in: {output_dir}\n")
        log.write(f"Detailed debug trace in: {debug_log}\n")
    
    print(f"Testing complete. Results saved to {log_file}")
    print(f"Debug trace saved to {debug_log}")
    print(f"Test files are in: {output_dir}")
    return log_file

if __name__ == "__main__":
    if len(sys.argv) < 2:
        print("Usage: python test.py input/pilot_image_A.svs/4_5_stitch_output_segformer/pilot_image_A.svs_classification_stitched.tif")
        sys.exit(1)
    
    tiff_path = sys.argv[1]
    
    test_tiff_conversion(tiff_path)