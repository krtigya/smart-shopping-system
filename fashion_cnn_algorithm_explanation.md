# Fashion CNN Algorithm Explanation

This code trains a convolutional neural network (CNN) to classify fashion images by `articleType`, then uses the trained CNN to create a 256-number embedding for each product.

> Security: the original code contains a Kaggle API key. Revoke it in Kaggle and create a new key.

## 1. Complete pipeline

```text
Dataset → clean data → select 20 classes → split data
        → resize/normalize/augment → train CNN
        → save best classifier → extract feature vectors
        → save product embeddings
```

## 2. Dataset preparation

```python
df = pd.read_csv('fashion_data/styles.csv', on_bad_lines='skip')
df['image'] = df['id'].astype(str) + '.jpg'
```

The CSV is loaded into a DataFrame. If the product ID is `12345`, the expected image becomes `12345.jpg`.

```python
df = df[df['image'].apply(lambda x: os.path.exists(os.path.join(img_dir, x)))]
df = df[['image', 'articleType']].dropna()
```

Rows without a real image or valid label are removed.

```python
top_classes = df['articleType'].value_counts().nlargest(20).index
df = df[df['articleType'].isin(top_classes)]
```

The 20 most frequent clothing classes are selected. This is a supervised 20-class classification problem.

## 3. Train/validation split

```python
train_df, val_df = train_test_split(
    df, test_size=0.15, stratify=df['articleType'], random_state=42
)
```

85% is used for training and 15% for validation. `stratify` keeps approximately the same class proportions in both sets. `random_state=42` makes the split repeatable.

## 4. Image preprocessing

Images are resized to `160 × 160 × 3`; the last dimension represents RGB channels.

Pixel normalization uses:

$$x_{normalized}=\frac{x_{pixel}}{255}$$

Training augmentation uses rotation, zoom, and horizontal flipping. Validation images are only normalized, so validation remains consistent.

`class_mode='categorical'` converts labels into one-hot vectors. For example: `Jeans → [1,0,0]`, `Shirts → [0,1,0]`, `Shoes → [0,0,1]`.

## 5. CNN architecture and shapes

| Stage | Output shape |
|---|---:|
| Input | 160 × 160 × 3 |
| Conv2D: 32 filters | 160 × 160 × 32 |
| MaxPooling | 80 × 80 × 32 |
| Conv2D: 64 filters | 80 × 80 × 64 |
| MaxPooling | 40 × 40 × 64 |
| Conv2D: 128 filters | 40 × 40 × 128 |
| MaxPooling | 20 × 20 × 128 |
| Conv2D: 256 filters | 20 × 20 × 256 |
| MaxPooling | 10 × 10 × 256 |
| GlobalAveragePooling | 256 |
| Dense feature layer | 256 |
| Softmax output | number of classes |

### Convolution

A 3×3 filter slides over the image and detects a pattern:

$$z_{i,j,k}=b_k+\sum_u\sum_v\sum_c W_{u,v,c,k}x_{i+u,j+v,c}$$

`W` is a learned filter, `b` is its bias, and `z` is a feature-map value. Early filters learn edges and textures; deeper filters learn clothing shapes.

### ReLU

$$ReLU(x)=max(0,x)$$

Negative values become zero. ReLU adds nonlinearity so the model can learn complex patterns.

### Batch normalization

$$\mu_B=\frac{1}{m}\sum_i x_i$$

$$\sigma_B^2=\frac{1}{m}\sum_i(x_i-\mu_B)^2$$

$$\hat{x}_i=\frac{x_i-\mu_B}{\sqrt{\sigma_B^2+\epsilon}},\qquad y_i=\gamma\hat{x}_i+\beta$$

Batch normalization stabilizes activations and often speeds training.

### Max pooling

For each 2×2 region, the largest value is kept. This reduces spatial dimensions by about half while preserving strong features.

### Global average pooling

Each 10×10 feature map is averaged:

$$g_k=\frac{1}{10\times10}\sum_{i=1}^{10}\sum_{j=1}^{10}x_{i,j,k}$$

This changes `10 × 10 × 256` into `256` values.

### Dense feature vector

$$z=Wx+b,\qquad a=max(0,z)$$

