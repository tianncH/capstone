<?php
require_once 'email_config.php';

class EmailSender {
    private $config;
    
    public function __construct() {
        $this->config = new EmailConfig();
    }
    
    /**
     * Send reservation confirmation email
     */
    public function sendReservationConfirmation($reservation_data) {
        $subject = EmailConfig::CONFIRMATION_SUBJECT;
        $html_body = $this->generateConfirmationEmailHTML($reservation_data);
        $text_body = $this->generateConfirmationEmailText($reservation_data);
        
        return $this->sendEmail(
            $reservation_data['customer_email'],
            $reservation_data['customer_name'],
            $subject,
            $html_body,
            $text_body
        );
    }
    
    /**
     * Send reservation cancellation email
     */
    public function sendReservationCancellation($reservation_data) {
        $subject = EmailConfig::CANCELLATION_SUBJECT;
        $html_body = $this->generateCancellationEmailHTML($reservation_data);
        $text_body = $this->generateCancellationEmailText($reservation_data);
        
        return $this->sendEmail(
            $reservation_data['customer_email'],
            $reservation_data['customer_name'],
            $subject,
            $html_body,
            $text_body
        );
    }
    
    /**
     * Send reservation reminder email
     */
    public function sendReservationReminder($reservation_data) {
        $subject = EmailConfig::REMINDER_SUBJECT;
        $html_body = $this->generateReminderEmailHTML($reservation_data);
        $text_body = $this->generateReminderEmailText($reservation_data);
        
        return $this->sendEmail(
            $reservation_data['customer_email'],
            $reservation_data['customer_name'],
            $subject,
            $html_body,
            $text_body
        );
    }
    
    /**
     * Main email sending function
     */
    private function sendEmail($to_email, $to_name, $subject, $html_body, $text_body) {
        // Try SMTP first, fallback to mail()
        if ($this->sendViaSMTP($to_email, $to_name, $subject, $html_body, $text_body)) {
            return ['success' => true, 'method' => 'SMTP'];
        } elseif ($this->sendViaMail($to_email, $to_name, $subject, $html_body, $text_body)) {
            return ['success' => true, 'method' => 'mail()'];
        } else {
            return ['success' => false, 'error' => 'Failed to send email via both methods'];
        }
    }
    
