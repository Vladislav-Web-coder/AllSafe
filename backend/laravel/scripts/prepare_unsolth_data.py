# scripts/prepare_unsloth_data.py
import json

def convert_to_chatml(jsonl_file, output_file):
    with open(jsonl_file, 'r', encoding='utf-8') as f:
        lines = f.readlines()

    examples = []
    for line in lines:
        data = json.loads(line)

        example = {
            "messages": [
                {
                    "role": "system",
                    "content": "Ты — эксперт по compliance и защите персональных данных в РФ. Отвечай только валидным JSON."
                },
                {
                    "role": "user",
                    "content": data['input']
                },
                {
                    "role": "assistant",
                    "content": data['output']
                }
            ]
        }
        examples.append(example)

    with open(output_file, 'w', encoding='utf-8') as f:
        json.dump(examples, f, ensure_ascii=False, indent=2)

convert_to_chatml(
    'storage/app/training_data.jsonl',
    'storage/app/training_unsloth.json'
)
