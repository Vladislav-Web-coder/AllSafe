<?php

namespace App\Infrastructure\AI\Prompts;

use App\Domain\Generation\Entities\DocumentTemplate;
use App\Domain\Organizations\Entities\Organization;
use App\Domain\Profiles\Entities\OrganizationProfile;

class DocumentGenerationPrompt
{
    public static function build(
        DocumentTemplate $template,
        Organization $organization,
        ?OrganizationProfile $profile,
    ): string {
        $systemPrompt = self::system();
        $userPrompt = self::user($template, $organization, $profile);

        return $systemPrompt . "\n\n" . $userPrompt;
    }

    public static function system(): string
    {
        return <<<TEXT
Ты — ассистент по генерации документов в области информационной безопасности и защиты персональных данных.

Твоя задача:
1. Сгенерировать полный текст документа на основе предоставленного шаблона.
2. Использовать данные организации для персонализации документа.
3. Включить ВСЕ обязательные разделы, указанные в шаблоне.
4. Писать формальным юридическим языком.
5. Ссылаться на актуальные нормативные акты РФ.

ОБЯЗАТЕЛЬНО верни результат строго в формате JSON:
{
  "content": "Полный текст документа в формате Markdown",
  "sections": [
    {
      "title": "Название раздела",
      "content": "Подробное содержимое раздела. Минимум 3-5 предложений."
    }
  ]
}

ВАЖНО:
- Массив "sections" ОБЯЗАТЕЛЬНО должен содержать все разделы из списка обязательных
- Каждый раздел должен быть подробно расписан (минимум 3-5 предложений)
- Поле "content" должно содержать полный текст документа в Markdown
- Не используй placeholder'ы вроде [ВСТАВЬТЕ ТЕКСТ]
- Не пропускай разделы
TEXT;
    }

    public static function user(
        DocumentTemplate $template,
        Organization $organization,
        ?OrganizationProfile $profile,
    ): string {
        $organizationName = $organization->name ?? 'Организация';
        $legalName = $organization->legal_name ?? $organizationName;
        $inn = $organization->inn ?? 'не указан';

        $profileInfo = '';
        if ($profile) {
            $profileInfo = self::buildProfileInfo($profile);
        }

        $sections = implode("\n", $template->required_sections_json ?? []);

        return <<<TEXT
Сгенерируй документ по следующему шаблону.

Данные организации:
- Название: {$organizationName}
- Полное наименование: {$legalName}
- ИНН: {$inn}
- Отрасль: {$organization->industry}

Данные профиля организации:
{$profileInfo}

Тип документа: {$template->name}
Описание: {$template->description}

Обязательные разделы:
{$sections}

Инструкция по генерации:
{$template->generation_prompt}

Сгенерируй полный текст документа со всеми обязательными разделами.
TEXT;
    }

    private static function buildProfileInfo(OrganizationProfile $profile): string
    {
        $lines = [];

        if ($profile->processes_personal_data) {
            $lines[] = '- Обрабатывает персональные данные: да';
        }

        if ($profile->has_website) {
            $lines[] = '- Имеет сайт: да';
        }

        if ($profile->has_gis) {
            $lines[] = '- Имеет государственные ИС: да';
        }

        if ($profile->has_kii) {
            $lines[] = '- Имеет объекты КИИ: да';
        }

        if ($profile->has_asutp) {
            $lines[] = '- Имеет АСУ ТП: да';
        }

        if ($profile->uses_cloud) {
            $lines[] = '- Использует облачные сервисы: да';
        }

        if ($profile->has_contractors) {
            $lines[] = '- Работает с подрядчиками: да';
        }

        if (! empty($profile->data_categories)) {
            $categories = implode(', ', $profile->data_categories);
            $lines[] = "- Категории данных: {$categories}";
        }

        if (! empty($profile->special_data_categories)) {
            $specialCategories = implode(', ', $profile->special_data_categories);
            $lines[] = "- Специальные категории ПДн: {$specialCategories}";
        }

        if ($profile->subjects_count) {
            $lines[] = "- Количество субъектов ПДн: {$profile->subjects_count}";
        }

        if ($profile->protection_level) {
            $lines[] = "- Уровень защищённости: {$profile->protection_level}";
        }

        return empty($lines) ? '- Профиль не заполнен' : implode("\n", $lines);
    }
}
