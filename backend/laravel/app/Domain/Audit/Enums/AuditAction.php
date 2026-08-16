<?php

namespace App\Domain\Audit\Enums;

enum AuditAction: string
{
    // Auth
    case AuthLoginSuccess = 'auth.login.success';
    case AuthLoginFailed = 'auth.login.failed';
    case AuthLogout = 'auth.logout';
    case AuthRefresh = 'auth.refresh';

    // Organizations
    case OrganizationCreated = 'organization.created';
    case OrganizationUpdated = 'organization.updated';
    case MemberAdded = 'organization.member.added';
    case MemberRoleChanged = 'organization.member.role_changed';
    case MemberRemoved = 'organization.member.removed';

    // Profile
    case ProfileUpdated = 'profile.updated';

    // Documents
    case DocumentCreated = 'document.created';
    case DocumentFileUploaded = 'document.file.uploaded';
    case DocumentDeleted = 'document.deleted';
    case DocumentAnalysisStarted = 'document.analysis.started';
    case DocumentAnalysisCompleted = 'document.analysis.completed';
    case DocumentAnalysisFailed = 'document.analysis.failed';
    case DocumentGenerationStarted = 'document.generation.started';
    case DocumentGenerationCompleted = 'document.generation.completed';
    case DocumentGenerationFailed = 'document.generation.failed';

    // Issues
    case IssueStatusChanged = 'issue.status.changed';
    case IssueCommentAdded = 'issue.comment.added';
    case IssueBulkUpdated = 'issue.bulk.updated';

    // Tasks
    case TaskCreated = 'task.created';
    case TaskStatusChanged = 'task.status.changed';
    case TaskAssigned = 'task.assigned';
    case TaskCommentAdded = 'task.comment.added';
    case TaskDeleted = 'task.deleted';

    // Compliance
    case ComplianceDashboardViewed = 'compliance.dashboard.viewed';

    public function label(): string
    {
        return match ($this) {
            self::AuthLoginSuccess => 'Успешный вход',
            self::AuthLoginFailed => 'Неуспешный вход',
            self::AuthLogout => 'Выход',
            self::AuthRefresh => 'Обновление токена',

            self::OrganizationCreated => 'Создание организации',
            self::OrganizationUpdated => 'Обновление организации',
            self::MemberAdded => 'Добавление участника',
            self::MemberRoleChanged => 'Изменение роли участника',
            self::MemberRemoved => 'Удаление участника',

            self::ProfileUpdated => 'Обновление профиля',

            self::DocumentCreated => 'Создание документа',
            self::DocumentFileUploaded => 'Загрузка файла',
            self::DocumentDeleted => 'Удаление документа',
            self::DocumentAnalysisStarted => 'Запуск анализа',
            self::DocumentAnalysisCompleted => 'Анализ завершён',
            self::DocumentAnalysisFailed => 'Ошибка анализа',
            self::DocumentGenerationStarted => 'Запуск генерации',
            self::DocumentGenerationCompleted => 'Генерация завершена',
            self::DocumentGenerationFailed => 'Ошибка генерации',

            self::IssueStatusChanged => 'Смена статуса замечания',
            self::IssueCommentAdded => 'Комментарий к замечанию',
            self::IssueBulkUpdated => 'Массовое обновление замечаний',

            self::TaskCreated => 'Создание задачи',
            self::TaskStatusChanged => 'Смена статуса задачи',
            self::TaskAssigned => 'Назначение задачи',
            self::TaskCommentAdded => 'Комментарий к задаче',
            self::TaskDeleted => 'Удаление задачи',

            self::ComplianceDashboardViewed => 'Просмотр дашборда',
        };
    }
}
