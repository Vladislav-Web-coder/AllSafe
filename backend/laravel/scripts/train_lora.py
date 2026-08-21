from unsloth import FastLanguageModel
from transformers import TrainingArguments
from trl import SFTTrainer

# Загружаем модель
model, tokenizer = FastLanguageModel.from_pretrained(
    model_name="Qwen/Qwen2.5-14B-Instruct",
    max_seq_length=4096,
    load_in_4bit=True,
)

# Добавляем LoRA adapters
model = FastLanguageModel.get_peft_model(
    model,
    r=16,
    target_modules=["q_proj", "k_proj", "v_proj", "o_proj", "gate_proj", "up_proj", "down_proj"],
    lora_alpha=16,
    lora_dropout=0,
    bias="none",
    use_gradient_checkpointing="unsloth",
)

# Загружаем датасет
from datasets import load_dataset
dataset = load_dataset("json", data_files="storage/app/training_unsloth.json")

# Настраиваем обучение
trainer = SFTTrainer(
    model=model,
    tokenizer=tokenizer,
    train_dataset=dataset["train"],
    dataset_text_field="messages",
    max_seq_length=4096,
    args=TrainingArguments(
        per_device_train_batch_size=2,
        gradient_accumulation_steps=4,
        warmup_steps=5,
        max_steps=100,
        learning_rate=2e-4,
        fp16=True,
        logging_steps=1,
        optim="adamw_8bit",
        weight_decay=0.01,
        lr_scheduler_type="linear",
        seed=3407,
        output_dir="outputs",
    ),
)

# Обучаем
trainer.train()

# Сохраняем адаптеры
model.save_pretrained("lora_model")
tokenizer.save_pretrained("lora_model")