The 256 outputs summarize the visual content of the product. This vector is later called an embedding.

### Dropout

With `Dropout(0.4)`, about 40% of activations are disabled during training:

$$\tilde{h}_i=m_i h_i,\qquad m_i\sim Bernoulli(0.6)$$

Dropout helps prevent overfitting and is disabled during prediction.

### Softmax

Softmax converts class scores into probabilities:

$$p_i=\frac{e^{z_i}}{\sum_{j=1}^{C}e^{z_j}}$$

The predicted class is `argmax(p)`, the class with the largest probability.

## 6. Loss and optimization

```python
model.compile(optimizer=Adam(learning_rate=0.001),
              loss='categorical_crossentropy', metrics=['accuracy'])
```

Categorical cross-entropy is:

$$L=-\sum_{i=1}^{C}y_i\log(p_i)$$

Because the true label is one-hot encoded, this becomes `L = -log(p_correct)`. If the correct probability is 0.9, loss is about 0.105; if it is 0.1, loss is about 2.303.

Accuracy is:

$$Accuracy=\frac{correct\ predictions}{total\ predictions}$$

Adam uses the gradient `g_t = ∇L_t`, maintains moving averages of gradients, and updates weights using:

$$\theta_{t+1}=\theta_t-\alpha\frac{\hat m_t}{\sqrt{\hat v_t}+\epsilon}$$

The initial learning rate is `α = 0.001`.

## 7. One training step

1. Load 32 images.
2. Resize, normalize, and augment them.
3. Run a forward pass through the CNN.
4. Produce class probabilities.
5. Calculate cross-entropy loss.
6. Use backpropagation to calculate gradients.
7. Adam updates the weights.

Backpropagation uses the chain rule:

$$\frac{\partial L}{\partial w}=\frac{\partial L}{\partial \hat y}\frac{\partial \hat y}{\partial w}$$

The gradient travels backward through softmax, dense layers, pooling, and convolutions.

## 8. Callbacks and model selection

`EarlyStopping(monitor='val_loss', patience=8)` stops when validation loss does not improve for 8 epochs and restores the best weights.

`ModelCheckpoint(..., save_best_only=True)` saves the best model to `best_model_v2.h5`.

`ReduceLROnPlateau(factor=0.5, patience=3)` halves the learning rate when validation loss stops improving for 3 epochs.

Training runs for at most 40 epochs. Approximate steps per epoch are:

$$steps=\left\lceil\frac{training\ images}{32}\right\rceil$$

## 9. Feature extraction

The Functional API rebuilds the same architecture and keeps the output of the `feature_vector` layer:

```python
feature_extractor = models.Model(inputs=inputs, outputs=feat)
```

The classifier does `image → class probabilities`; the feature extractor does `image → 256-dimensional embedding`.

## 10. Generate and save embeddings

For every product, the image is normalized, given a batch dimension, and passed through the extractor:

```python
arr = img_to_array(img) / 255.0
arr = np.expand_dims(arr, axis=0)
vec = feature_extractor.predict(arr, verbose=0)[0]
embeddings[row['image']] = vec.tolist()
```

The outputs are saved as:

```text
best_model_v2.h5          Full classifier
feature_extractor_v2.h5   Model returning 256 features
product_embeddings.json   Product vectors
class_indices.json        Class-name/index mapping
```

## 11. Similar-product search

Generate an embedding for a query image and compare it with every stored embedding.

Euclidean distance:

$$d(a,b)=\sqrt{\sum_i(a_i-b_i)^2}$$

Cosine similarity:

$$cosine(a,b)=\frac{a\cdot b}{||a||||b||}$$

Smaller Euclidean distance or larger cosine similarity indicates a more visually similar product.

## 12. Important notes

1. The embeddings are learned indirectly from classification; this is not a triplet-loss or similarity-loss model.
2. Use identical class ordering in the training and validation generators to avoid label-index mismatches.
3. A separate untouched test set gives a stronger final performance estimate.
4. These embeddings mainly represent features useful for article-type classification and may not perfectly represent brand, color, or personal style.
5. Save outputs under `/content/drive/MyDrive/` if they must survive a Colab runtime reset.
