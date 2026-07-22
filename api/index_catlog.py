import os
import glob
import numpy as np
from model import build_feature_extractor, extract_features

# Path setup
BASE_DIR = os.path.dirname(os.path.abspath(__file__))
IMAGE_DIR = os.path.abspath(os.path.join(BASE_DIR, "..", "assets", "images"))
STORAGE_DIR = os.path.join(BASE_DIR, "storage")

os.makedirs(STORAGE_DIR, exist_ok=True)

def index_all_products():
    print("Loading CNN Model (ResNet50)...")
    model = build_feature_extractor()
    
    # Supported image formats
    extensions = ['*.jpg', '*.jpeg', '*.png', '*.webp']
    image_paths = []
    for ext in extensions:
        image_paths.extend(glob.glob(os.path.join(IMAGE_DIR, ext)))
        
    print(f"Found {len(image_paths)} images in catalog directory: {IMAGE_DIR}")
    
    vectors = []
    metadata = []
    
    for img_path in image_paths:
        file_name = os.path.basename(img_path)
        
        # Skip placeholder graphics or non-product assets
        if file_name in ['hero.svg', 'no-image.svg', 'logo.png']:
            continue
            
        try:
            print(f"Processing vector for: {file_name}...")
            feat_vec = extract_features(img_path, model)
            vectors.append(feat_vec)
            metadata.append(file_name)
        except Exception as e:
            print(f"Failed to process {file_name}: {e}")

    if len(vectors) > 0:
        vectors_matrix = np.array(vectors)
        
        # Save pre-computed matrix and file mapping
        np.save(os.path.join(STORAGE_DIR, "catalog_vectors.npy"), vectors_matrix)
        np.save(os.path.join(STORAGE_DIR, "catalog_names.npy"), np.array(metadata))
        
        print(f"\n✅ Indexing Complete!")
        print(f"Saved {len(metadata)} product feature vectors to: {STORAGE_DIR}")
    else:
        print("\n❌ No product images found to index.")

if __name__ == "__main__":
    index_all_products()