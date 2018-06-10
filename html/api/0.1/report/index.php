<?php
    require_once(__DIR__ . '/../../../../functions.php');

    header('Content-Type: application/json');

    if (!isset($_REQUEST['key']) && !isset($_REQUEST['command']) && !isset($_REQUEST['args'])) {
        die(json_encode(['error' => 'Not all data was provided.']));
    }

    $key = trim($_REQUEST['key']);
    $command = trim($_REQUEST['command']);
    $args = trim($_REQUEST['args']);

    /**
     * Send a message to Socket Server..
     */
    function sendToServer($key, $command, $args) {
        global $config;

        $result = [];
        $fp = fsockopen($config['listen']['host'], $config['listen']['port'], $errno, $errstr, 30);
        if ($fp) {
            $sysout = '// Remote IP ' . $_SERVER['REMOTE_ADDR'] . "\n";
            fwrite($fp, $sysout);

            $userout = '-- ' . $key . ' ' . $command . ' ' . $args . "\n";
            fwrite($fp, $userout);

            $response = '';
            while (!feof($fp)) { $response .= fread($fp, 8192); }
            $result = ['request' => $userout, 'response' => [], 'error' => []];

            foreach (explode("\n", trim($response)) as $line) {
                if (preg_match('#^\[-- Err\] (.*)#', $line, $m)) {
                    $result['error'][] = $m[2];
                }

                $result['response'][] = $line;
            }

            if (empty($result['error'])) { unset($result['error']); }
            if (empty($result['response'])) { unset($result['response']); }

            fclose($fp);
        } else {
            $result = ['error' => $errstr];
        }

        return $result;
    }

    die(json_encode(sendToServer($key, $command, $args)));