    /**
     * Send email via SMTP (more reliable)
     */
    private function sendViaSMTP($to_email, $to_name, $subject, $html_body, $text_body) {
        try {
            // Create boundary for multipart email
            $boundary = md5(uniqid(time()));
            
            // Headers
            $headers = [
                'From: ' . EmailConfig::FROM_NAME . ' <' . EmailConfig::FROM_EMAIL . '>',
                'Reply-To: ' . EmailConfig::REPLY_TO,
                'MIME-Version: 1.0',
                'Content-Type: multipart/alternative; boundary="' . $boundary . '"',
                'X-Mailer: PHP/' . phpversion()
            ];
            
            // Email body
            $body = "--$boundary\r\n";
            $body .= "Content-Type: text/plain; charset=UTF-8\r\n";
            $body .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
            $body .= $text_body . "\r\n\r\n";
            
            $body .= "--$boundary\r\n";
            $body .= "Content-Type: text/html; charset=UTF-8\r\n";
            $body .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
            $body .= $html_body . "\r\n\r\n";
            $body .= "--$boundary--\r\n";
            
            // Send email
            return mail($to_email, $subject, $body, implode("\r\n", $headers));
            
        } catch (Exception $e) {
            error_log("SMTP Email Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send email via PHP mail() function (fallback)
     */
    private function sendViaMail($to_email, $to_name, $subject, $html_body, $text_body) {
        try {
            $headers = [
                'From: ' . EmailConfig::FROM_NAME . ' <' . EmailConfig::FROM_EMAIL . '>',
                'Reply-To: ' . EmailConfig::REPLY_TO,
                'MIME-Version: 1.0',
                'Content-Type: text/html; charset=UTF-8',
                'X-Mailer: PHP/' . phpversion()
            ];
            
            return mail($to_email, $subject, $html_body, implode("\r\n", $headers));
            
        } catch (Exception $e) {
            error_log("Mail() Email Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Generate HTML confirmation email
     */
    private function generateConfirmationEmailHTML($data) {
        $date_formatted = date('l, F j, Y', strtotime($data['reservation_date']));
        $start_time = date('g:i A', strtotime($data['start_time']));
        $end_time = date('g:i A', strtotime($data['end_time']));
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Reservation Confirmed</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background-color: #f4f4f4; }
                .container { max-width: 600px; margin: 0 auto; background-color: #ffffff; padding: 20px; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
                .header { text-align: center; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; border-radius: 10px 10px 0 0; margin: -20px -20px 20px -20px; }
                .header h1 { margin: 0; font-size: 28px; }
                .header p { margin: 10px 0 0 0; opacity: 0.9; }
                .confirmation-code { background: #f8f9fa; border: 2px dashed #dee2e6; padding: 15px; text-align: center; margin: 20px 0; border-radius: 8px; }
                .confirmation-code h2 { margin: 0; color: #495057; font-size: 24px; letter-spacing: 2px; }
                .details { background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0; }
                .detail-row { display: flex; justify-content: space-between; margin: 10px 0; padding: 8px 0; border-bottom: 1px solid #e9ecef; }
                .detail-row:last-child { border-bottom: none; }
                .detail-label { font-weight: bold; color: #495057; }
                .detail-value { color: #6c757d; }
                .special-requests { background: #e3f2fd; padding: 15px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #2196f3; }
                .footer { text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #e9ecef; color: #6c757d; font-size: 14px; }
                .btn { display: inline-block; padding: 12px 24px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; margin: 10px 5px; }
                .btn:hover { background: #0056b3; }
                .contact-info { background: #e8f5e8; padding: 15px; border-radius: 8px; margin: 20px 0; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>🎉 Reservation Confirmed!</h1>
                    <p>Thank you for choosing " . EmailConfig::RESTAURANT_NAME . "</p>
                </div>
                
                <div class='confirmation-code'>
                    <h2>" . $data['confirmation_code'] . "</h2>
                    <p>Please keep this confirmation code for your records</p>
                </div>
                
                <div class='details'>
                    <h3 style='margin-top: 0; color: #495057;'>Reservation Details</h3>
                    <div class='detail-row'>
                        <span class='detail-label'>Venue:</span>
                        <span class='detail-value'>" . htmlspecialchars($data['venue_name']) . "</span>
                    </div>
                    <div class='detail-row'>
                        <span class='detail-label'>Date:</span>
                        <span class='detail-value'>" . $date_formatted . "</span>
                    </div>
                    <div class='detail-row'>
                        <span class='detail-label'>Time:</span>
                        <span class='detail-value'>" . $start_time . " - " . $end_time . "</span>
                    </div>
                    <div class='detail-row'>
                        <span class='detail-label'>Party Size:</span>
                        <span class='detail-value'>" . $data['party_size'] . " people</span>
                    </div>
                    <div class='detail-row'>
                        <span class='detail-label'>Status:</span>
                        <span class='detail-value' style='color: #28a745; font-weight: bold;'>Confirmed</span>
                    </div>
                </div>
                
                " . ($data['special_requests'] ? "
                <div class='special-requests'>
                    <h4 style='margin-top: 0; color: #1976d2;'>Special Requests:</h4>
                    <p>" . htmlspecialchars($data['special_requests']) . "</p>
                </div>
                " : "") . "
                
                <div class='contact-info'>
                    <h4 style='margin-top: 0; color: #2e7d32;'>Important Information:</h4>
                    <ul>
                        <li>Please arrive on time for your reservation. We hold reservations for 15 minutes past the scheduled time.</li>
                        <li>If you need to make changes or cancel your reservation, please contact us at least 2 hours in advance.</li>
                        <li>Keep your confirmation code safe - you may need it for any changes or inquiries.</li>
                    </ul>
                </div>
                
                <div style='text-align: center; margin: 30px 0;'>
                    <a href='" . EmailConfig::RESTAURANT_WEBSITE . "/reservations/manage_reservation.php?id=" . $data['reservation_id'] . "&code=" . $data['confirmation_code'] . "' class='btn'>Manage Reservation</a>
                    <a href='" . EmailConfig::RESTAURANT_WEBSITE . "/reservations/' class='btn' style='background: #28a745;'>Make Another Reservation</a>
                </div>
                
                <div class='footer'>
                    <p><strong>" . EmailConfig::RESTAURANT_NAME . "</strong></p>
                    <p>📞 " . EmailConfig::RESTAURANT_PHONE . " | ✉️ " . EmailConfig::RESTAURANT_EMAIL . "</p>
                    <p>" . EmailConfig::RESTAURANT_ADDRESS . "</p>
                    <p><a href='" . EmailConfig::RESTAURANT_WEBSITE . "'>" . EmailConfig::RESTAURANT_WEBSITE . "</a></p>
                </div>
            </div>
        </body>
        </html>";
    }
    
    /**
     * Generate text confirmation email
     */
    private function generateConfirmationEmailText($data) {
        $date_formatted = date('l, F j, Y', strtotime($data['reservation_date']));
        $start_time = date('g:i A', strtotime($data['start_time']));
        $end_time = date('g:i A', strtotime($data['end_time']));
        
        return "
RESERVATION CONFIRMED - " . EmailConfig::RESTAURANT_NAME . "

Dear " . $data['customer_name'] . ",

Thank you for choosing " . EmailConfig::RESTAURANT_NAME . "! Your reservation has been confirmed.

CONFIRMATION CODE: " . $data['confirmation_code'] . "

RESERVATION DETAILS:
- Venue: " . $data['venue_name'] . "
- Date: " . $date_formatted . "
- Time: " . $start_time . " - " . $end_time . "
- Party Size: " . $data['party_size'] . " people
- Status: Confirmed

" . ($data['special_requests'] ? "SPECIAL REQUESTS:\n" . $data['special_requests'] . "\n\n" : "") . "

IMPORTANT INFORMATION:
- Please arrive on time for your reservation. We hold reservations for 15 minutes past the scheduled time.
- If you need to make changes or cancel your reservation, please contact us at least 2 hours in advance.
- Keep your confirmation code safe - you may need it for any changes or inquiries.

MANAGE YOUR RESERVATION:
" . EmailConfig::RESTAURANT_WEBSITE . "/reservations/manage_reservation.php?id=" . $data['reservation_id'] . "&code=" . $data['confirmation_code'] . "

CONTACT INFORMATION:
" . EmailConfig::RESTAURANT_NAME . "
Phone: " . EmailConfig::RESTAURANT_PHONE . "
Email: " . EmailConfig::RESTAURANT_EMAIL . "
Address: " . EmailConfig::RESTAURANT_ADDRESS . "
Website: " . EmailConfig::RESTAURANT_WEBSITE . "

We look forward to serving you!

Best regards,
The " . EmailConfig::RESTAURANT_NAME . " Team
        ";
    }
    
    /**
     * Generate HTML cancellation email
     */
    private function generateCancellationEmailHTML($data) {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>Reservation Cancelled</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background-color: #f4f4f4; }
                .container { max-width: 600px; margin: 0 auto; background-color: #ffffff; padding: 20px; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
                .header { text-align: center; background: #dc3545; color: white; padding: 30px; border-radius: 10px 10px 0 0; margin: -20px -20px 20px -20px; }
                .header h1 { margin: 0; font-size: 28px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>Reservation Cancelled</h1>
                </div>
                <p>Dear " . $data['customer_name'] . ",</p>
                <p>Your reservation for " . $data['venue_name'] . " has been cancelled.</p>
                <p>We hope to serve you in the future!</p>
                <p>Best regards,<br>The " . EmailConfig::RESTAURANT_NAME . " Team</p>
            </div>
        </body>
        </html>";
    }
    
    /**
     * Generate text cancellation email
     */
    private function generateCancellationEmailText($data) {
        return "RESERVATION CANCELLED - " . EmailConfig::RESTAURANT_NAME . "\n\nDear " . $data['customer_name'] . ",\n\nYour reservation for " . $data['venue_name'] . " has been cancelled.\n\nWe hope to serve you in the future!\n\nBest regards,\nThe " . EmailConfig::RESTAURANT_NAME . " Team";
    }
    
    /**
     * Generate HTML reminder email
     */
    private function generateReminderEmailHTML($data) {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>Reservation Reminder</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background-color: #f4f4f4; }
                .container { max-width: 600px; margin: 0 auto; background-color: #ffffff; padding: 20px; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
                .header { text-align: center; background: #ffc107; color: #212529; padding: 30px; border-radius: 10px 10px 0 0; margin: -20px -20px 20px -20px; }
                .header h1 { margin: 0; font-size: 28px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>Reservation Reminder</h1>
                </div>
                <p>Dear " . $data['customer_name'] . ",</p>
                <p>This is a friendly reminder about your upcoming reservation at " . $data['venue_name'] . ".</p>
                <p>We look forward to seeing you!</p>
                <p>Best regards,<br>The " . EmailConfig::RESTAURANT_NAME . " Team</p>
            </div>
        </body>
        </html>";
    }
    
    /**
     * Generate text reminder email
     */
    private function generateReminderEmailText($data) {
        return "RESERVATION REMINDER - " . EmailConfig::RESTAURANT_NAME . "\n\nDear " . $data['customer_name'] . ",\n\nThis is a friendly reminder about your upcoming reservation at " . $data['venue_name'] . ".\n\nWe look forward to seeing you!\n\nBest regards,\nThe " . EmailConfig::RESTAURANT_NAME . " Team";
    }
}
?>

