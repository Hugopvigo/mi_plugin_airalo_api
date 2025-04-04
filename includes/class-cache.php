<?php
class Airalo_Cache {
    private $logger;
    private $prefix = 'airalo_api_';

    public function __construct($logger) {
        $this->logger = $logger;
    }

    public function get($key) {
        $key = $this->prefix . $key;
        $data = get_transient($key);

        if ($data === false) {
            $this->logger->log('Cache miss for key: ' . $key);
            return false;
        }

        $this->logger->log('Cache hit for key: ' . $key);
        return $data;
    }

    public function set($key, $data, $expiration = 0) {
        $key = $this->prefix . $key;
        $result = set_transient($key, $data, $expiration);

        if ($result) {
            $this->logger->log('Cache set for key: ' . $key . ' with expiration: ' . $expiration);
        } else {
            $this->logger->log('Cache set failed for key: ' . $key);
        }

        return $result;
    }

    public function delete($key) {
        $key = $this->prefix . $key;
        $result = delete_transient($key);

        if ($result) {
            $this->logger->log('Cache deleted for key: ' . $key);
        }

        return $result;
    }

    public function flush() {
        global $wpdb;
        $sql = "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_{$this->prefix}%' OR option_name LIKE '_transient_timeout_{$this->prefix}%'";
        $result = $wpdb->query($sql);
        $this->logger->log('Cache flushed. Removed ' . $result . ' items');
        return $result;
    }
}