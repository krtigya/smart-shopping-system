import numpy as np
import tensorflow as tf
from tensorflow.keras.applications.resnet50 import ResNet50, preprocess_input
from tensorflow.keras.preprocessing import image
from sklearn.metrics.pairwise import cosine_similarity

def build_feature_extractor():
    """
    Loads ResNet50 pre-trained on ImageNet.
    Excludes top dense layers and applies Global Average Pooling 
    to output a 2048-dimensional feature embedding.
    """
    base_model = ResNet50(weights='imagenet', include_top=False, pooling='avg')
    base_model.trainable = False  # Freeze model parameters
    return base_model

def extract_features(img_path: str, model) -> np.ndarray:
    """
    Loads an image from img_path, resizes it to 224x224, standardizes pixel values,
    and runs a forward pass through ResNet50 to extract feature embeddings.
    """
    # 1. Resize image to ResNet standard input dimensions
    img = image.load_img(img_path, target_size=(224, 224))
    img_array = image.img_to_array(img)
    
    # 2. Expand dimensions to shape (1, 224, 224, 3)
    expanded_img = np.expand_dims(img_array, axis=0)
    
    # 3. Apply standard ResNet preprocessing (channel mean centering and normalization)
    preprocessed_img = preprocess_input(expanded_img)
    
    # 4. Extract visual feature vector
    feature_vector = model.predict(preprocessed_img, verbose=0)
    return feature_vector.flatten()

def calculate_similarity(query_vector: np.ndarray, db_vectors: np.ndarray) -> np.ndarray:
    """
    Calculates Cosine Similarity between a single query image vector 
    and a matrix of product database vectors.
    """
    query_vector = query_vector.reshape(1, -1)
    similarities = cosine_similarity(query_vector, db_vectors)[0]
    return similarities