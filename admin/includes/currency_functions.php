<?php
/**
 * Currency formatting functions for Philippine Peso
 */

/**
 * Format a number as Philippine Peso currency
 * @param float|int $amount The amount to format
 * @param bool $show_symbol Whether to show the ₱ symbol (default: true)
 * @return string Formatted currency string
 */
function formatPeso($amount, $show_symbol = true) {
    // Ensure we have a valid number
    $amount = floatval($amount);
    
    // Format with commas as thousand separators and 2 decimal places
    $formatted = number_format($amount, 2, '.', ',');
    
    // Add peso symbol if requested
    if ($show_symbol) {
        return '₱' . $formatted;
    }
    
    return $formatted;
}

/**
 * Format a number as Philippine Peso currency without decimal places (for whole numbers)
 * @param float|int $amount The amount to format
 * @param bool $show_symbol Whether to show the ₱ symbol (default: true)
 * @return string Formatted currency string
 */
function formatPesoWhole($amount, $show_symbol = true) {
    // Ensure we have a valid number
    $amount = floatval($amount);
    
    // Format with commas as thousand separators, no decimal places
    $formatted = number_format($amount, 0, '.', ',');
    
    // Add peso symbol if requested
    if ($show_symbol) {
        return '₱' . $formatted;
    }
    
    return $formatted;
}

/**
 * Parse a peso string back to a float
 * @param string $peso_string The peso string to parse
 * @return float The parsed amount
 */
function parsePeso($peso_string) {
    // Remove peso symbol and commas
    $clean = str_replace(['₱', ','], '', $peso_string);
    return floatval($clean);
}

/**
 * Validate if a string is a valid peso amount
 * @param string $peso_string The string to validate
 * @return bool True if valid, false otherwise
 */
function isValidPeso($peso_string) {
    // Remove peso symbol and spaces
    $clean = str_replace(['₱', ' '], '', $peso_string);
    
    // Check if it's a valid number
    return is_numeric($clean) && floatval($clean) >= 0;
}
?>









