<?php
/**
 * Email Configuration for Venue Reservation System
 * Supports both local mail() and SMTP for better delivery
 */

class EmailConfig {
    // Email Settings
    const FROM_EMAIL = 'cianos.restaurant.ph@gmail.com';
    const FROM_NAME = 'Cianos Restaurant';
    const REPLY_TO = 'reservations@cianosrestaurant.com';
    
    // SMTP Settings (for better delivery)
    const SMTP_HOST = 'smtp.gmail.com'; // Can be changed to your email provider
    const SMTP_PORT = 587;
    const SMTP_USERNAME = 'cianos.restaurant.ph@gmail.com'; // Professional restaurant email
    const SMTP_PASSWORD = 'djxc kbpk wtqt ztlh'; // Replace with your app password
    const SMTP_ENCRYPTION = 'tls';
    
    // Email Templates
    const CONFIRMATION_SUBJECT = 'Reservation Confirmed - Cianos Restaurant';
    const CANCELLATION_SUBJECT = 'Reservation Cancelled - Cianos Restaurant';
    const REMINDER_SUBJECT = 'Reservation Reminder - Cianos Restaurant';
    
    // Restaurant Information
    const RESTAURANT_NAME = 'Cianos Restaurant';
    const RESTAURANT_PHONE = '(555) 123-4567';
    const RESTAURANT_EMAIL = 'reservations@cianosrestaurant.com';
    const RESTAURANT_ADDRESS = '123 Restaurant Street, City, State 12345';
    const RESTAURANT_WEBSITE = 'http://localhost/capstone';
}
?>
