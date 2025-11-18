<?php
/**
 * White-Box Test Functions
 * 
 * Contains all test function definitions that can be shared
 * between php_unit_tests.php and generate_report.php
 */

// ============================================================================
// CURRENCY FUNCTIONS TESTS
// ============================================================================

function test_formatPeso_basic() {
    $result = formatPeso(1234.56);
    return [
        'passed' => $result === '₱1,234.56',
        'message' => 'formatPeso should format basic number',
        'expected' => '₱1,234.56',
        'actual' => $result
    ];
}

function test_formatPeso_without_symbol() {
    $result = formatPeso(1234.56, false);
    return [
        'passed' => $result === '1,234.56',
        'message' => 'formatPeso should format without symbol when requested',
        'expected' => '1,234.56',
        'actual' => $result
    ];
}

function test_formatPeso_zero() {
    $result = formatPeso(0);
    return [
        'passed' => $result === '₱0.00',
        'message' => 'formatPeso should handle zero',
        'expected' => '₱0.00',
        'actual' => $result
    ];
}

function test_formatPeso_negative() {
    $result = formatPeso(-100);
    return [
        'passed' => $result === '₱-100.00',
        'message' => 'formatPeso should handle negative numbers',
        'expected' => '₱-100.00',
        'actual' => $result
    ];
}

function test_formatPeso_large_number() {
    $result = formatPeso(1234567.89);
    return [
        'passed' => $result === '₱1,234,567.89',
        'message' => 'formatPeso should format large numbers with multiple commas',
        'expected' => '₱1,234,567.89',
        'actual' => $result
    ];
}

function test_formatPeso_string_input() {
    $result = formatPeso('1234.56');
    return [
        'passed' => $result === '₱1,234.56',
        'message' => 'formatPeso should handle string input',
        'expected' => '₱1,234.56',
        'actual' => $result
    ];
}

function test_formatPesoWhole_basic() {
    $result = formatPesoWhole(1234);
    return [
        'passed' => $result === '₱1,234',
        'message' => 'formatPesoWhole should format without decimals',
        'expected' => '₱1,234',
        'actual' => $result
    ];
}

function test_formatPesoWhole_decimal_rounding() {
    $result = formatPesoWhole(1234.99);
    return [
        'passed' => $result === '₱1,235',
        'message' => 'formatPesoWhole should round decimals',
        'expected' => '₱1,235',
        'actual' => $result
    ];
}

function test_parsePeso_basic() {
    $result = parsePeso('₱1,234.56');
    return [
        'passed' => abs($result - 1234.56) < 0.01,
        'message' => 'parsePeso should parse formatted peso string',
        'expected' => 1234.56,
        'actual' => $result
    ];
}

function test_parsePeso_without_symbol() {
    $result = parsePeso('1,234.56');
    return [
        'passed' => abs($result - 1234.56) < 0.01,
        'message' => 'parsePeso should parse without symbol',
        'expected' => 1234.56,
        'actual' => $result
    ];
}

function test_isValidPeso_valid() {
    $result = isValidPeso('₱1,234.56');
    return [
        'passed' => $result === true,
        'message' => 'isValidPeso should validate correct peso string',
        'expected' => true,
        'actual' => $result
    ];
}

function test_isValidPeso_invalid() {
    $result = isValidPeso('invalid');
    return [
        'passed' => $result === false,
        'message' => 'isValidPeso should reject invalid strings',
        'expected' => false,
        'actual' => $result
    ];
}

function test_isValidPeso_negative() {
    $result = isValidPeso('-₱100');
    return [
        'passed' => $result === true,
        'message' => 'isValidPeso should accept negative amounts',
        'expected' => true,
        'actual' => $result
    ];
}

// ============================================================================
// DISCOUNT FUNCTIONS TESTS
// ============================================================================

function test_DiscountManager_calculateDiscount_success() {
    global $conn;
    
    // Check if config exists, create if not
    $check_sql = "SELECT * FROM discount_config WHERE discount_type = 'senior_citizen' AND is_active = 1";
    $result = $conn->query($check_sql);
    
    if ($result->num_rows == 0) {
        // Create test config
        $insert_sql = "INSERT INTO discount_config (discount_type, discount_percentage, minimum_amount, maximum_discount, is_active) 
                       VALUES ('senior_citizen', 20, 100, 500, 1)";
        $conn->query($insert_sql);
    }
    
    $manager = new DiscountManager($conn);
    $result = $manager->calculateDiscount(1000, 'senior_citizen');
    
    return [
        'passed' => $result['success'] === true && 
                   abs($result['discount_amount'] - 200) < 0.01 &&
                   abs($result['final_amount'] - 800) < 0.01,
        'message' => 'DiscountManager should calculate discount correctly',
        'expected' => ['success' => true, 'discount_amount' => 200, 'final_amount' => 800],
        'actual' => $result
    ];
}

function test_DiscountManager_calculateDiscount_below_minimum() {
    global $conn;
    
    $manager = new DiscountManager($conn);
    $result = $manager->calculateDiscount(50, 'senior_citizen');
    
    return [
        'passed' => $result['success'] === false && 
                   strpos($result['error'], 'minimum') !== false,
        'message' => 'DiscountManager should reject amounts below minimum',
        'expected' => ['success' => false],
        'actual' => $result
    ];
}

