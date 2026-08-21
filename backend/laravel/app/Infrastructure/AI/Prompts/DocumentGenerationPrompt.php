<?php

namespace App\Infrastructure\AI\Prompts;

use App\Domain\Generation\Entities\DocumentTemplate;
use App\Domain\Organizations\Entities\Organization;
use App\Domain\Profiles\Entities\OrganizationProfile;

class DocumentGenerationPrompt
{
    /**
     * Максимальный размер контекста НПА в символах.
     * Ограничиваем, чтобы не превысить контекст LLM.
     */
    private const MAX_LEGAL_CONTEXT_CHARS = 4000;

    /**
     * Максимальная длина одного фрагмента НПА.
     */
    private const MAX_CHUNK_CHARS = 600;

    public static function build(
        DocumentTemplate $template,
        Organization $organization,
        ?OrganizationProfile $profile,
        array $legalContext = [],
    ): string {
        $parts = [];

        // === СИСТЕМНАЯ РОЛЬ ===
        $parts[] = self::buildRoleSection();

        // === ИНФОРМАЦИЯ ОБ ОРГАНИЗАЦИИ ===
        $parts[] = self::buildOrganizationSection($organization, $profile);

        // === КОНТЕКСТ НПА ===
        $legalSection = self::buildLegalContextSection($legalContext);
        if ($legalSection !== '') {
            $parts[] = $legalSection;
        }

        // === ШАБЛОН ДОКУМЕНТА ===
        $parts[] = self::buildTemplateSection($template);

        // === СПЕЦИФИЧНЫЕ ИНСТРУКЦИИ ПО ТИПУ ДОКУМЕНТА ===
        $typeInstructions = self::buildTypeSpecificInstructions($template->code);
        if ($typeInstructions !== '') {
            $parts[] = $typeInstructions;
        }

        // === ОБЩИЕ ТРЕБОВАНИЯ ===
        $parts[] = self::buildRequirementsSection();

        // === ФОРМАТ ОТВЕТА ===
        $parts[] = self::buildFormatSection();

        return implode("\n\n", $parts);
    }

    private static function buildRoleSection(): string
    {
        return <<<TEXT
Ты — эксперт-юрист по compliance и защите персональных данных в Российской Федерации с 15-летним опытом разработки локальных нормативных актов.

Твоя задача — создать профессиональный юридический документ, который:
- Полностью соответствует действующему законодательству РФ
- Учитывает специфику конкретной организации
- Содержит конкретные ссылки на нормы закона
- Готов к использованию без существенных доработок
TEXT;
    }

    private static function buildOrganizationSection(
        Organization $organization,
        ?OrganizationProfile $profile,
    ): string {
        $lines = ["## ОРГАНИЗАЦИЯ\n"];

        $lines[] = "- Название: {$organization->name}";

        if ($organization->legal_name && $organization->legal_name !== $organization->name) {
            $lines[] = "- Полное наименование: {$organization->legal_name}";
        }

        if ($organization->inn) {
            $lines[] = "- ИНН: {$organization->inn}";
        }

        if ($organization->type?->name) {
            $lines[] = "- Тип организации: {$organization->type->name}";
        }

        if ($organization->industry?->name) {
            $lines[] = "- Отрасль: {$organization->industry->name}";
        }

        if ($profile) {
            $lines[] = "";
            $lines[] = "### Характеристики обработки данных";

            if ($profile->processes_personal_data) {
                $lines[] = "- Является оператором персональных данных";
            }

            if ($profile->subjects_count) {
                $lines[] = "- Количество субъектов ПДн: " . number_format($profile->subjects_count, 0, '.', ' ');
            }

            if ($profile->protection_level) {
                $lines[] = "- Уровень защищённости: {$profile->protection_level}";
            }

            if (! empty($profile->data_categories)) {
                $labels = self::getDataCategoryLabels($profile->data_categories);
                if (! empty($labels)) {
                    $lines[] = "- Категории субъектов: " . implode(', ', $labels);
                }
            }

            if (! empty($profile->special_data_categories)) {
                $labels = self::getSpecialCategoryLabels($profile->special_data_categories);
                if (! empty($labels)) {
                    $lines[] = "- Специальные категории: " . implode(', ', $labels);
                }
            }

            $flags = [];
            if ($profile->has_website) $flags[] = 'имеет сайт';
            if ($profile->has_gis) $flags[] = 'эксплуатирует ГИС';
            if ($profile->has_kii) $flags[] = 'имеет объекты КИИ';
            if ($profile->has_asutp) $flags[] = 'имеет АСУ ТП';
            if ($profile->uses_cloud) $flags[] = 'использует облачные сервисы';
            if ($profile->has_contractors) $flags[] = 'привлекает подрядчиков';
            if ($profile->has_cross_border_transfer) $flags[] = 'осуществляет трансграничную передачу';

            if (! empty($flags)) {
                $lines[] = "- Особенности: " . implode(', ', $flags);
            }
        }

        return implode("\n", $lines);
    }

