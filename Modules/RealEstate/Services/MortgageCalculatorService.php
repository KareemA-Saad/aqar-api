<?php

declare(strict_types=1);

namespace Modules\RealEstate\Services;

use Illuminate\Support\Facades\Log;

/**
 * Mortgage Calculator Service
 * 
 * Provides stateless mortgage/financing calculations.
 * Uses native PHP math for precision with standard financial formulas.
 */
class MortgageCalculatorService
{
    /**
     * Calculate monthly payment using standard mortgage formula.
     * 
     * Formula: M = P [ r(1 + r)^n ] / [ (1 + r)^n - 1 ]
     * Where:
     * - M = Monthly payment
     * - P = Principal (loan amount)
     * - r = Monthly interest rate (annual rate / 12 / 100)
     * - n = Number of payments (years * 12)
     */
    public function calculateMonthlyPayment(
        float $loanAmount,
        float $annualInterestRate,
        int $loanYears
    ): array {
        if ($loanAmount <= 0 || $loanYears <= 0) {
            Log::warning('Mortgage: invalid input parameters', [
                'loan_amount' => $loanAmount,
                'annual_rate' => $annualInterestRate,
                'loan_years' => $loanYears,
            ]);
            return [
                'error' => 'Invalid input parameters',
                'monthly_payment' => 0,
                'total_payment' => 0,
                'total_interest' => 0,
            ];
        }

        // Convert to monthly rate
        $monthlyRate = $annualInterestRate / 12 / 100;
        $numberOfPayments = $loanYears * 12;

        // Handle zero interest rate
        if ($monthlyRate == 0) {
            $monthlyPayment = $loanAmount / $numberOfPayments;
            $totalPayment = $loanAmount;
            $totalInterest = 0;
        } else {
            // Standard mortgage formula
            $numerator = $monthlyRate * pow(1 + $monthlyRate, $numberOfPayments);
            $denominator = pow(1 + $monthlyRate, $numberOfPayments) - 1;
            $monthlyPayment = $loanAmount * ($numerator / $denominator);
            
            $totalPayment = $monthlyPayment * $numberOfPayments;
            $totalInterest = $totalPayment - $loanAmount;
        }

        Log::info('Mortgage calculation completed', [
            'loan_amount' => $loanAmount,
            'monthly_rate' => $monthlyRate,
            'loan_years' => $loanYears,
            'monthly_payment' => round($monthlyPayment, 2),
            'total_interest' => round($totalInterest, 2),
        ]);

        return [
            'monthly_payment' => round($monthlyPayment, 2),
            'total_payment' => round($totalPayment, 2),
            'total_interest' => round($totalInterest, 2),
            'number_of_payments' => $numberOfPayments,
            'annual_interest_rate' => $annualInterestRate,
        ];
    }

    /**
     * Calculate loan amount based on desired monthly payment.
     * 
     * Reverse formula: P = M * [ (1 + r)^n - 1 ] / [ r(1 + r)^n ]
     */
    public function calculateLoanAmount(
        float $monthlyPayment,
        float $annualInterestRate,
        int $loanYears
    ): array {
        if ($monthlyPayment <= 0 || $loanYears <= 0) {
            Log::warning('Mortgage: invalid input for loan amount calculation', [
                'monthly_payment' => $monthlyPayment,
                'annual_rate' => $annualInterestRate,
                'loan_years' => $loanYears,
            ]);
            return [
                'error' => 'Invalid input parameters',
                'loan_amount' => 0,
            ];
        }

        $monthlyRate = $annualInterestRate / 12 / 100;
        $numberOfPayments = $loanYears * 12;

        if ($monthlyRate == 0) {
            $loanAmount = $monthlyPayment * $numberOfPayments;
        } else {
            $denominator = $monthlyRate * pow(1 + $monthlyRate, $numberOfPayments);
            $numerator = pow(1 + $monthlyRate, $numberOfPayments) - 1;
            $loanAmount = $monthlyPayment * ($numerator / $denominator);
        }

        Log::info('Loan amount calculated', [
            'monthly_payment' => $monthlyPayment,
            'loan_years' => $loanYears,
            'loan_amount' => round($loanAmount, 2),
        ]);

        return [
            'loan_amount' => round($loanAmount, 2),
            'monthly_payment' => $monthlyPayment,
            'number_of_payments' => $numberOfPayments,
            'annual_interest_rate' => $annualInterestRate,
        ];
    }

