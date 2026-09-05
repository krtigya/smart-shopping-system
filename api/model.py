"""Keras feature extraction and cosine-similarity catalog indexing."""

import json
import os
from typing import Any

import numpy as np
import h5py
from PIL import Image

try:
    import torch
    import torchvision.transforms as T
    from torchvision.models import ResNet50_Weights, resnet50
except ImportError:
    torch = None

BASE_DIR = os.path.dirname(os.path.abspath(__file__))
MODELS_DIR = os.path.join(BASE_DIR, "models")
STORAGE_DIR = os.path.join(BASE_DIR, "storage")
FEATURE_EXTRACTOR_PATH = os.path.join(MODELS_DIR, "feature_extractor_v2.h5")
CLASSIFIER_PATH = os.path.join(MODELS_DIR, "best_model_v2.h5")
EMBEDDINGS_PATH = os.path.join(MODELS_DIR, "product_embeddings.json")
CLASS_INDICES_PATH = os.path.join(MODELS_DIR, "class_indices.json")
CATALOG_VECTORS_PATH = os.path.join(STORAGE_DIR, "catalog_vectors.npy")
CATALOG_NAMES_PATH = os.path.join(STORAGE_DIR, "catalog_names.npy")
CATALOG_META_PATH = os.path.join(STORAGE_DIR, "catalog_meta.json")
IMAGE_SIZE = (160, 160)
MODEL_KIND = "keras-feature-extractor-v2"
PREPROCESSING_VERSION = "foreground-crop-resnet-v2"

_MODEL: Any = None
_MODEL_META: dict[str, Any] = {}
_RESNET: Any = None
_RESNET_TRANSFORM: Any = None


