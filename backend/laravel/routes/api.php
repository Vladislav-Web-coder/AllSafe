<?php

use App\Interfaces\Http\Controllers\Api\V1\DictionaryController;
use App\Interfaces\Http\Controllers\Api\V1\DocumentAnalysisController;
use App\Interfaces\Http\Controllers\Api\V1\DocumentController;
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
        Route::post('refresh', [AuthController::class, 'refresh']);

        Route::middleware('auth:sanctum')->group(function () {
            Route::post('logout', [AuthController::class, 'logout']);
            Route::get('me', [AuthController::class, 'me']);
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
                });

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
            });
        });
    });
});
