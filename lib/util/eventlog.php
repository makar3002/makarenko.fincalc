<?php

namespace makarenko\fincalc\util;

use CEventLog;
use Throwable;

class EventLog {
    public static function add(Throwable $e): void {
        $message = array();
        $message[] = 'Error: ' . $e->getMessage();
        $message[] = 'File: ' . $e->getFile();
        $message[] = 'Line: ' . $e->getLine();
        $message[] = $e->getTraceAsString();

        CEventLog::Add(array(
                'MODULE_ID' => 'intervolga.admitad',
                'DESCRIPTION' => implode(' ____ ', $message),
        ));
    }

    public static function addMessage(string $message): void {
        CEventLog::Add(array(
                'MODULE_ID' => 'intervolga.admitad',
                'DESCRIPTION' => $message,
        ));
    }
}
