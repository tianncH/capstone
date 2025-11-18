<?php
/**
 * White-Box Unit Test Endpoint for PHP Functions
 * 
 * This endpoint tests internal PHP functions and classes without modifying
 * existing code. All functions are tested in isolation with various inputs.
 * 
 * Usage: POST to this file with test_name parameter
 */

require_once '../../admin/includes/db_connection.php';
require_once '../../admin/includes/currency_functions.php';
require_once '../../admin/includes/discount_functions.php';
require_once '../../admin/includes/cash_float_functions.php';

// Include test function definitions
require_once 'test_functions.php';

header('Content-Type: application/json');

// Test results storage
$test_results = [
    'passed' => [],
    'failed' => [],
    'total' => 0
];

/**
 * Helper function to run a test and record results
 */
function runTest($test_name, $test_function) {
    global $test_results;
    $test_results['total']++;
    
    try {
        $result = $test_function();
        if ($result['passed']) {
            $test_results['passed'][] = [
                'test' => $test_name,
                'message' => $result['message'] ?? 'Test passed'
            ];
            return true;
        } else {
            $test_results['failed'][] = [
                'test' => $test_name,
                'message' => $result['message'] ?? 'Test failed',
                'expected' => $result['expected'] ?? null,
                'actual' => $result['actual'] ?? null
            ];
            return false;
        }
    } catch (Exception $e) {
        $test_results['failed'][] = [
            'test' => $test_name,
            'message' => 'Exception: ' . $e->getMessage(),
            'error' => $e->getTraceAsString()
        ];
        return false;
    }
}

// ============================================================================
// RUN ALL TESTS
// ============================================================================

$test_name = $_GET['test'] ?? 'all';

if ($test_name === 'all') {
    // Run all currency tests
    runTest('formatPeso_basic', 'test_formatPeso_basic');
    runTest('formatPeso_without_symbol', 'test_formatPeso_without_symbol');
    runTest('formatPeso_zero', 'test_formatPeso_zero');
    runTest('formatPeso_negative', 'test_formatPeso_negative');
    runTest('formatPeso_large_number', 'test_formatPeso_large_number');
    runTest('formatPeso_string_input', 'test_formatPeso_string_input');
    runTest('formatPesoWhole_basic', 'test_formatPesoWhole_basic');
    runTest('formatPesoWhole_decimal_rounding', 'test_formatPesoWhole_decimal_rounding');
    runTest('parsePeso_basic', 'test_parsePeso_basic');
    runTest('parsePeso_without_symbol', 'test_parsePeso_without_symbol');
    runTest('isValidPeso_valid', 'test_isValidPeso_valid');
    runTest('isValidPeso_invalid', 'test_isValidPeso_invalid');
    runTest('isValidPeso_negative', 'test_isValidPeso_negative');
    
    // Run all discount tests
    runTest('DiscountManager_calculateDiscount_success', 'test_DiscountManager_calculateDiscount_success');
    runTest('DiscountManager_calculateDiscount_below_minimum', 'test_DiscountManager_calculateDiscount_below_minimum');
    runTest('DiscountManager_calculateDiscount_invalid_type', 'test_DiscountManager_calculateDiscount_invalid_type');
    runTest('DiscountManager_calculateDiscount_maximum_limit', 'test_DiscountManager_calculateDiscount_maximum_limit');
    runTest('formatDiscountDisplay_senior_citizen', 'test_formatDiscountDisplay_senior_citizen');
    runTest('formatDiscountDisplay_pwd', 'test_formatDiscountDisplay_pwd');
    runTest('formatDiscountDisplay_unknown', 'test_formatDiscountDisplay_unknown');
    runTest('getDiscountBadgeClass_senior_citizen', 'test_getDiscountBadgeClass_senior_citizen');
    runTest('getDiscountBadgeClass_pwd', 'test_getDiscountBadgeClass_pwd');
    
    // Run all cash float tests
    runTest('setCashFloat_success', 'test_setCashFloat_success');
    runTest('setCashFloat_duplicate_date', 'test_setCashFloat_duplicate_date');
    runTest('getCashFloat_not_found', 'test_getCashFloat_not_found');
    runTest('calculateCashVariance_no_float', 'test_calculateCashVariance_no_float');
    
} else {
    // Run specific test
    if (function_exists('test_' . $test_name)) {
        runTest($test_name, 'test_' . $test_name);
    } else {
        $test_results['failed'][] = [
            'test' => $test_name,
            'message' => 'Test function not found'
        ];
    }
}

// Output results
echo json_encode([
    'summary' => [
        'total' => $test_results['total'],
        'passed' => count($test_results['passed']),
        'failed' => count($test_results['failed']),
        'pass_rate' => $test_results['total'] > 0 ? 
                      round((count($test_results['passed']) / $test_results['total']) * 100, 2) : 0
    ],
    'passed' => $test_results['passed'],
    'failed' => $test_results['failed']
], JSON_PRETTY_PRINT);

