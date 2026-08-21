# Дообучение LLM для анализа документов

## Обзор

Дообучаем модель для улучшения:
1. **Структурированного вывода** — всегда валидный JSON
2. **Юридического стиля** — корректные формулировки на русском
3. **Точности ссылок на НПА** — правильные статьи из контекста RAG

## Процесс дообучения

### 1. Сбор датасета

```bash
# Собираем примеры из успешных анализов
./vendor/bin/sail artisan ai:collect-training-data \
    --limit=200 \
    --min-score=70 \
    --output=training_data.jsonl

# Валидируем датасет
./vendor/bin/sail artisan ai:validate-training-data training_data.jsonl
