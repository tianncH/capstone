<?php
/**
 * QR Code Fallback System
 * Provides multiple QR generation services for maximum reliability
 */

class QRFallback {
    private $services = [
        'qrserver' => 'https://api.qrserver.com/v1/create-qr-code/?size={size}&data={data}&format=png',
        'quickchart' => 'https://quickchart.io/qr?text={data}&size={size}',
        'qrserver_alt' => 'https://api.qrserver.com/v1/create-qr-code/?size={size}&data={data}'
    ];
    
    /**
     * Generate QR code with automatic fallback
     */
    public function generateQR($data, $size = 200) {
        // Try each service in order
        foreach ($this->services as $service_name => $url_template) {
            $url = $this->buildURL($url_template, $data, $size);
            
            if ($this->testURL($url)) {
                return [
                    'success' => true,
                    'url' => $url,
                    'service' => $service_name
                ];
            }
        }
        
        // If all services fail, return a data URI placeholder
        return [
            'success' => false,
            'url' => $this->generatePlaceholder($size),
            'service' => 'placeholder'
        ];
    }
    
    /**
     * Build URL for a specific service
     */
    private function buildURL($template, $data, $size) {
        return str_replace(
            ['{data}', '{size}'],
            [urlencode($data), $size],
            $template
        );
    }
    
    /**
     * Test if URL is accessible
     */
    private function testURL($url) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        
        $result = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        return $http_code == 200;
    }
    
    /**
     * Generate a placeholder SVG if all services fail
     */
    private function generatePlaceholder($size) {
        $svg = '<svg width="' . $size . '" height="' . $size . '" xmlns="http://www.w3.org/2000/svg">';
        $svg .= '<rect width="100%" height="100%" fill="#f8f9fa" stroke="#dee2e6" stroke-width="2"/>';
        $svg .= '<text x="50%" y="50%" font-family="Arial" font-size="14" fill="#6c757d" text-anchor="middle" dy=".3em">QR Code Unavailable</text>';
        $svg .= '</svg>';
        
        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }
    
    /**
     * Get service status for monitoring
     */
    public function getServiceStatus() {
        $status = [];
        $test_data = 'https://www.google.com';
        
        foreach ($this->services as $service_name => $url_template) {
            $url = $this->buildURL($url_template, $test_data, 200);
            $status[$service_name] = [
                'url' => $url,
                'working' => $this->testURL($url)
            ];
        }
        
        return $status;
    }
}
?>