    /**
     * Calculate amortization schedule (payment breakdown).
     * 
     * Shows principal vs. interest breakdown for each payment period.
     */
    public function generateAmortizationSchedule(
        float $loanAmount,
        float $annualInterestRate,
        int $loanYears,
        int $limit = 360
    ): array {
        if ($loanAmount <= 0 || $loanYears <= 0) {
            return [
                'error' => 'Invalid input parameters',
                'schedule' => [],
            ];
        }

        // Get monthly payment
        $paymentInfo = $this->calculateMonthlyPayment($loanAmount, $annualInterestRate, $loanYears);
        $monthlyPayment = $paymentInfo['monthly_payment'] ?? 0;
        
        $monthlyRate = $annualInterestRate / 12 / 100;
        $numberOfPayments = min($loanYears * 12, $limit);
        
        $schedule = [];
        $remainingBalance = $loanAmount;

        Log::info('Amortization schedule generation started', [
            'loan_amount' => $loanAmount,
            'loan_years' => $loanYears,
            'monthly_payment' => $monthlyPayment,
        ]);

        for ($month = 1; $month <= $numberOfPayments; $month++) {
            // Only include summary for every 12 months (yearly summary)
            if ($month % 12 !== 0 && $month !== $numberOfPayments) {
                $remainingBalance -= ($monthlyPayment - $remainingBalance * $monthlyRate);
                continue;
            }

            $interestPayment = $remainingBalance * $monthlyRate;
            $principalPayment = $monthlyPayment - $interestPayment;
            $remainingBalance -= $principalPayment;

            // Prevent negative balance
            if ($remainingBalance < 0) {
                $remainingBalance = 0;
            }

            $schedule[] = [
                'month' => $month,
                'year' => ceil($month / 12),
                'monthly_payment' => round($monthlyPayment, 2),
                'principal' => round($principalPayment, 2),
                'interest' => round($interestPayment, 2),
                'remaining_balance' => round($remainingBalance, 2),
            ];
        }

        Log::info('Amortization schedule generated', [
            'total_months' => $numberOfPayments,
            'schedule_entries' => count($schedule),
        ]);

        return [
            'schedule' => $schedule,
            'summary' => [
                'loan_amount' => $loanAmount,
                'monthly_payment' => round($monthlyPayment, 2),
                'total_payments' => $numberOfPayments,
                'total_interest' => round($paymentInfo['total_interest'], 2),
            ],
        ];
    }

    /**
     * Calculate affordability based on income and debt-to-income ratio.
     * 
     * Standard DTI ratio: 43% (can afford monthly debt of up to 43% of gross income)
     */
    public function calculateAffordability(
        float $monthlyGrossIncome,
        float $existingMonthlyDebt = 0,
        float $maxDtiRatio = 0.43,
        float $annualInterestRate = 5.0,
        int $loanYears = 25
    ): array {
        if ($monthlyGrossIncome <= 0) {
            Log::warning('Mortgage: invalid income for affordability calculation', [
                'monthly_income' => $monthlyGrossIncome,
            ]);
            return [
                'error' => 'Invalid monthly income',
                'max_loan_amount' => 0,
                'max_monthly_payment' => 0,
            ];
        }

        // Maximum debt allowed by DTI ratio
        $maxTotalDebt = $monthlyGrossIncome * $maxDtiRatio;
        
        // Available for mortgage payment
        $maxMortgagePayment = $maxTotalDebt - $existingMonthlyDebt;

        // Calculate loan amount from max payment
        $loanCalc = $this->calculateLoanAmount($maxMortgagePayment, $annualInterestRate, $loanYears);

        Log::info('Affordability calculation completed', [
            'monthly_income' => $monthlyGrossIncome,
            'max_dti_ratio' => $maxDtiRatio,
            'max_mortgage_payment' => round($maxMortgagePayment, 2),
            'max_loan_amount' => round($loanCalc['loan_amount'], 2),
        ]);

        return [
            'monthly_gross_income' => $monthlyGrossIncome,
            'existing_monthly_debt' => $existingMonthlyDebt,
            'dti_ratio_used' => $maxDtiRatio,
            'max_total_debt_allowed' => round($maxTotalDebt, 2),
            'max_mortgage_payment' => round($maxMortgagePayment, 2),
            'estimated_max_loan_amount' => round($loanCalc['loan_amount'], 2),
            'assumptions' => [
                'interest_rate' => $annualInterestRate . '%',
                'loan_term_years' => $loanYears,
                'dti_ratio' => $maxDtiRatio * 100 . '%',
            ],
        ];
    }

    /**
     * Calculate down payment based on purchase price and down payment percentage.
     */
    public function calculateDownPayment(
        float $propertyPrice,
        float $downPaymentPercent = 20
    ): array {
        if ($propertyPrice <= 0 || $downPaymentPercent < 0 || $downPaymentPercent > 100) {
            Log::warning('Mortgage: invalid input for down payment', [
                'property_price' => $propertyPrice,
                'down_payment_percent' => $downPaymentPercent,
            ]);
            return [
                'error' => 'Invalid input',
                'down_payment' => 0,
                'loan_amount' => 0,
            ];
        }

        $downPayment = $propertyPrice * ($downPaymentPercent / 100);
        $loanAmount = $propertyPrice - $downPayment;

        Log::info('Down payment calculated', [
            'property_price' => $propertyPrice,
            'down_payment_percent' => $downPaymentPercent,
            'down_payment_amount' => round($downPayment, 2),
            'loan_amount' => round($loanAmount, 2),
        ]);

        return [
            'property_price' => $propertyPrice,
            'down_payment_percent' => $downPaymentPercent,
            'down_payment_amount' => round($downPayment, 2),
            'loan_amount' => round($loanAmount, 2),
        ];
    }
}
