<?php

use App\Interfaces\Http\Controllers\Api\V1\AuditController;
use App\Interfaces\Http\Controllers\Api\V1\ComplianceController;
use App\Interfaces\Http\Controllers\Api\V1\DictionaryController;
use App\Interfaces\Http\Controllers\Api\V1\DocumentAnalysisController;
use App\Interfaces\Http\Controllers\Api\V1\DocumentController;
use App\Interfaces\Http\Controllers\Api\V1\DocumentGenerationController;
use App\Interfaces\Http\Controllers\Api\V1\IssueController;
use App\Interfaces\Http\Controllers\Api\V1\NotificationController;
use App\Interfaces\Http\Controllers\Api\V1\OrganizationInvitationController;
use App\Interfaces\Http\Controllers\Api\V1\OrganizationProfileController;
use App\Interfaces\Http\Controllers\Api\V1\TaskController;
use Interfaces\Http\Controllers\Api\V1\AuthController;
use App\Interfaces\Http\Controllers\Api\V1\OrganizationController;
use App\Interfaces\Http\Controllers\Api\V1\OrganizationMemberController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    /*
     * Аутентификация
     */
    Route::prefix('auth')->group(function () {
        Route::post('login', [AuthController::class, 'login']);
        Route::post('register', [AuthController::class, 'register']);
        Route::post('verify-registration', [AuthController::class, 'verifyRegistration']);
        Route::post('resend-verification', [AuthController::class, 'resendVerificationCode']);
        Route::post('refresh', [AuthController::class, 'refresh']);
        Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
        Route::post('reset-password', [AuthController::class, 'resetPassword']);

        Route::middleware('auth:sanctum')->group(function () {
            Route::post('logout', [AuthController::class, 'logout']);
            Route::get('me', [AuthController::class, 'me']);
            Route::post('change-email', [AuthController::class, 'changeEmail']);
            Route::post('verify-email-change', [AuthController::class, 'verifyEmailChange']);
            Route::post('change-password', [AuthController::class, 'changePassword']);

            Route::get('sessions', [AuthController::class, 'sessions']);
            Route::delete('sessions/{sessionId}', [AuthController::class, 'terminateSession']);
            Route::delete('sessions', [AuthController::class, 'terminateAllSessions']);
        });
    });

    /*
     * Справочник по типам и индустрии
     */
    Route::prefix('dictionaries')->group(function () {
        Route::get('organization-types', [DictionaryController::class, 'organizationTypes']);
        Route::get('industries', [DictionaryController::class, 'industries']);
        Route::get('document-types', [DictionaryController::class, 'documentTypes']);
    });

    /*
     * Защищённые маршруты
     */
    Route::middleware('auth:sanctum')->group(function () {

        Route::get('ping', function () {
            return response()->json([
                'message' => 'pong',
            ]);
        });

        /*
         * Организации
         */
        Route::prefix('organizations')->group(function () {

            // Список организаций текущего пользователя
            Route::get('/', [OrganizationController::class, 'index']);

            // Создание организации
            Route::post('/', [OrganizationController::class, 'store']);

            /*
             * Маршруты конкретной организации.
             *
             * middleware organization.access:
             * - проверяет, что пользователь состоит в организации;
             * - кладёт организацию и роль в request attributes.
             */
            Route::prefix('{organizationId}')->middleware('organization.access')->group(function () {

                // Просмотр организации
                Route::get('/', [OrganizationController::class, 'show']);

                /*
                 * Управление организацией
                 *
                 * middleware organization.manage:
                 * - разрешает только owner/admin
                 */
                Route::middleware('organization.manage')->group(function () {
                    Route::put('/', [OrganizationController::class, 'update']);
                    Route::delete('/', [OrganizationController::class, 'destroy']);
                });

                Route::post('leave', [OrganizationController::class, 'leave']);

                /*
                 * Участники организации
                 */
                Route::get('members', [OrganizationMemberController::class, 'index']);

                /*
                 * Управление участниками
                 *
                 * middleware organization.members.manage:
                 * - разрешает только owner/admin
                 */
                Route::middleware('organization.members.manage')->group(function () {
                    Route::post('members', [OrganizationMemberController::class, 'store']);
                    Route::put('members/{userId}', [OrganizationMemberController::class, 'update']);
                    Route::delete('members/{userId}', [OrganizationMemberController::class, 'destroy']);
                });
                /*
                 * Документы
                 */
                Route::prefix('documents')->group(function () {

                    Route::get('/', [DocumentController::class, 'index']);

                    Route::middleware('organization.documents.upload')->group(function () {
                        Route::post('/', [DocumentController::class, 'store']);
                    });

                    Route::prefix('{documentId}')->middleware('document.access')->group(function () {

                        Route::get('/', [DocumentController::class, 'show']);
                        Route::delete('/', [DocumentController::class, 'destroy']);

                        Route::middleware('organization.documents.upload')->group(function () {
                            Route::post('upload', [DocumentController::class, 'upload']);
                        });

                        Route::middleware('organization.documents.analyze')->group(function () {
                            Route::post('analyze', [DocumentAnalysisController::class, 'analyze']);
                        });

                        Route::get('download', [DocumentController::class, 'download']);
                        Route::get('analysis', [DocumentAnalysisController::class, 'analysis']);
                        Route::get('issues', [DocumentAnalysisController::class, 'issues']);
                    });
                });
                Route::prefix('profile')->group(function () {
                    Route::get('/', [OrganizationProfileController::class, 'show']);

                    Route::middleware('organization.manage')->group(function () {
                        Route::put('/', [OrganizationProfileController::class, 'update']);
                    });
                });

                Route::get('required-documents', [OrganizationProfileController::class, 'requiredDocuments']);
                Route::get('missing-documents', [OrganizationProfileController::class, 'missingDocuments']);

                Route::prefix('generation')->group(function () {
                    Route::get('templates', [DocumentGenerationController::class, 'templates']);

                    Route::middleware('organization.documents.upload')->group(function () {
                        Route::post('/', [DocumentGenerationController::class, 'store']);
                    });

                    Route::get('runs', [DocumentGenerationController::class, 'index']);
                    Route::get('runs/{generationRunId}', [DocumentGenerationController::class, 'show']);
                });

                // Замечания организации
                Route::get('issues', [IssueController::class, 'index']);

                Route::middleware('organization.documents.upload')->group(function () {
                    Route::post('issues/bulk', [IssueController::class, 'bulkUpdate']);
                });

                // Замечания конкретного документа
                Route::prefix('documents/{documentId}')->middleware('document.access')->group(function () {

                    Route::get('issues', [IssueController::class, 'listForDocument']);

                    Route::prefix('issues/{issueId}')->group(function () {

                        Route::get('/', [IssueController::class, 'show']);

                        Route::middleware('organization.documents.upload')->group(function () {
                            Route::patch('status', [IssueController::class, 'updateStatus']);
                            Route::post('comments', [IssueController::class, 'addComment']);
                            Route::delete('comments/{commentId}', [IssueController::class, 'deleteComment']);
                        });

                        Route::get('comments', [IssueController::class, 'listComments']);
                        Route::get('history', [IssueController::class, 'listHistory']);
                    });
                });

                Route::prefix('tasks')->group(function () {
                    Route::get('/', [TaskController::class, 'index']);
                    Route::get('stats', [TaskController::class, 'stats']);
                    Route::get('my', [TaskController::class, 'myTasks']);

                    Route::middleware('organization.documents.upload')->group(function () {
                        Route::post('/', [TaskController::class, 'store']);
                        Route::post('from-issue', [TaskController::class, 'createFromIssue']);
                    });

                    Route::prefix('{taskId}')->group(function () {

                        Route::get('/', [TaskController::class, 'show']);
                        Route::middleware('organization.documents.upload')->group(function () {
                            Route::patch('/', [TaskController::class, 'update']);
                            Route::patch('status', [TaskController::class, 'updateStatus']);
                            Route::post('assign', [TaskController::class, 'assign']);
                            Route::post('comments', [TaskController::class, 'addComment']);
                            Route::delete('comments/{commentId}', [TaskController::class, 'deleteComment']);
                            Route::delete('/', [TaskController::class, 'destroy']);
                        });

                        Route::get('comments', [TaskController::class, 'listComments']);
                    });

                });

                Route::prefix('compliance')->group(function () {
                    Route::get('dashboard', [ComplianceController::class, 'dashboard']);
                    Route::get('summary', [ComplianceController::class, 'summary']);
                });

                Route::prefix('audit')->group(function () {
                    Route::get('/', [AuditController::class, 'index']);
                    Route::get('{auditLogId}', [AuditController::class, 'show']);
                    Route::get('user/{userId}', [AuditController::class, 'userActions']);

                    Route::middleware('organization.owner')->group(function () {
                        Route::delete('/', [AuditController::class, 'clear']);
                    });
                });

                // Приглашения
                Route::prefix('invitations')->group(function () {
                    Route::get('/', [OrganizationInvitationController::class, 'index']);

                    Route::middleware('organization.members.manage')->group(function () {
                        Route::post('/', [OrganizationInvitationController::class, 'store']);
                        Route::delete('{invitationId}', [OrganizationInvitationController::class, 'destroy']);
                    });
                });
            });
        });
        Route::prefix('notifications')->group(function () {
            Route::get('/', [NotificationController::class, 'index']);
            Route::get('unread-count', [NotificationController::class, 'unreadCount']);
            Route::get('settings', [NotificationController::class, 'getSettings']);
            Route::put('settings', [NotificationController::class, 'updateSettings']);
            Route::post('mark-all-read', [NotificationController::class, 'markAllAsRead']);
            Route::delete('all', [NotificationController::class, 'destroyAll']);
            Route::post('{notificationId}/read', [NotificationController::class, 'markAsRead']);
            Route::delete('{notificationId}', [NotificationController::class, 'destroy']);
        });
        Route::post('invitations/{token}/accept', [OrganizationInvitationController::class, 'accept']);
    });
});