    private static function buildLegalContextSection(array $legalContext): string
    {
        if (empty($legalContext)) {
            return '';
        }

        $lines = [];
        $lines[] = "## ПРИМЕНИМЫЕ НОРМЫ ЗАКОНОДАТЕЛЬСТВА";
        $lines[] = "";
        $lines[] = "При составлении документа опирайся на следующие нормы. Цитируй их точно и указывай конкретные статьи, части, пункты.";
        $lines[] = "";

        $totalChars = 0;

        foreach ($legalContext as $chunk) {
            $reference = $chunk['reference'] ?? 'НПА';
            $content = mb_substr(trim($chunk['content'] ?? ''), 0, self::MAX_CHUNK_CHARS);

            if (empty($content)) {
                continue;
            }

            $entry = "[{$reference}]\n{$content}";

            // Проверяем, не превышаем ли лимит
            if ($totalChars + mb_strlen($entry) > self::MAX_LEGAL_CONTEXT_CHARS) {
                break;
            }

            $lines[] = $entry;
            $lines[] = "";
            $totalChars += mb_strlen($entry);
        }

        return implode("\n", $lines);
    }

    private static function buildTemplateSection(DocumentTemplate $template): string
    {
        $lines = [];
        $lines[] = "## ШАБЛОН ДОКУМЕНТА";
        $lines[] = "";
        $lines[] = "Название: {$template->name}";

        if ($template->description) {
            $lines[] = "Назначение: {$template->description}";
        }

        if (! empty($template->required_sections_json)) {
            $lines[] = "";
            $lines[] = "Документ ОБЯЗАТЕЛЬНО должен содержать следующие разделы (в указанном порядке):";
            foreach ($template->required_sections_json as $i => $section) {
                $lines[] = ($i + 1) . ". {$section}";
            }
        }

        if (! empty($template->generation_prompt)) {
            $lines[] = "";
            $lines[] = "Дополнительные инструкции к шаблону:";
            $lines[] = $template->generation_prompt;
        }

        return implode("\n", $lines);
    }

    /**
     * Специфичные инструкции для разных типов документов.
     */
    private static function buildTypeSpecificInstructions(string $templateCode): string
    {
        return match ($templateCode) {
            'pd_policy' => self::pdPolicyInstructions(),
            'consent_form' => self::consentFormInstructions(),
            'security_policy' => self::securityPolicyInstructions(),
            'privacy_policy' => self::privacyPolicyInstructions(),
            'notification' => self::notificationInstructions(),
            default => '',
        };
    }

    private static function pdPolicyInstructions(): string
    {
        return <<<TEXT
## СПЕЦИФИКА: ПОЛИТИКА ОБРАБОТКИ ПДн

Документ должен соответствовать требованиям:
- ст. 18.1 152-ФЗ (обязанности оператора, включая публикацию политики)
- ст. 5 152-ФЗ (принципы обработки)
- ст. 6 152-ФЗ (условия обработки)
- ст. 19 152-ФЗ (меры безопасности)

Обязательно включи:
1. Общие положения с указанием правовых оснований
2. Основные понятия и определения (из ст. 3 152-ФЗ)
3. Цели обработки ПДн — конкретные, для данной организации
4. Перечень обрабатываемых ПДн по категориям субъектов
5. Правовые основания обработки (ссылки на конкретные пункты ст. 6)
6. Порядок и условия обработки (сбор, хранение, передача, уничтожение)
7. Сроки обработки и хранения
8. Меры защиты (со ссылкой на ст. 19 и ПП РФ № 1119)
9. Права субъектов ПДн (ст. 14-17 152-ФЗ)
10. Порядок обращения субъектов и сроки ответов (ст. 20)
11. Ответственное лицо за организацию обработки (ст. 22.1)
12. Ответственность за нарушение
13. Заключительные положения
TEXT;
    }

    private static function consentFormInstructions(): string
    {
        return <<<TEXT
## СПЕЦИФИКА: СОГЛАСИЕ НА ОБРАБОТКУ ПДн

Документ должен строго соответствовать ст. 9 152-ФЗ.

Для письменного согласия (ч. 4 ст. 9) обязательно укажи:
1. ФИО, адрес субъекта, данные документа
2. Данные представителя (если применимо)
3. Наименование и адрес оператора
4. Цель обработки
5. Перечень ПДн, на которые даётся согласие
6. Лицо, обрабатывающее по поручению (если есть)
7. Перечень действий с ПДн
8. Срок действия согласия и порядок отзыва
9. Подпись субъекта

Важно:
- Согласие должно быть конкретным, предметным, информированным, сознательным и однозначным (ч. 1 ст. 9)
- Для биометрических данных — только письменная форма (ст. 11)
- Для специальных категорий — дополнительные требования (ст. 10)
- Укажи право отзыва согласия (ч. 2 ст. 9)
TEXT;
    }

