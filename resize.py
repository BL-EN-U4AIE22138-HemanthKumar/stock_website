import os
from PIL import Image

def resize_images(input_folder, output_folder, size):
    # Create output folder if it doesn't exist
    if not os.path.exists(output_folder):
        os.makedirs(output_folder)

    # Iterate through all files in the input folder
    for filename in os.listdir(input_folder):
        file_path = os.path.join(input_folder, filename)

        # Check if it is an image file (you can modify this as needed)
        if file_path.lower().endswith(('.png', '.jpg', '.jpeg', '.bmp', '.gif')):
            try:
                # Open an image file
                with Image.open(file_path) as img:
                    # Resize the image
                    img_resized = img.resize(size)
                    
                    # Save the resized image to the output folder
                    output_path = os.path.join(output_folder, filename)
                    img_resized.save(output_path)
                    print(f"Resized and saved {filename}")
            except Exception as e:
                print(f"Error processing {filename}: {e}")

# Example usage
input_folder = 'C:/xampp/htdocs/stock_website/assets/Animal'  # Replace with your folder path
output_folder = 'C:/xampp/htdocs/stock_website/assets/Animal_Resized'  # Replace with your output folder path
resize_images(input_folder, output_folder,(1980,1080))
