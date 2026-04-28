<?php

declare(strict_types=1);

namespace Modules\RealEstate\Http\Controllers\Frontend;

use Modules\RealEstate\Http\Controllers\BaseController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\RealEstate\Services\MortgageCalculatorService;
use OpenApi\Attributes as OA;
use Illuminate\Support\Facades\Log;

#[OA\Tag(name: 'Mortgage Calculator', description: 'Mortgage and financing calculations')]
class MortgageCalculatorController extends BaseController
{
    public function __construct(
        protected MortgageCalculatorService $mortgageService,
        protected \Modules\RealEstate\Services\PropertyService $propertyService
    ) {}

    /**
     * Calculate monthly mortgage payment.
     */
    #[OA\Post(
        path: '/api/v1/tenant/{tenant}/realestate/mortgage/monthly-payment',
        summary: 'Calculate monthly payment',
        description: 'Calculate monthly mortgage payment based on loan amount, interest rate, and term',
        tags: ['Mortgage Calculator'],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                required: ['loan_amount', 'annual_interest_rate', 'loan_years'],
                properties: [
                    new OA\Property(property: 'loan_amount', type: 'number', example: 500000, description: 'Loan amount in SAR'),
                    new OA\Property(property: 'annual_interest_rate', type: 'number', example: 4.5, description: 'Annual interest rate as percentage'),
                    new OA\Property(property: 'loan_years', type: 'integer', example: 25, description: 'Loan term in years'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Monthly payment calculated',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean'),
                        new OA\Property(property: 'data', type: 'object', properties: [
                            new OA\Property(property: 'monthly_payment', type: 'number', example: 2764.5),
                            new OA\Property(property: 'total_payment', type: 'number'),
                            new OA\Property(property: 'total_interest', type: 'number'),
                            new OA\Property(property: 'number_of_payments', type: 'integer'),
                            new OA\Property(property: 'annual_interest_rate', type: 'number'),
                        ]),
                    ]
                )
            ),
        ]
    )]
    public function monthlyPayment(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'loan_amount' => 'required|numeric|min:1',
            'annual_interest_rate' => 'required|numeric|min:0|max:30',
            'loan_years' => 'required|integer|min:1|max:50',
        ]);

        Log::info('Mortgage: calculating monthly payment', [
            'loan_amount' => $validated['loan_amount'],
            'annual_rate' => $validated['annual_interest_rate'],
            'loan_years' => $validated['loan_years'],
            'ip' => $request->ip(),
        ]);

        $result = $this->mortgageService->calculateMonthlyPayment(
            (float) $validated['loan_amount'],
            (float) $validated['annual_interest_rate'],
            (int) $validated['loan_years']
        );

        if (isset($result['error'])) {
            return $this->error($result['error'], 400);
        }

        return $this->success($result);
    }

    /**
     * Calculate loan amount from desired monthly payment.
     */
    #[OA\Post(
        path: '/api/v1/tenant/{tenant}/realestate/mortgage/loan-amount',
        summary: 'Calculate loan amount',
        description: 'Calculate maximum loan amount based on desired monthly payment',
        tags: ['Mortgage Calculator'],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                required: ['monthly_payment', 'annual_interest_rate', 'loan_years'],
                properties: [
                    new OA\Property(property: 'monthly_payment', type: 'number', example: 2764.5, description: 'Desired monthly payment in SAR'),
                    new OA\Property(property: 'annual_interest_rate', type: 'number', example: 4.5, description: 'Annual interest rate as percentage'),
                    new OA\Property(property: 'loan_years', type: 'integer', example: 25, description: 'Loan term in years'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Loan amount calculated',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean'),
                        new OA\Property(property: 'data', type: 'object', properties: [
                            new OA\Property(property: 'loan_amount', type: 'number'),
                            new OA\Property(property: 'monthly_payment', type: 'number'),
                            new OA\Property(property: 'number_of_payments', type: 'integer'),
                            new OA\Property(property: 'annual_interest_rate', type: 'number'),
                        ]),
                    ]
                )
            ),
        ]
    )]
    public function loanAmount(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'monthly_payment' => 'required|numeric|min:1',
            'annual_interest_rate' => 'required|numeric|min:0|max:30',
            'loan_years' => 'required|integer|min:1|max:50',
        ]);

        Log::info('Mortgage: calculating loan amount', [
            'monthly_payment' => $validated['monthly_payment'],
            'annual_rate' => $validated['annual_interest_rate'],
            'loan_years' => $validated['loan_years'],
            'ip' => $request->ip(),
        ]);

        $result = $this->mortgageService->calculateLoanAmount(
            (float) $validated['monthly_payment'],
            (float) $validated['annual_interest_rate'],
            (int) $validated['loan_years']
        );

        if (isset($result['error'])) {
            return $this->error($result['error'], 400);
        }

        return $this->success($result);
    }

    /**
     * Generate amortization schedule.
     */
    #[OA\Post(
        path: '/api/v1/tenant/{tenant}/realestate/mortgage/amortization-schedule',
        summary: 'Generate amortization schedule',
        description: 'Generate detailed payment schedule showing principal and interest breakdown',
        tags: ['Mortgage Calculator'],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                required: ['loan_amount', 'annual_interest_rate', 'loan_years'],
                properties: [
                    new OA\Property(property: 'loan_amount', type: 'number', example: 500000),
                    new OA\Property(property: 'annual_interest_rate', type: 'number', example: 4.5),
                    new OA\Property(property: 'loan_years', type: 'integer', example: 25),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Amortization schedule generated',
            ),
        ]
    )]
    public function amortizationSchedule(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'loan_amount' => 'required|numeric|min:1',
            'annual_interest_rate' => 'required|numeric|min:0|max:30',
            'loan_years' => 'required|integer|min:1|max:50',
        ]);

        Log::info('Mortgage: generating amortization schedule', [
            'loan_amount' => $validated['loan_amount'],
            'annual_rate' => $validated['annual_interest_rate'],
            'loan_years' => $validated['loan_years'],
            'ip' => $request->ip(),
        ]);

        $result = $this->mortgageService->generateAmortizationSchedule(
            (float) $validated['loan_amount'],
            (float) $validated['annual_interest_rate'],
            (int) $validated['loan_years']
        );

        return $this->success($result);
    }

    /**
     * Calculate affordability based on income.
     */
    #[OA\Post(
        path: '/api/v1/tenant/{tenant}/realestate/mortgage/affordability',
        summary: 'Calculate affordability',
        description: 'Calculate maximum loan amount based on income and debt-to-income ratio',
        tags: ['Mortgage Calculator'],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                required: ['monthly_gross_income'],
                properties: [
                    new OA\Property(property: 'monthly_gross_income', type: 'number', example: 10000, description: 'Monthly gross income in SAR'),
                    new OA\Property(property: 'existing_monthly_debt', type: 'number', example: 2000, description: 'Existing monthly debt (car payments, etc)'),
                    new OA\Property(property: 'annual_interest_rate', type: 'number', example: 4.5, description: 'Assumed annual interest rate'),
                    new OA\Property(property: 'loan_years', type: 'integer', example: 25, description: 'Loan term in years'),
                    new OA\Property(property: 'max_dti_ratio', type: 'number', example: 0.43, description: 'Max debt-to-income ratio (0-1)'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Affordability calculated',
            ),
        ]
    )]
    public function affordability(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'monthly_gross_income' => 'required|numeric|min:1',
            'existing_monthly_debt' => 'nullable|numeric|min:0',
            'annual_interest_rate' => 'nullable|numeric|min:0|max:30',
            'loan_years' => 'nullable|integer|min:1|max:50',
            'max_dti_ratio' => 'nullable|numeric|min:0|max:1',
        ]);

        Log::info('Mortgage: calculating affordability', [
            'monthly_income' => $validated['monthly_gross_income'],
            'existing_debt' => $validated['existing_monthly_debt'] ?? 0,
            'annual_rate' => $validated['annual_interest_rate'] ?? 5.0,
            'loan_years' => $validated['loan_years'] ?? 25,
            'ip' => $request->ip(),
        ]);

        $result = $this->mortgageService->calculateAffordability(
            (float) $validated['monthly_gross_income'],
            (float) ($validated['existing_monthly_debt'] ?? 0),
            (float) ($validated['max_dti_ratio'] ?? 0.43),
            (float) ($validated['annual_interest_rate'] ?? 5.0),
            (int) ($validated['loan_years'] ?? 25)
        );

        if (isset($result['error'])) {
            return $this->error($result['error'], 400);
        }

        return $this->success($result);
    }

    /**
     * Calculate down payment.
     */
    #[OA\Post(
        path: '/api/v1/tenant/{tenant}/realestate/mortgage/down-payment',
        summary: 'Calculate down payment',
        description: 'Calculate down payment and resulting loan amount',
        tags: ['Mortgage Calculator'],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                required: ['property_price', 'down_payment_percent'],
                properties: [
                    new OA\Property(property: 'property_price', type: 'number', example: 625000, description: 'Property price in SAR'),
                    new OA\Property(property: 'down_payment_percent', type: 'number', example: 20, description: 'Down payment as percentage'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Down payment calculated',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean'),
                        new OA\Property(property: 'data', type: 'object', properties: [
                            new OA\Property(property: 'property_price', type: 'number'),
                            new OA\Property(property: 'down_payment_percent', type: 'number'),
                            new OA\Property(property: 'down_payment_amount', type: 'number'),
                            new OA\Property(property: 'loan_amount', type: 'number'),
                        ]),
                    ]
                )
            ),
        ]
    )]
    public function downPayment(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'property_price' => 'required|numeric|min:1',
            'down_payment_percent' => 'required|numeric|min:0|max:100',
        ]);

        Log::info('Mortgage: calculating down payment', [
            'property_price' => $validated['property_price'],
            'down_payment_percent' => $validated['down_payment_percent'],
            'ip' => $request->ip(),
        ]);

        $result = $this->mortgageService->calculateDownPayment(
            (float) $validated['property_price'],
            (float) $validated['down_payment_percent']
        );

        if (isset($result['error'])) {
            return $this->error($result['error'], 400);
        }

        return $this->success($result);
    }

    /**
     * Calculate mortgage for a specific property.
     */
    #[OA\Post(
        path: '/api/v1/tenant/{tenant}/realestate/mortgage/for-property/{property}',
        summary: 'Calculate mortgage for property',
        description: 'Calculate mortgage payment for a specific property using its actual price',
        tags: ['Mortgage Calculator'],
        parameters: [
            new OA\Parameter(name: 'property', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'down_payment_percent', type: 'number', example: 20, description: 'Down payment as percentage (default: 20)'),
                    new OA\Property(property: 'annual_interest_rate', type: 'number', example: 4.5, description: 'Annual interest rate (default: 4.5)'),
                    new OA\Property(property: 'loan_years', type: 'integer', example: 25, description: 'Loan term in years (default: 25)'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Mortgage calculated for property',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean'),
                        new OA\Property(property: 'data', type: 'object', properties: [
                            new OA\Property(property: 'property', type: 'object', properties: [
                                new OA\Property(property: 'id', type: 'integer'),
                                new OA\Property(property: 'title', type: 'string'),
                                new OA\Property(property: 'price', type: 'number'),
                                new OA\Property(property: 'currency', type: 'string'),
                            ]),
                            new OA\Property(property: 'down_payment', type: 'object'),
                            new OA\Property(property: 'loan_details', type: 'object'),
                            new OA\Property(property: 'installment_options', type: 'array', items: new OA\Items(type: 'object')),
                        ]),
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'Property not found'),
        ]
    )]
    public function forProperty(Request $request, int $property): JsonResponse
    {
        $validated = $request->validate([
            'down_payment_percent' => 'nullable|numeric|min:0|max:100',
            'annual_interest_rate' => 'nullable|numeric|min:0|max:30',
            'loan_years' => 'nullable|integer|min:1|max:50',
        ]);

        // Fetch property
        $propertyModel = $this->propertyService->getProperty($property);
        
        if (!$propertyModel || !$propertyModel->is_published) {
            Log::warning('Mortgage for property: property not found', [
                'property_id' => $property,
            ]);
            return $this->error('Property not found', 404);
        }

        // Use defaults or user-provided values
        $downPaymentPercent = $validated['down_payment_percent'] ?? 20;
        $annualRate = $validated['annual_interest_rate'] ?? 4.5;
        $loanYears = $validated['loan_years'] ?? 25;

        Log::info('Mortgage: calculating for property', [
            'property_id' => $property,
            'property_price' => $propertyModel->price,
            'down_payment_percent' => $downPaymentPercent,
            'annual_rate' => $annualRate,
            'loan_years' => $loanYears,
            'ip' => $request->ip(),
        ]);

        // Calculate down payment
        $downPaymentCalc = $this->mortgageService->calculateDownPayment(
            (float) $propertyModel->price,
            (float) $downPaymentPercent
        );

        // Calculate monthly payment
        $monthlyPaymentCalc = $this->mortgageService->calculateMonthlyPayment(
            $downPaymentCalc['loan_amount'],
            (float) $annualRate,
            (int) $loanYears
        );

        // Calculate alternate loan terms (15, 20, 25, 30 years)
        $installmentOptions = [];
        foreach ([15, 20, 25, 30] as $years) {
            $calc = $this->mortgageService->calculateMonthlyPayment(
                $downPaymentCalc['loan_amount'],
                (float) $annualRate,
                $years
            );
            $installmentOptions[] = [
                'loan_years' => $years,
                'monthly_payment' => $calc['monthly_payment'],
                'total_payment' => $calc['total_payment'],
                'total_interest' => $calc['total_interest'],
            ];
        }

        Log::info('Mortgage for property: calculation complete', [
            'property_id' => $property,
            'monthly_payment' => $monthlyPaymentCalc['monthly_payment'],
        ]);

        return $this->success([
            'property' => [
                'id' => $propertyModel->id,
                'title' => $propertyModel->title,
                'price' => (float) $propertyModel->price,
                'currency' => $propertyModel->currency,
                'listing_type' => $propertyModel->listing_type,
            ],
            'down_payment' => $downPaymentCalc,
            'loan_details' => $monthlyPaymentCalc,
            'installment_options' => $installmentOptions,
            'assumptions' => [
                'down_payment_percent' => $downPaymentPercent,
                'annual_interest_rate' => $annualRate,
                'primary_loan_term_years' => $loanYears,
            ],
        ]);
    }
}