    private static function securityPolicyInstructions(): string
    {
        return <<<TEXT
## СПЕЦИФИКА: ПОЛИТИКА БЕЗОПАСНОСТИ

Документ должен учитывать:
- ст. 19 152-ФЗ (меры безопасности)
- ПП РФ № 1119 (требования к защите)
- Приказ ФСТЭК № 21 (меры защиты ПДн)
- Приказ ФСТЭК № 17 (для ГИС, если применимо)

Включи разделы:
1. Область применения и нормативные ссылки
2. Уровни защищённости ПДн (согласно ПП № 1119)
3. Организационные меры защиты
4. Технические меры защиты (по Приказу ФСТЭК № 21)
5. Управление доступом
6. Защита при передаче данных
7. Антивирусная защита и обнаружение вторжений
8. Резервное копирование
9. Реагирование на инциденты
10. Контроль и аудит безопасности
TEXT;
    }

    private static function privacyPolicyInstructions(): string
    {
        return <<<TEXT
## СПЕЦИФИКА: ПОЛИТИКА КОНФИДЕНЦИАЛЬНОСТИ (для сайта)

Документ должен соответствовать:
- ст. 18.1 152-ФЗ (публикация на сайте обязательна)
- ст. 10.1 152-ФЗ (если обрабатываются общедоступные данные)

Учитывай, что документ должен быть доступен неограниченному кругу лиц.
Излагай требования понятным языком для конечных пользователей.

Обязательные разделы:
1. Общие положения
2. Основные понятия
3. Перечень собираемых данных
4. Цели обработки
5. Правовые основания
6. Cookies и аналитика (если применимо)
7. Передача данных третьим лицам
8. Трансграничная передача (если применимо)
9. Сроки хранения
10. Права пользователя
11. Порядок отзыва согласия
12. Контактная информация оператора
TEXT;
    }

    private static function notificationInstructions(): string
    {
        return <<<TEXT
## СПЕЦИФИКА: УВЕДОМЛЕНИЕ ОБ ОБРАБОТКЕ ПДн

Документ должен соответствовать ст. 22 152-ФЗ.

Уведомление в Роскомнадзор должно содержать (ч. 3 ст. 22):
1. Наименование и адрес оператора
2. Цель обработки ПДн
3. Категории ПДн
4. Категории субъектов
5. Правовое основание обработки
6. Перечень действий с ПДн
7. Описание мер по ст. 18.1 и 19
8. ФИО ответственного за организацию обработки (ст. 22.1)
9. Дата начала обработки
10. Срок и условия прекращения обработки
11. Сведения об обеспечении безопасности (ПП РФ)
TEXT;
    }

    private static function buildRequirementsSection(): string
    {
        return <<<TEXT
## ОБЩИЕ ТРЕБОВАНИЯ

1. Язык: профессиональный юридический русский язык
2. Все обязательные разделы должны присутствовать и быть наполненными
3. Ссылки на НПА — в формате «статья 5 Федерального закона от 27.07.2006 № 152-ФЗ»
4. Не используй placeholder'ы ([ВСТАВЬТЕ ...], [ФИО], ...)
5. Для данных, специфичных для организации, используй информацию из раздела «ОРГАНИЗАЦИЯ»
6. Если данных для конкретного пункта не хватает — укажи общее положение со ссылкой на закон
7. Объём документа: не менее 3000 слов для политик, не менее 500 слов для форм
8. Не выдумывай факты об организации, которых нет в предоставленных данных
9. Не ссылайся на НПА, которых нет в разделе «ПРИМЕНИМЫЕ НОРМЫ»
TEXT;
    }

    private static function buildFormatSection(): string
    {
        return <<<TEXT
## ФОРМАТ ОТВЕТА

Верни ответ СТРОГО в формате валидного JSON без markdown-обёрток:

{"content":"полный текст документа в формате Markdown","sections":[{"title":"1. Общие положения","content":"текст раздела"},{"title":"2. ...","content":"..."}]}

Требования к JSON:
- content — полный текст документа с заголовками ##
- sections — массив всех разделов с title и content
- Начни ответ сразу с { и закончи }
- Не добавляй пояснений до и после JSON
TEXT;
    }

    private static function getDataCategoryLabels(array $categories): array
    {
        $labels = [
            'employees' => 'сотрудники',
            'clients' => 'клиенты',
            'patients' => 'пациенты',
            'students' => 'студенты/учащиеся',
            'children' => 'несовершеннолетние',
            'partners' => 'контрагенты',
        ];

        return array_values(array_filter(
            array_map(fn ($c) => $labels[$c] ?? null, $categories)
        ));
    }

    private static function getSpecialCategoryLabels(array $categories): array
    {
        $labels = [
            'health' => 'состояние здоровья',
            'biometric' => 'биометрические данные',
            'criminal' => 'сведения о судимости',
            'racial' => 'расовая/национальная принадлежность',
            'political' => 'политические взгляды',
            'religious' => 'религиозные убеждения',
        ];

        return array_values(array_filter(
            array_map(fn ($c) => $labels[$c] ?? null, $categories)
        ));
    }
}
