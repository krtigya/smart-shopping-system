import os

import numpy as np
from fastapi import FastAPI, HTTPException
from pydantic import BaseModel, Field

from model import (
    CATALOG_NAMES_PATH,
    CATALOG_VECTORS_PATH,
    build_feature_extractor,
    calculate_similarity,
    ensure_catalog_index,
    extract_features,
    get_model_kind,
    get_model_meta,
)

app = FastAPI(title="Smart Shopping System - AI Engine")

print("Loading Keras visual search model...")
cnn_model = build_feature_extractor()
catalog_count = ensure_catalog_index()
print(f"Model ready: {get_model_kind()} ({catalog_count} catalog images)")


class VisualSearchPayload(BaseModel):
    image_path: str
    top_k: int = Field(default=5, ge=1, le=50)


@app.get("/")
def health_check():
    meta = get_model_meta()
    return {
        "status": "running",
        "model": get_model_kind(),
        "framework": meta.get("framework"),
        "embedding_dim": meta.get("embedding_dim"),
        "feature_sources": meta.get("feature_sources"),
        "preprocessing": meta.get("preprocessing"),
        "catalog_size": int(np.load(CATALOG_VECTORS_PATH, mmap_mode="r").shape[0]),
    }


@app.post("/api/search")
def search_similar_products(payload: VisualSearchPayload):
    if not os.path.isfile(payload.image_path):
        raise HTTPException(status_code=400, detail=f"Image path '{payload.image_path}' not found.")
    try:
        # Admin uploads can happen after the API starts. Refresh the index
        # automatically so a restart or manual reindex is not required.
        ensure_catalog_index()
        vectors = np.load(CATALOG_VECTORS_PATH, mmap_mode="r")
        names = np.load(CATALOG_NAMES_PATH, allow_pickle=False)
        query_vector = extract_features(payload.image_path, cnn_model)
        similarities = calculate_similarity(query_vector, vectors)
        top_indices = np.argsort(similarities)[::-1][: payload.top_k]
        return {
            "status": "success",
            "model": get_model_kind(),
            "matches": [
                {"image_name": str(names[index]), "score": float(similarities[index])}
                for index in top_indices
            ],
        }
    except Exception as exc:
        raise HTTPException(status_code=500, detail=str(exc)) from exc
