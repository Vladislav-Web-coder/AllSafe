from fastapi import FastAPI, HTTPException
from pydantic import BaseModel, Field
from sentence_transformers import SentenceTransformer
from typing import List
import logging
import os

logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

app = FastAPI(
    title="Embedding Service",
    description="Сервис для создания векторных представлений текста",
    version="1.0.0"
)

# Модель можно переопределить через переменную окружения
MODEL_NAME = os.getenv(
    "EMBEDDING_MODEL",
    "sentence-transformers/paraphrase-multilingual-MiniLM-L12-v2"
)

logger.info(f"Loading model: {MODEL_NAME}")

try:
    model = SentenceTransformer(MODEL_NAME)
    EMBEDDING_DIM = model.get_sentence_embedding_dimension()
    logger.info(f"Model loaded. Embedding dimension: {EMBEDDING_DIM}")
except Exception as e:
    logger.error(f"Failed to load model: {e}")
    model = None
    EMBEDDING_DIM = 0


class EmbedRequest(BaseModel):
    text: str = Field(..., min_length=1, max_length=50000)


class EmbedResponse(BaseModel):
    embedding: List[float]
    dimension: int


class BatchEmbedRequest(BaseModel):
    texts: List[str] = Field(..., min_length=1, max_length=50)


class BatchEmbedResponse(BaseModel):
    embeddings: List[List[float]]
    dimension: int


@app.get("/health")
def health():
    return {
        "status": "ok" if model else "error",
        "model": MODEL_NAME,
        "dimension": EMBEDDING_DIM,
    }


@app.post("/embed", response_model=EmbedResponse)
def embed(request: EmbedRequest):
    if model is None:
        raise HTTPException(status_code=503, detail="Model not loaded")

    try:
        embedding = model.encode(request.text, convert_to_numpy=True)
        return EmbedResponse(
            embedding=embedding.tolist(),
            dimension=len(embedding),
        )
    except Exception as e:
        logger.error(f"Embedding error: {str(e)}")
        raise HTTPException(status_code=500, detail=str(e))


@app.post("/embed_batch", response_model=BatchEmbedResponse)
def embed_batch(request: BatchEmbedRequest):
    if model is None:
        raise HTTPException(status_code=503, detail="Model not loaded")

    try:
        embeddings = model.encode(request.texts, convert_to_numpy=True)
        return BatchEmbedResponse(
            embeddings=[emb.tolist() for emb in embeddings],
            dimension=len(embeddings[0]) if len(embeddings) > 0 else 0,
        )
    except Exception as e:
        logger.error(f"Batch embedding error: {str(e)}")
        raise HTTPException(status_code=500, detail=str(e))


if __name__ == "__main__":
    import uvicorn
    uvicorn.run(app, host="0.0.0.0", port=8001)
