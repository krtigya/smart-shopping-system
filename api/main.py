import os
import numpy as np
from fastapi import FastAPI, HTTPException
from pydantic import BaseModel
from model import build_feature_extractor, extract_features, calculate_similarity

app = FastAPI(title="Smart Shopping System - AI Engine")

# Load ResNet50 model into memory on startup
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
    """
    Receives an uploaded image path from the PHP backend, converts it to 
    a 2048-d feature vector, and compares it against stored catalog vectors.
    """
    if not os.path.exists(payload.image_path):
        raise HTTPException(status_code=400, detail=f"Image path '{payload.image_path}' not found.")

    try:
        # Extract features for query image
        query_vector = extract_features(payload.image_path, cnn_model)
        
        # Verify vector generation (2048-dimensional output)
        return {
            "status": "success",
            "vector_dimension": len(query_vector),
            "message": "Features extracted successfully."
        }
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))