class KerasH5FeatureExtractor:
    """Small NumPy runtime for the saved Keras CNN feature extractor.

    This avoids TensorFlow's unavailable Python 3.14 Windows wheel while
    preserving the exact convolution, batch-normalization, pooling, GAP, and
    feature-vector operations from feature_extractor_v2.h5.
    """

    def __init__(self, path: str):
        self.layers = []
        with h5py.File(path, "r") as handle:
            weights = handle["model_weights"]
            for index in range(4, 8):
                conv = weights[f"conv2d_{index}/conv2d_{index}"]
                norm = weights[f"batch_normalization_{index}/batch_normalization_{index}"]
                self.layers.append({
                    "kernel": conv["kernel"][()].astype(np.float32),
                    "bias": conv["bias"][()].astype(np.float32),
                    "gamma": norm["gamma"][()].astype(np.float32),
                    "beta": norm["beta"][()].astype(np.float32),
                    "mean": norm["moving_mean"][()].astype(np.float32),
                    "variance": norm["moving_variance"][()].astype(np.float32),
                })
            dense = weights["feature_vector/feature_vector"]
            self.dense_kernel = dense["kernel"][()].astype(np.float32)
            self.dense_bias = dense["bias"][()].astype(np.float32)
        self.classifier_kernel = None
        self.classifier_bias = None
        if os.path.isfile(CLASSIFIER_PATH):
            with h5py.File(CLASSIFIER_PATH, "r") as handle:
                dense = handle["model_weights/dense/sequential/dense"]
                self.classifier_kernel = dense["kernel"][()].astype(np.float32)
                self.classifier_bias = dense["bias"][()].astype(np.float32)

    @staticmethod
    def _conv_same(values: np.ndarray, layer: dict[str, np.ndarray]) -> np.ndarray:
        padded = np.pad(values, ((1, 1), (1, 1), (0, 0)), mode="constant")
        windows = np.lib.stride_tricks.sliding_window_view(padded, (3, 3), axis=(0, 1))
        result = np.einsum("hwcij,ijco->hwo", windows, layer["kernel"], optimize=True)
        result += layer["bias"]
        result = layer["gamma"] * (result - layer["mean"]) / np.sqrt(layer["variance"] + 1e-3) + layer["beta"]
        return np.maximum(result, 0)

    @staticmethod
    def _pool(values: np.ndarray) -> np.ndarray:
        height, width, channels = values.shape
        return values[:height - height % 2, :width - width % 2].reshape(height // 2, 2, width // 2, 2, channels).max(axis=(1, 3))

    def _features(self, batch: np.ndarray) -> np.ndarray:
        outputs = []
        for values in batch:
            for layer in self.layers:
                values = self._pool(self._conv_same(values, layer))
            values = values.mean(axis=(0, 1))
            values = np.maximum(values @ self.dense_kernel + self.dense_bias, 0)
            outputs.append(values)
        return np.asarray(outputs, dtype=np.float32)

    def predict(self, batch: np.ndarray, verbose: int = 0) -> np.ndarray:
        return self._features(batch)

    def predict_class(self, batch: np.ndarray) -> int | None:
        if self.classifier_kernel is None:
            return None
        features = self._features(batch)
        logits = features @ self.classifier_kernel + self.classifier_bias
        return int(np.argmax(logits[0]))


def build_feature_extractor():
    global _MODEL, _MODEL_META, _RESNET, _RESNET_TRANSFORM
    if _MODEL is not None:
        return _MODEL
    if not os.path.isfile(FEATURE_EXTRACTOR_PATH):
        raise FileNotFoundError(f"Trained feature extractor not found: {FEATURE_EXTRACTOR_PATH}")
    _MODEL = KerasH5FeatureExtractor(FEATURE_EXTRACTOR_PATH)
    resnet_status = "unavailable"
    if torch is not None:
        try:
            _RESNET = resnet50(weights=ResNet50_Weights.DEFAULT)
            _RESNET.fc = torch.nn.Identity()
            _RESNET.eval()
            _RESNET_TRANSFORM = ResNet50_Weights.DEFAULT.transforms()
            resnet_status = "resnet50-imagenet"
        except Exception as exc:
            print(f"Pretrained ResNet unavailable; using trained CNN only: {exc}")
            _RESNET = None
    classes = {}
    if os.path.isfile(CLASS_INDICES_PATH):
        with open(CLASS_INDICES_PATH, "r", encoding="utf-8") as handle:
            classes = json.load(handle)
    _MODEL_META = {
        "framework": "tensorflow.keras",
        "model": MODEL_KIND,
        "image_size": IMAGE_SIZE[0],
        "embedding_dim": 256 + (2048 if _RESNET is not None else 0),
        "num_classes": len(classes),
        "class_names": list(classes.keys()),
        "reference_embeddings": os.path.basename(EMBEDDINGS_PATH),
        "feature_sources": ["trained-cnn"] + ([resnet_status] if _RESNET is not None else []),
        "preprocessing": PREPROCESSING_VERSION,
    }
    return _MODEL


def get_model_kind() -> str:
    build_feature_extractor()
    return MODEL_KIND


def get_model_meta() -> dict[str, Any]:
    build_feature_extractor()
    return _MODEL_META


def _foreground_crop(image: Image.Image) -> Image.Image:
    """Crop a clear foreground product away from a plain image background."""
    array = np.asarray(image.convert("RGB"), dtype=np.float32)
    height, width = array.shape[:2]
    if height < 20 or width < 20:
        return image
    border = np.concatenate((array[:5].reshape(-1, 3), array[-5:].reshape(-1, 3), array[:, :5].reshape(-1, 3), array[:, -5:].reshape(-1, 3)))
    background = np.median(border, axis=0)
    distance = np.linalg.norm(array - background, axis=2)
    threshold = max(22.0, float(np.percentile(distance, 70) * 0.35))
    rows, columns = np.where(distance > threshold)
    if len(rows) < 20:
        return image
    top, bottom = int(rows.min()), int(rows.max())
    left, right = int(columns.min()), int(columns.max())
    box_area = (bottom - top + 1) * (right - left + 1)
    if box_area < height * width * 0.04 or box_area > height * width * 0.94:
        return image
    padding_y = max(4, int((bottom - top + 1) * 0.10))
    padding_x = max(4, int((right - left + 1) * 0.10))
    return image.crop((max(0, left - padding_x), max(0, top - padding_y), min(width, right + padding_x + 1), min(height, bottom + padding_y + 1)))


def _center_focus(image: Image.Image) -> Image.Image:
    """Remove a small amount of edge/background while keeping centered products intact."""
    width, height = image.size
    if width < 40 or height < 40:
        return image
    margin_x = int(width * 0.06)
    margin_y = int(height * 0.06)
    return image.crop((margin_x, margin_y, width - margin_x, height - margin_y))


def _prepare_image(img_path: str) -> Image.Image:
    image = Image.open(img_path).convert("RGB")
    focused = _foreground_crop(image)
    # A white product on a white background has little border contrast. In that
    # case, use a conservative centered crop instead of feeding the whole scene.
    if focused.size == image.size:
        focused = _center_focus(image)
    return focused


def extract_features(img_path: str, model=None) -> np.ndarray:
    global _RESNET, _RESNET_TRANSFORM
    model = model or build_feature_extractor()
    image = _prepare_image(img_path)
    array = np.asarray(image.resize(IMAGE_SIZE), dtype=np.float32) / 255.0
    custom_vector = np.asarray(model.predict(np.expand_dims(array, axis=0), verbose=0)[0], dtype=np.float32).reshape(-1)
    custom_norm = np.linalg.norm(custom_vector)
    custom_vector = custom_vector / custom_norm if custom_norm > 0 else custom_vector
    if _RESNET is None:
        return custom_vector
    tensor = _RESNET_TRANSFORM(image).unsqueeze(0)
    with torch.no_grad():
        resnet_vector = _RESNET(tensor).squeeze(0).cpu().numpy().astype(np.float32)
    resnet_norm = np.linalg.norm(resnet_vector)
    resnet_vector = resnet_vector / resnet_norm if resnet_norm > 0 else resnet_vector
    # The supplied classifier is useful for its trained fashion classes, but
    # ImageNet ResNet is more robust for products outside those classes.
    combined = np.concatenate((custom_vector * 0.25, resnet_vector * 0.75))
    combined_norm = np.linalg.norm(combined)
    return combined / combined_norm if combined_norm > 0 else combined


def calculate_similarity(query_vector: np.ndarray, db_vectors: np.ndarray) -> np.ndarray:
    query = np.asarray(query_vector, dtype=np.float32).reshape(-1)
    database = np.asarray(db_vectors, dtype=np.float32)
    query_norm = np.linalg.norm(query)
    database_norms = np.linalg.norm(database, axis=1)
    if query_norm == 0:
        return np.zeros(len(database), dtype=np.float32)
    normalized_database = database / np.maximum(database_norms[:, None], 1e-12)
    return normalized_database @ (query / query_norm)


def _catalog_files(image_dir: str) -> list[str]:
    extensions = {".jpg", ".jpeg", ".png", ".webp", ".gif"}
    ignored = {"hero.svg", "no-image.svg", "logo.png"}
    paths = []
    for root, _, files in os.walk(image_dir):
        for name in files:
            if name.lower() in ignored or os.path.splitext(name)[1].lower() not in extensions:
                continue
            paths.append(os.path.join(root, name))
    return sorted(paths)


def _catalog_signature(paths: list[str]) -> list[dict[str, Any]]:
    return [{"name": os.path.basename(path), "size": os.path.getsize(path), "mtime": os.path.getmtime(path)} for path in paths]


def catalog_index_needs_rebuild(image_dir: str) -> bool:
    if not all(os.path.isfile(path) for path in (CATALOG_VECTORS_PATH, CATALOG_NAMES_PATH, CATALOG_META_PATH)):
        return True
    try:
        with open(CATALOG_META_PATH, "r", encoding="utf-8") as handle:
            meta = json.load(handle)
        expected_dim = int(get_model_meta().get("embedding_dim", 256))
        return (
            meta.get("signature") != _catalog_signature(_catalog_files(image_dir))
            or not isinstance(meta.get("classes"), list)
            or int(meta.get("embedding_dim", 0)) != expected_dim
            or meta.get("preprocessing") != PREPROCESSING_VERSION
        )
    except (OSError, ValueError, TypeError):
        return True


def rebuild_catalog_index(image_dir: str | None = None) -> int:
    image_dir = image_dir or os.path.abspath(os.path.join(BASE_DIR, "..", "assets", "images"))
    paths = _catalog_files(image_dir)
    model = build_feature_extractor()
    vectors, names, classes = [], [], []
    class_indices = {}
    if os.path.isfile(CLASS_INDICES_PATH):
        with open(CLASS_INDICES_PATH, "r", encoding="utf-8") as handle:
            class_indices = {int(index): name for name, index in json.load(handle).items()}
    for path in paths:
        try:
            vectors.append(extract_features(path, model))
            names.append(os.path.basename(path))
            prepared = _prepare_image(path)
            predicted = model.predict_class(np.expand_dims(np.asarray(prepared.resize(IMAGE_SIZE), dtype=np.float32) / 255.0, axis=0))
            classes.append(class_indices.get(predicted, ""))
        except Exception as exc:
            print(f"Failed to index {path}: {exc}")
    if not vectors:
        raise RuntimeError(f"No supported product images found in {image_dir}")
    os.makedirs(STORAGE_DIR, exist_ok=True)
    np.save(CATALOG_VECTORS_PATH, np.asarray(vectors, dtype=np.float32))
    np.save(CATALOG_NAMES_PATH, np.asarray(names))
    with open(CATALOG_META_PATH, "w", encoding="utf-8") as handle:
        json.dump({
            "signature": _catalog_signature(paths),
            "embedding_dim": len(vectors[0]),
            "classes": classes,
            "preprocessing": PREPROCESSING_VERSION,
            "feature_sources": get_model_meta().get("feature_sources", ["trained-cnn"]),
        }, handle)
    return len(names)


def ensure_catalog_index(image_dir: str | None = None) -> int:
    image_dir = image_dir or os.path.abspath(os.path.join(BASE_DIR, "..", "assets", "images"))
    if catalog_index_needs_rebuild(image_dir):
        print("Building catalog index with the supplied Keras feature extractor...")
        return rebuild_catalog_index(image_dir)
    return int(np.load(CATALOG_VECTORS_PATH, mmap_mode="r").shape[0])
