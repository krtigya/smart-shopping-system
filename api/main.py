import os
import numpy as np
from fastapi import FastAPI, HTTPException
from pydantic import BaseModel
from model import build_feature_extractor, extract_features, calculate_similarity

app = FastAPI(title="Smart Shopping System - AI Engine")

BASE_DIR = os.path.dirname(os.path.abspath(__file__))
STORAGE_DIR = os.path.join(BASE_DIR, "storage")

print("Initializing ResNet50 Feature Extractor...")
cnn_model = build_feature_extractor()
print("Model ready!")

class VisualSearchPayload(BaseModel):
    image_path: str
    top_k: int = 5

@app.get("/")
def health_check():
    return {"status": "running", "model": "ResNet50"}

@app.post("/api/search")
def search_similar_products(payload: VisualSearchPayload):
    if not os.path.exists(payload.image_path):
        raise HTTPException(status_code=400, detail=f"Image path '{payload.image_path}' not found.")

    vec_file = os.path.join(STORAGE_DIR, "catalog_vectors.npy")
    names_file = os.path.join(STORAGE_DIR, "catalog_names.npy")

    if not os.path.exists(vec_file) or not os.path.exists(names_file):
        raise HTTPException(status_code=500, detail="Catalog index files missing. Run index_catalog.py first.")

    try:
        # Load precomputed database embeddings
        db_vectors = np.load(vec_file)
        db_names = np.load(names_file)

        # Extract features for current uploaded image
        query_vector = extract_features(payload.image_path, cnn_model)

        # Calculate Cosine Similarities across inventory
        similarities = calculate_similarity(query_vector, db_vectors)

        # Rank indices by top similarity scores descending
        top_indices = np.argsort(similarities)[::-1][:payload.top_k]

        matches = []
        for idx in top_indices:
            matches.append({
                "image_name": str(db_names[idx]),
                "score": float(similarities[idx])
            })

        return {
            "status": "success",
            "matches": matches
        }
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))