function test_DiscountManager_calculateDiscount_invalid_type() {
    global $conn;
    
    $manager = new DiscountManager($conn);
    $result = $manager->calculateDiscount(1000, 'invalid_type');
    
    return [
        'passed' => $result['success'] === false && 
                   strpos($result['error'], 'not found') !== false,
        'message' => 'DiscountManager should reject invalid discount types',
        'expected' => ['success' => false],
        'actual' => $result
    ];
}

function test_DiscountManager_calculateDiscount_maximum_limit() {
    global $conn;
    
    // Ensure config with max limit exists
    $check_sql = "SELECT * FROM discount_config WHERE discount_type = 'senior_citizen' AND is_active = 1";
    $result = $conn->query($check_sql);
    
    if ($result->num_rows > 0) {
        $update_sql = "UPDATE discount_config SET maximum_discount = 100 WHERE discount_type = 'senior_citizen'";
        $conn->query($update_sql);
    }
    
    $manager = new DiscountManager($conn);
    $result = $manager->calculateDiscount(10000, 'senior_citizen'); // Would be 2000, but max is 100
    
    return [
        'passed' => $result['success'] === true && 
                   abs($result['discount_amount'] - 100) < 0.01,
        'message' => 'DiscountManager should apply maximum discount limit',
        'expected' => ['success' => true, 'discount_amount' => 100],
        'actual' => $result
    ];
}

function test_formatDiscountDisplay_senior_citizen() {
    $result = formatDiscountDisplay('senior_citizen');
    return [
        'passed' => $result === 'Senior Citizen',
        'message' => 'formatDiscountDisplay should format senior_citizen',
        'expected' => 'Senior Citizen',
        'actual' => $result
    ];
}

function test_formatDiscountDisplay_pwd() {
    $result = formatDiscountDisplay('pwd');
    return [
        'passed' => $result === 'PWD',
        'message' => 'formatDiscountDisplay should format pwd',
        'expected' => 'PWD',
        'actual' => $result
    ];
}

function test_formatDiscountDisplay_unknown() {
    $result = formatDiscountDisplay('unknown');
    return [
        'passed' => $result === 'None',
        'message' => 'formatDiscountDisplay should return None for unknown types',
        'expected' => 'None',
        'actual' => $result
    ];
}

function test_getDiscountBadgeClass_senior_citizen() {
    $result = getDiscountBadgeClass('senior_citizen');
    return [
        'passed' => $result === 'bg-info',
        'message' => 'getDiscountBadgeClass should return bg-info for senior_citizen',
        'expected' => 'bg-info',
        'actual' => $result
    ];
}

function test_getDiscountBadgeClass_pwd() {
    $result = getDiscountBadgeClass('pwd');
    return [
        'passed' => $result === 'bg-success',
        'message' => 'getDiscountBadgeClass should return bg-success for pwd',
        'expected' => 'bg-success',
        'actual' => $result
    ];
}

// ============================================================================
// CASH FLOAT FUNCTIONS TESTS
// ============================================================================

function test_setCashFloat_success() {
    global $conn;
    
    $date = date('Y-m-d');
    $amount = 5000.00;
    $admin_id = 1; // Assuming admin ID 1 exists
    
    $result = setCashFloat($conn, $date, $amount, $admin_id, 'Test float');
    
    // Clean up - delete test float if it was created
    if ($result['success']) {
        $delete_sql = "DELETE FROM cash_float WHERE float_id = ?";
        $stmt = $conn->prepare($delete_sql);
        $stmt->bind_param("i", $result['float_id']);
        $stmt->execute();
        $stmt->close();
    }
    
    return [
        'passed' => $result['success'] === true,
        'message' => 'setCashFloat should create cash float successfully',
        'expected' => ['success' => true],
        'actual' => $result
    ];
}

function test_setCashFloat_duplicate_date() {
    global $conn;
    
    $date = date('Y-m-d');
    $amount = 5000.00;
    $admin_id = 1;
    
    // Create first float
    $first = setCashFloat($conn, $date, $amount, $admin_id);
    
    // Try to create duplicate
    $second = setCashFloat($conn, $date, $amount, $admin_id);
    
    // Clean up
    if ($first['success']) {
        $delete_sql = "DELETE FROM cash_float WHERE float_id = ?";
        $stmt = $conn->prepare($delete_sql);
        $stmt->bind_param("i", $first['float_id']);
        $stmt->execute();
        $stmt->close();
    }
    
    return [
        'passed' => $second['success'] === false && 
                   strpos($second['message'], 'already') !== false,
        'message' => 'setCashFloat should reject duplicate dates',
        'expected' => ['success' => false],
        'actual' => $second
    ];
}

function test_getCashFloat_not_found() {
    global $conn;
    
    $date = '1900-01-01'; // Date that definitely doesn't exist
    $result = getCashFloat($conn, $date);
    
    return [
        'passed' => $result['success'] === false,
        'message' => 'getCashFloat should return false for non-existent dates',
        'expected' => ['success' => false],
        'actual' => $result
    ];
}

function test_calculateCashVariance_no_float() {
    global $conn;
    
    $date = '1900-01-01';
    $result = calculateCashVariance($conn, $date);
    
    return [
        'passed' => $result['success'] === false,
        'message' => 'calculateCashVariance should fail when no float exists',
        'expected' => ['success' => false],
        'actual' => $result
    ];
}


