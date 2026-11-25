<?php

namespace App\Services;

use RuntimeException;

/**
 * Minimal WebSocket client for the Kubernetes pods/exec endpoint.
 *
 * Only speaks the non-interactive subset we need: send one request, stream the
 * response until the server closes, cap it. Sub-protocol v4.channel.k8s.io
 * prefixes every binary frame with a channel byte (1 stdout, 2 stderr, 3 status).
 */
class KubernetesExec
{
    private const SA_PATH = '/var/run/secrets/kubernetes.io/serviceaccount';
    private const MAX_OUTPUT = 65536;

    /** @throws RuntimeException */
    public function run(string $namespace, string $pod, string $container, array $command, int $timeout = 15): string
    {
        $token = $this->token();
        if ($token === null) {
            throw new RuntimeException('service account token not found');
        }

        // The apiserver wants the argv as repeated `command=` params; an array
        // here would serialise as command[0]=… and be silently ignored.
        $query = http_build_query([
            'container' => $container,
            'stdout' => 'true',
            'stderr' => 'true',
            'tty' => 'false',
        ]);
        foreach ($command as $arg) {
            $query .= '&command='.rawurlencode($arg);
        }
        $path = "/api/v1/namespaces/{$namespace}/pods/{$pod}/exec?{$query}";
        $key = base64_encode(random_bytes(16));

        $socket = $this->connect($timeout);
        stream_set_timeout($socket, $timeout);

        $request = "GET {$path} HTTP/1.1\r\n"
            ."Host: kubernetes.default.svc\r\n"
            ."Authorization: Bearer {$token}\r\n"
            ."Connection: Upgrade\r\n"
            ."Upgrade: websocket\r\n"
            ."Sec-WebSocket-Version: 13\r\n"
            ."Sec-WebSocket-Key: {$key}\r\n"
            ."Sec-WebSocket-Protocol: v4.channel.k8s.io\r\n\r\n";

        if (fwrite($socket, $request) === false) {
            fclose($socket);
            throw new RuntimeException('failed to write exec request');
        }

        $head = '';
        while (! str_contains($head, "\r\n\r\n")) {
            $chunk = fread($socket, 1024);
            if ($chunk === false || $chunk === '') {
                fclose($socket);
                throw new RuntimeException('exec handshake failed');
            }
            $head .= $chunk;
            if (strlen($head) > 8192) {
                fclose($socket);
                throw new RuntimeException('exec handshake too large');
            }
        }

        if (! preg_match('#^HTTP/1\.[01] 101#', $head)) {
            $status = strtok($head, "\r\n");
            fclose($socket);
            throw new RuntimeException('exec rejected: '.$status);
        }

        // Discard any frame bytes that arrived in the same read as the headers.
        $buffer = substr($head, strpos($head, "\r\n\r\n") + 4);
        $output = '';

        while (true) {
            $frame = $this->readFrame($socket, $buffer);
            if ($frame === null) {
                break;
            }
            if ($frame['opcode'] === 0x8) {
                break;
            }
            if ($frame['opcode'] !== 0x2 || $frame['payload'] === '') {
                continue;
            }
            $channel = ord($frame['payload'][0]);
            $text = substr($frame['payload'], 1);

            if ($channel === 3) {
                $status = trim($text);
                if ($status !== '' && $status !== 'Success' && ! str_starts_with($status, '{')) {
                    $output .= "\n[status] {$status}";
                }
                continue;
            }

            $output .= $text;

            if (strlen($output) >= self::MAX_OUTPUT) {
                $output = substr($output, 0, self::MAX_OUTPUT)."\n[output truncated]";
                break;
            }
        }

        $this->close($socket);
        fclose($socket);

        return $output === '' ? '[no output]' : $output;
    }

    private function connect(int $timeout)
    {
        $context = stream_context_create(['ssl' => [
            'verify_peer' => true,
            'verify_peer_name' => true,
            'peer_name' => 'kubernetes.default.svc',
            'cafile' => self::SA_PATH.'/ca.crt',
            'SNI_enabled' => true,
        ]]);

        $socket = @stream_socket_client(
            'ssl://kubernetes.default.svc:443',
            $errno,
            $errstr,
            $timeout,
            STREAM_CLIENT_CONNECT,
            $context
        );

        if ($socket === false) {
            throw new RuntimeException("exec connect failed: {$errstr} ({$errno})");
        }

        return $socket;
    }

    /**
     * @return array{opcode:int,payload:string}|null
     */
    private function readFrame($socket, string &$buffer): ?array
    {
        if (! $this->fill($socket, $buffer, 2)) {
            return null;
        }

        $b1 = ord($buffer[0]);
        $b2 = ord($buffer[1]);
        $opcode = $b1 & 0x0F;
        $length = $b2 & 0x7F;
        $offset = 2;

        if ($length === 126) {
            if (! $this->fill($socket, $buffer, 4)) {
                return null;
            }
            $length = unpack('n', substr($buffer, 2, 2))[1];
            $offset = 4;
        } elseif ($length === 127) {
            if (! $this->fill($socket, $buffer, 10)) {
                return null;
            }
            $length = unpack('J', substr($buffer, 2, 8))[1];
            $offset = 10;
        }

        if (! $this->fill($socket, $buffer, $offset + $length)) {
            return null;
        }

        $payload = substr($buffer, $offset, $length);
        $buffer = substr($buffer, $offset + $length);

        return ['opcode' => $opcode, 'payload' => $payload];
    }

    private function fill($socket, string &$buffer, int $needed): bool
    {
        while (strlen($buffer) < $needed) {
            $chunk = fread($socket, max(1, $needed - strlen($buffer)));
            if ($chunk === false || $chunk === '') {
                return false;
            }
            $buffer .= $chunk;
        }

        return true;
    }

    private function close($socket): void
    {
        fwrite($socket, $this->mask('', 0x8));
    }

    private function mask(string $payload, int $opcode): string
    {
        $length = strlen($payload);
        $header = chr(0x80 | $opcode);

        if ($length < 126) {
            $header .= chr(0x80 | $length);
        } elseif ($length < 65536) {
            $header .= chr(0x80 | 126).pack('n', $length);
        } else {
            $header .= chr(0x80 | 127).pack('J', $length);
        }

        $maskKey = random_bytes(4);
        $masked = '';
        for ($i = 0; $i < $length; $i++) {
            $masked .= $payload[$i] ^ $maskKey[$i % 4];
        }

        return $header.$maskKey.$masked;
    }

    private function token(): ?string
    {
        $path = self::SA_PATH.'/token';

        return is_readable($path) ? trim((string) file_get_contents($path)) : null;
    }
}