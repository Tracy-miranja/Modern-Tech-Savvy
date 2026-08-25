<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\BiometricDevice;
use App\Services\DeviceAttendanceService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class DevicePushController extends Controller
{
    public function __construct(protected DeviceAttendanceService $attendanceService)
    {
    }

    public function handle(Request $request, string $vendor, string $token)
    {
        $device = BiometricDevice::where('webhook_token', $token)->where('is_active', true)->first();
        if (!$device) {
            return response('Unknown or inactive device.', 404);
        }

        return match ($vendor) {
            'zkteco'    => $this->zktecoPush($request, $device),
            'hikvision' => $this->hikvisionPush($request, $device),
            default     => $this->genericPush($request, $device),
        };
    }

    // --- Push SDK style ZKTeco (custom webhook URL) -------------------

    protected function zktecoPush(Request $request, BiometricDevice $device)
    {
        if ($request->isMethod('get')) {
            return $this->zktecoHandshake($device);
        }

        $table = $request->query('table');
        if ($table && strtoupper($table) !== 'ATTLOG') {

            return response('OK', 200)->header('Content-Type', 'text/plain');
        }

        $this->processZktecoAttlogBody($device, $request->getContent());

        return response('OK', 200)->header('Content-Type', 'text/plain');
    }

    // --- Classic ADMS ZKTeco (fixed /iclock/* paths, SN-identified) ---

    public function zktecoCdata(Request $request)
    {
        $device = $this->resolveBySerial($request);
        if (!$device) {
            return response('Unregistered device.', 200)->header('Content-Type', 'text/plain');
        }

        if ($request->isMethod('get')) {
            return $this->zktecoHandshake($device);
        }

        $table = $request->query('table');
        if ($table && strtoupper($table) !== 'ATTLOG') {
            return response('OK', 200)->header('Content-Type', 'text/plain');
        }

        $this->processZktecoAttlogBody($device, $request->getContent());

        return response('OK', 200)->header('Content-Type', 'text/plain');
    }

    public function zktecoGetRequest(Request $request)
    {
        $device = $this->resolveBySerial($request);
        if ($device) {
            $device->update(['last_seen_at' => now()]);
        }

        return response('OK', 200)->header('Content-Type', 'text/plain');
    }

    public function zktecoDeviceCmd(Request $request)
    {
        $device = $this->resolveBySerial($request);
        if ($device) {
            $device->update(['last_seen_at' => now()]);
        }

        return response('OK', 200)->header('Content-Type', 'text/plain');
    }

    protected function resolveBySerial(Request $request): ?BiometricDevice
    {
        $sn = $request->query('SN');
        if (!$sn) {
            return null;
        }

        return BiometricDevice::where('vendor', 'zkteco')
            ->where('serial_number', $sn)
            ->where('is_active', true)
            ->first();
    }

    protected function zktecoHandshake(BiometricDevice $device): Response
    {
        $device->update(['last_seen_at' => now()]);

        $body = implode("\r\n", [
            'GET OPTION FROM: ' . ($device->serial_number ?: $device->webhook_token),
            'Stamp=9999',
            'OpStamp=9999',
            'ErrorDelay=30',
            'Delay=30',
            'TransFlag=TransData AttLog=1',
            'Realtime=1',
            'Encrypt=0',
        ]);

        return response($body, 200)->header('Content-Type', 'text/plain');
    }

    protected function processZktecoAttlogBody(BiometricDevice $device, string $body): void
    {
        $lines = preg_split('/\r\n|\r|\n/', trim($body));

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            $fields = preg_split('/\t+/', $line);
            $pin = $fields[0] ?? null;
            $timeRaw = $fields[1] ?? null;

            if (!$pin || !$timeRaw) {
                continue;
            }

            try {
                $punchedAt = Carbon::parse($timeRaw, 'Africa/Nairobi');
            } catch (\Throwable $e) {
                Log::warning('DevicePushController: unparseable ZKTeco ATTLOG timestamp', ['device_id' => $device->id, 'line' => $line]);
                continue;
            }

            $this->attendanceService->processPunch($device, $pin, $punchedAt, ['raw_line' => $line]);
        }
    }

    // --- Hikvision ISAPI event push ------------------------------------

    protected function hikvisionPush(Request $request, BiometricDevice $device)
    {
        if ($request->isMethod('get')) {
            $device->update(['last_seen_at' => now()]);
            return response('OK', 200);
        }

        $payload = $this->extractHikvisionPayload($request);
        if (!$payload) {
            return response('OK', 200);
        }

        $event = $payload['AccessControllerEvent'] ?? $payload;
        $pin = $event['employeeNoString'] ?? $event['employeeNo'] ?? null;
        $timeRaw = $payload['dateTime'] ?? $event['time'] ?? null;

        if ($pin && $timeRaw) {
            try {
                $punchedAt = Carbon::parse($timeRaw)->timezone('Africa/Nairobi');
            } catch (\Throwable $e) {
                $punchedAt = now('Africa/Nairobi');
            }

            $this->attendanceService->processPunch($device, (string) $pin, $punchedAt, $payload);
        } else {
            $device->update(['last_seen_at' => now()]);
        }

        return response('OK', 200);
    }

    protected function extractHikvisionPayload(Request $request): ?array
    {

        if (str_contains((string) $request->header('Content-Type'), 'application/json')) {
            $decoded = json_decode($request->getContent(), true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        $eventLog = $request->input('event_log');
        if ($eventLog) {
            $decoded = json_decode($eventLog, true);
            if (is_array($decoded)) {
                return $decoded;
            }

            if (str_starts_with(trim($eventLog), '<')) {
                try {
                    $xml = simplexml_load_string($eventLog);
                    return $xml ? json_decode(json_encode($xml), true) : null;
                } catch (\Throwable $e) {
                    return null;
                }
            }
        }

        $decoded = json_decode($request->getContent(), true);
        return is_array($decoded) ? $decoded : null;
    }

    // --- Generic JSON push (manual integrations, testing) --------------

    protected function genericPush(Request $request, BiometricDevice $device)
    {
        if ($request->isMethod('get')) {
            $device->update(['last_seen_at' => now()]);
            return response()->json(['status' => 'ok']);
        }

        $data = $request->isJson() ? ($request->json()->all() ?: []) : $request->all();
        $pin = $data['pin'] ?? $data['device_pin'] ?? $data['employee_code'] ?? null;
        $timeRaw = $data['time'] ?? $data['punched_at'] ?? null;

        if (!$pin) {
            return response()->json(['status' => 'error', 'message' => 'Missing pin.'], 422);
        }

        try {
            $punchedAt = $timeRaw ? Carbon::parse($timeRaw, 'Africa/Nairobi') : now('Africa/Nairobi');
        } catch (\Throwable $e) {
            $punchedAt = now('Africa/Nairobi');
        }

        $log = $this->attendanceService->processPunch($device, (string) $pin, $punchedAt, $data);

        return response()->json(['status' => $log->status, 'message' => $log->message]);
    }
}
