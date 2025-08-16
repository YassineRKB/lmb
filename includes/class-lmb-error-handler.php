<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Enhanced error handling and logging system
 */
class LMB_Error_Handler {
    private static $log_file;
    private static $max_log_size = 10485760; // 10MB
    
    public static function init() {
        $upload_dir = wp_upload_dir();
        self::$log_file = $upload_dir['basedir'] . '/lmb-errors.log';
        
        #add_action('wp_loaded', [__CLASS__, 'setup_error_handling']);
        add_action('admin_menu', [__CLASS__, 'add_logs_page']);
    }
    
    /**
     * Log error with context
     */
    public static function log_error($message, $context = []) {
        $timestamp = current_time('mysql');
        $user_id = get_current_user_id();
        $ip = self::get_client_ip();
        
        $log_entry = sprintf(
            "[%s] [User:%d] [IP:%s] %s %s\n",
            $timestamp,
            $user_id,
            $ip,
            $message,
            !empty($context) ? json_encode($context, JSON_UNESCAPED_UNICODE) : ''
        );
        
        // Rotate log if too large
        if (file_exists(self::$log_file) && filesize(self::$log_file) > self::$max_log_size) {
            self::rotate_log();
        }
        
        error_log($log_entry, 3, self::$log_file);
    }
    
    /**
     * Handle form submission errors
     */
    public static function handle_form_error($error, $form_data = []) {
        $message = is_wp_error($error) ? $error->get_error_message() : (string) $error;
        
        self::log_error('Form submission error: ' . $message, [
            'form_data' => self::sanitize_log_data($form_data),
            'user_id' => get_current_user_id(),
            'url' => $_SERVER['REQUEST_URI'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ]);
    }
    
    /**
     * Handle points transaction errors
     */
    public static function handle_points_error($error, $user_id, $amount, $reason) {
        self::log_error('Points transaction error: ' . $error, [
            'user_id' => $user_id,
            'amount' => $amount,
            'reason' => $reason,
            'current_balance' => LMB_Points::get($user_id)
        ]);
    }
    
    /**
     * Get client IP address
     */
    private static function get_client_ip() {
        $ip_keys = [
            'HTTP_CF_CONNECTING_IP',
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        ];
        
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) && !empty($_SERVER[$key])) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                        return $ip;
                    }
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
    
    /**
     * Sanitize data for logging (remove sensitive info)
     */
    private static function sanitize_log_data($data) {
        if (!is_array($data)) {
            return $data;
        }
        
        $sensitive_keys = ['password', 'token', 'key', 'secret', 'credit_card'];
        
        foreach ($data as $key => $value) {
            foreach ($sensitive_keys as $sensitive) {
                if (stripos($key, $sensitive) !== false) {
                    $data[$key] = '[REDACTED]';
                    break;
                }
            }
        }
        
        return $data;
    }
    
    /**
     * Rotate log file
     */
    private static function rotate_log() {
        if (file_exists(self::$log_file)) {
            $backup_file = self::$log_file . '.old';
            if (file_exists($backup_file)) {
                unlink($backup_file);
            }
            rename(self::$log_file, $backup_file);
        }
    }
    
    /**
     * Add logs page to admin menu
     */
    public static function add_logs_page() {
        add_submenu_page(
            'lmb-core',
            __('Error Logs', 'lmb-core'),
            __('Error Logs', 'lmb-core'),
            'manage_options',
            'lmb-error-logs',
            [__CLASS__, 'render_logs_page']
        );
    }
    
    /**
     * Render logs page
     */
    public static function render_logs_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'lmb-core'));
        }
        
        // Handle log clearing
        if (isset($_POST['clear_logs']) && wp_verify_nonce($_POST['_wpnonce'], 'clear_logs')) {
            if (file_exists(self::$log_file)) {
                unlink(self::$log_file);
            }
            echo '<div class="notice notice-success"><p>' . __('Logs cleared successfully.', 'lmb-core') . '</p></div>';
        }
        
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('LMB Error Logs', 'lmb-core'); ?></h1>
            
            <form method="post" style="margin-bottom: 20px;">
                <?php wp_nonce_field('clear_logs'); ?>
                <button type="submit" name="clear_logs" class="button button-secondary" 
                        onclick="return confirm('<?php esc_attr_e('Are you sure you want to clear all logs?', 'lmb-core'); ?>')">
                    <?php esc_html_e('Clear Logs', 'lmb-core'); ?>
                </button>
            </form>
            
            <div style="background: #f1f1f1; padding: 20px; border-radius: 5px; max-height: 600px; overflow-y: auto;">
                <pre style="white-space: pre-wrap; font-family: monospace; font-size: 12px;">
                    <?php
                    if (file_exists(self::$log_file)) {
                        $logs = file_get_contents(self::$log_file);
                        // Show last 100 lines
                        $lines = explode("\n", $logs);
                        $lines = array_slice($lines, -100);
                        echo esc_html(implode("\n", $lines));
                    } else {
                        esc_html_e('No logs found.', 'lmb-core');
                    }
                    ?>
                </pre>
            </div>
        </div>
        <?php
    }
    
    /**
     * Get recent errors for dashboard
     */
    public static function get_recent_errors($limit = 10) {
        if (!file_exists(self::$log_file)) {
            return [];
        }
        
        $logs = file_get_contents(self::$log_file);
        $lines = explode("\n", trim($logs));
        $lines = array_filter($lines);
        $lines = array_slice($lines, -$limit);
        
        $errors = [];
        foreach ($lines as $line) {
            if (preg_match('/\[(.*?)\].*?error/i', $line, $matches)) {
                $errors[] = [
                    'timestamp' => $matches[1] ?? '',
                    'message' => $line
                ];
            }
        }
        
        return array_reverse($errors);
    }
}

// Initialize error handler
LMB_Error_Handler::init();