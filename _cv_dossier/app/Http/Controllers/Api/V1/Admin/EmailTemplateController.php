<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\EmailTemplateRequest;
use App\Services\EmailTemplateService;
use App\Services\LanguageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmailTemplateController extends Controller
{
    public function __construct(
        private EmailTemplateService $emailTemplateService,
        private LanguageService $languageService
    ) {}

    /**
     * Get list of available template types
     *
     * @OA\Get(
     *     path="/api/v1/admin/email-templates/types",
     *     summary="Get available email template types",
     *     tags={"Admin - Email Templates"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of template types",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(type="string"))
     *         )
     *     )
     * )
     */
    public function types(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->emailTemplateService->getTemplateTypes(),
        ]);
    }

    /**
     * Get email template for specific type and language
     *
     * @OA\Get(
     *     path="/api/v1/admin/email-templates/{type}/{lang}",
     *     summary="Get email template",
     *     tags={"Admin - Email Templates"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="type",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string", example="user_reset_password")
     *     ),
     *     @OA\Parameter(
     *         name="lang",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string", example="en")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Email template retrieved",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="type", type="string"),
     *                 @OA\Property(property="lang", type="string"),
     *                 @OA\Property(property="subject", type="string"),
     *                 @OA\Property(property="message", type="string")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="Template not found")
     * )
     */
    public function show(string $type, string $lang): JsonResponse
    {
        $template = $this->emailTemplateService->getTemplate($type, $lang);

        if (empty($template['subject']) && empty($template['message'])) {
            return response()->json([
                'success' => false,
                'message' => 'Template not found or empty',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'type' => $type,
                'lang' => $lang,
                'subject' => $template['subject'],
                'message' => $template['message'],
            ],
        ]);
    }

    /**
     * Get all templates for a specific type across all languages
     *
     * @OA\Get(
     *     path="/api/v1/admin/email-templates/{type}",
     *     summary="Get email template for all languages",
     *     tags={"Admin - Email Templates"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="type",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string", example="user_reset_password")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Templates retrieved for all languages",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="type", type="string"),
     *                 @OA\Property(property="templates", type="object")
     *             )
     *         )
     *     )
     * )
     */
    public function showAll(string $type): JsonResponse
    {
        $languages = $this->languageService->getAllLanguages()->pluck('slug')->toArray();
        $templates = $this->emailTemplateService->getTemplateAllLanguages($type, $languages);

        return response()->json([
            'success' => true,
            'data' => [
                'type' => $type,
                'templates' => $templates,
            ],
        ]);
    }

    /**
     * Update email template
     *
     * @OA\Put(
     *     path="/api/v1/admin/email-templates/{type}/{lang}",
     *     summary="Update email template",
     *     tags={"Admin - Email Templates"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="type",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string", example="user_reset_password")
     *     ),
     *     @OA\Parameter(
     *         name="lang",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string", example="en")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"subject", "message"},
     *             @OA\Property(property="subject", type="string", example="Reset Your Password"),
     *             @OA\Property(property="message", type="string", example="Click the link to reset your password...")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Template updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=500, description="Update failed")
     * )
     */
    public function update(EmailTemplateRequest $request, string $type, string $lang): JsonResponse
    {
        $success = $this->emailTemplateService->updateTemplate(
            $type,
            $lang,
            $request->subject,
            $request->message
        );

        if (!$success) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update email template',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Email template updated successfully',
        ]);
    }

    /**
     * Batch update templates for multiple languages
     *
     * @OA\Post(
     *     path="/api/v1/admin/email-templates/{type}/batch",
     *     summary="Batch update email templates for multiple languages",
     *     tags={"Admin - Email Templates"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="type",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string", example="user_reset_password")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"templates"},
     *             @OA\Property(property="templates", type="object",
     *                 @OA\Property(property="en", type="object",
     *                     @OA\Property(property="subject", type="string"),
     *                     @OA\Property(property="message", type="string")
     *                 ),
     *                 @OA\Property(property="ar", type="object",
     *                     @OA\Property(property="subject", type="string"),
     *                     @OA\Property(property="message", type="string")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Templates updated",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="failed", type="array", @OA\Items(type="string"))
     *         )
     *     )
     * )
     */
    public function batchUpdate(Request $request, string $type): JsonResponse
    {
        $request->validate([
            'templates' => ['required', 'array'],
            'templates.*.subject' => ['required', 'string', 'max:191'],
            'templates.*.message' => ['required', 'string', 'max:5000'],
        ]);

        $result = $this->emailTemplateService->batchUpdateTemplates($type, $request->templates);

        return response()->json([
            'success' => $result['success'],
            'message' => $result['success'] 
                ? 'All templates updated successfully' 
                : 'Some templates failed to update',
            'failed' => $result['failed'],
        ], $result['success'] ? 200 : 207);
    }
}
