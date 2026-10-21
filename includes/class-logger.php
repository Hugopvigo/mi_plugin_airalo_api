<?php
class Airalo_Logger {
    private $log_file;
    private $max_file_size = 5242880; // 5MB
    private static $instance = null;

    /**
     * Instancia compartida del logger.
     *
     * @return Airalo_Logger
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function __construct() {
        if (!file_exists(AIRALO_API_LOG_DIR)) {
            if (function_exists('wp_mkdir_p')) {
                wp_mkdir_p(AIRALO_API_LOG_DIR);
            } else {
                @mkdir(AIRALO_API_LOG_DIR, 0755, true);
            }
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

        $log_dir = dirname($this->log_file);

        if (!is_dir($log_dir) || !is_writable($log_dir)) {
            // Si el directorio de logs no existe o no es escribible, no se rompe
            // el panel: el aviso va al log de PHP.
            error_log('[Airalo API] ' . $log_entry);
            return;
        }

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

    /**
     * Últimas entradas del log, de la más reciente a la más antigua.
     *
     * Cada entrada es un array con las claves `time` y `message`, que es lo que
     * espera la plantilla del dashboard.
     *
     * @param int $limit Número máximo de entradas.
     * @return array<int,array{time:string,message:string}>
     */
    public function get_recent_logs($limit = 5) {
        $limit = max(1, (int) $limit);
        $entries = array();

        foreach ($this->get_logs(1) as $log) {
            $content = $this->get_log_content($log['file']);

            if (false === $content || '' === $content) {
                continue;
            }

            $lines = array_reverse(array_filter(explode("\n", $content), 'strlen'));
            $count = 0;

            foreach ($lines as $line) {
                if (!preg_match('/^\[([^\]]+)\]\s*(.*)$/', $line, $matches)) {
                    continue;
                }

                $entries[] = array(
                    'time'    => $matches[1],
                    'message' => trim($matches[2]),
                );

                $count++;

                if ($count >= $limit) {
                    break;
                }
            }

            if (count($entries) >= $limit) {
                break;
            }
        }

        return $entries;
    }
}