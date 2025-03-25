<?php

namespace CourseManager;

/**
 * Log helper class.
 */
class Log {
    /**
     * Log a message to the error log.
     *
     * @param string $msg The message to log.
     * @return void
     */
    public static function log(string $msg): void {
        error_log('[CourseManager] ' . $msg);
    }
}