<?php
class Airalo_Logger {
    private $log_file;
    private $max_file_size = 5242880; // 5MB

    public function __construct() {
        if (!file_exists(AIRALO_API_LOG_DIR)) {
            wp_mkdir_p(AIRALO_API_LOG_DIR);
        }

        $this->log_file = AIRALO_API_LOG_DIR . 'airalo-api-' . date('Y-m-d') . '.log';
        $this->rotate_logs();
    }

    public function log($message, $context = []) {
        if (!is_string($message)) {
            $message = print_r($message, true);
        }

        $log_entry = sprintf(
            "[%s] %s %s\n",
            date('Y-m-d H:i:s'),
            $message,
            !empty($context) ? json_encode($context, JSON_PRETTY_PRINT) : ''
        );

        file_put_contents($this->log_file, $log_entry, FILE_APPEND | LOCK_EX);
    }

    private function rotate_logs() {
        if (!file_exists($this->log_file)) {
            return;
        }

        if (filesize($this->log_file) >= $this->max_file_size) {
            $backup_file = AIRALO_API_LOG_DIR . 'airalo-api-' . date('Y-m-d-His') . '.log';
            rename($this->log_file, $backup_file);
        }

        // Eliminar logs antiguos (más de 30 días)
        $files = glob(AIRALO_API_LOG_DIR . 'airalo-api-*.log');
        $now = time();

        foreach ($files as $file) {
            if (is_file($file) && ($now - filemtime($file)) >= 30 * DAY_IN_SECONDS) {
                unlink($file);
            }
        }
    }

    public function get_logs($days = 7) {
        $logs = [];
        $files = glob(AIRALO_API_LOG_DIR . 'airalo-api-*.log');
        $cutoff = time() - ($days * DAY_IN_SECONDS);

        foreach ($files as $file) {
            if (filemtime($file) >= $cutoff) {
                $logs[] = [
                    'date' => date('Y-m-d', filemtime($file)),
                    'file' => basename($file),
                    'size' => size_format(filesize($file))
                ];
            }
        }

        return array_reverse($logs);
    }

    public function get_log_content($filename) {
        $filepath = AIRALO_API_LOG_DIR . sanitize_file_name($filename);
        
        if (!file_exists($filepath) || strpos($filename, 'airalo-api-') !== 0) {
            return false;
        }

        return file_get_contents($filepath);
    }
}