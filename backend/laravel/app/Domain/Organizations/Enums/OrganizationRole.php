<?php

namespace App\Domain\Organizations\Enums;

enum OrganizationRole: string
{
    case Owner = 'owner';
    case Admin = 'admin';
    case SecurityOfficer = 'security_officer';
    case LegalOfficer = 'legal_officer';
    case Auditor = 'auditor';
    case Employee = 'employee';
    case Viewer = 'viewer';

    public function label(): string
    {
        return match ($this) {
            self::Owner => 'Владелец',
            self::Admin => 'Администратор',
            self::SecurityOfficer => 'Специалист по информационной безопасности',
            self::LegalOfficer => 'Юрист',
            self::Auditor => 'Аудитор',
            self::Employee => 'Сотрудник',
            self::Viewer => 'Наблюдатель',
        };
    }

    public function canManageOrganization(): bool
    {
        return in_array($this, [
            self::Owner,
            self::Admin,
        ]);
    }

    public function canManageMembers(): bool
    {
        return in_array($this, [
            self::Owner,
            self::Admin,
        ]);
    }

    public function canViewOrganization(): bool
    {
        return true;
    }

    public function canViewDocuments(): bool
    {
        return true;
    }

    public function canUploadDocuments(): bool
    {
        return in_array($this, [
            self::Owner,
            self::Admin,
            self::SecurityOfficer,
            self::LegalOfficer,
        ]);
    }

    public function canAnalyzeDocuments(): bool
    {
        return in_array($this, [
            self::Owner,
            self::Admin,
            self::SecurityOfficer,
            self::LegalOfficer,
        ]);
    }

    public function canDeleteDocuments(): bool
    {
        return in_array($this, [
            self::Owner,
            self::Admin,
        ]);
    }

    public function canRunGeneration(): bool
    {
        return in_array($this, [
            self::Owner,
            self::Admin,
            self::SecurityOfficer,
            self::LegalOfficer,
        ]);
    }

    public function canUseAssistant(): bool
    {
        return in_array($this, [
            self::Owner,
            self::Admin,
            self::SecurityOfficer,
            self::LegalOfficer,
            self::Auditor,
            self::Employee,
        ]);
    }

    public function canViewReports(): bool
    {
        return in_array($this, [
            self::Owner,
            self::Admin,
            self::SecurityOfficer,
            self::LegalOfficer,
            self::Auditor,
        ]);
    }

    public function canViewAudit(): bool
    {
        return in_array($this, [
            self::Owner,
            self::Admin,
            self::SecurityOfficer,
            self::Auditor,
        ]);
    }
}
