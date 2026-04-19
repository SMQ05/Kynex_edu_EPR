<?php

declare(strict_types=1);

namespace App\Http\Controllers\Zkteco;

use App\Models\Tenant\AttendanceDevice;
use App\Models\Tenant\BiometricLog;
use App\Models\SchoolUser;
use App\Models\Tenant\Student;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * ZKTeco ADMS (Automated Data Master Server) Controller
 *
 * Implements the ZKTeco push protocol. The device periodically polls
 * our server via HTTP and pushes attendance logs when available.
 *
 * Protocol flow:
 *   1. Device sends GET /iclock/cdata?SN=xxx → we respond with registry settings
 *   2. Device sends GET /iclock/getrequest?SN=xxx → we respond with pending commands
 *   3. Device sends POST /iclock/cdata?SN=xxx&table=ATTLOG → attendance data
 *   4. Device sends POST /iclock/devicecmd?SN=xxx → command acknowledgement
 *
 * No authentication middleware — devices use serial number for identification.
 * The device IP should be whitelisted at the firewall/reverse-proxy level.
 */
class AdmsController extends Controller
{
    /**
     * GET /iclock/cdata — Device handshake / registry request
     * POST /iclock/cdata — Device pushes attendance logs (table=ATTLOG)
     */
    public function cdata(Request $request): Response
    {
        $serialNumber = $request->query('SN', '');

        if ($request->isMethod('GET')) {
            return $this->handleHandshake($serialNumber);
        }

        // POST — parse the table parameter
        $table = strtoupper($request->query('table', ''));

        if ($table === 'ATTLOG') {
            return $this->handleAttendanceLog($request, $serialNumber);
        }

        if ($table === 'OPERLOG') {
            return $this->handleOperationLog($request, $serialNumber);
        }

        Log::info("ADMS: Unknown table '{$table}' from device {$serialNumber}");

        return $this->ok();
    }

    /**
     * GET /iclock/getrequest — Device polls for pending commands
     *
     * Returns pending commands from the device's `pending_commands` JSON column.
     * Each command is sent one per line. After sending, commands are cleared.
     */
    public function getrequest(Request $request): Response
    {
        $serialNumber = $request->query('SN', '');
        $device = AttendanceDevice::where('serial_number', $serialNumber)->first();

        if (! $device) {
            return $this->ok();
        }

        // Update last heartbeat
        $device->update(['last_sync_at' => now()]);

        // Check for pending commands
        $commands = $device->pending_commands ?? [];

        if (empty($commands)) {
            return $this->ok();
        }

        // Send commands and clear the queue
        $device->update(['pending_commands' => []]);

        $commandString = implode("\n", $commands);

        return new Response($commandString, 200, [
            'Content-Type' => 'text/plain',
        ]);
    }

    /**
     * POST /iclock/devicecmd — Device acknowledges command execution
     */
    public function devicecmd(Request $request): Response
    {
        $serialNumber = $request->query('SN', '');

        Log::info("ADMS: Command ACK from device {$serialNumber}", [
            'body' => $request->getContent(),
        ]);

        return $this->ok();
    }

    // ── Private Handlers ──────────────────────────────────────────

    /**
     * Handle initial device handshake (GET /iclock/cdata).
     *
     * Returns registry-style configuration that tells the device:
     *   - How often to send attendance logs
     *   - Server timezone
     *   - Push protocol settings
     */
    private function handleHandshake(string $serialNumber): Response
    {
        $device = AttendanceDevice::where('serial_number', $serialNumber)->first();

        if ($device) {
            $device->update(['last_sync_at' => now()]);
        } else {
            // Auto-register unknown devices as inactive
            $device = AttendanceDevice::create([
                'name'          => "Auto-registered ({$serialNumber})",
                'device_type'   => 'zkteco_push',
                'serial_number' => $serialNumber,
                'is_active'     => false,
            ]);

            Log::info("ADMS: Auto-registered new device {$serialNumber}");
        }

        // Registry response tells the device its configuration
        $registry = implode("\n", [
            'GET OPTION FROM: ' . $serialNumber,
            'Stamp=9999',
            'OpStamp=9999',
            'PhotoStamp=9999',
            'ErrorDelay=60',
            'Delay=30',
            'TransTimes=00:00;14:05',
            'TransInterval=1',
            'TransFlag=TransData AttLog\tOpLog\tAttPhoto\tEnrollUser\tEnrollFP\tFPImag',
            'TimeZone=5',
            'Realtime=1',
            'Encrypt=0',
            'ServerVer=2.4.1',
            'PushProtVer=2.4.1',
        ]);

        return new Response($registry, 200, [
            'Content-Type' => 'text/plain',
        ]);
    }

    /**
     * Parse and store attendance log lines from device POST.
     *
     * Each line format: "PIN\tTIME\tSTATUS\tVERIFY\tWORKCODE\tRESERVED1\tRESERVED2"
     * - PIN = device user ID (our biometric_device_id)
     * - TIME = "YYYY-MM-DD HH:MM:SS"
     * - STATUS = 0:check-in, 1:check-out, 2:break-out, 3:break-in, 4:OT-in, 5:OT-out
     * - VERIFY = verification mode (0:password, 1:finger, 2:card, etc.)
     */
    private function handleAttendanceLog(Request $request, string $serialNumber): Response
    {
        $device = AttendanceDevice::where('serial_number', $serialNumber)->first();

        if (! $device) {
            Log::warning("ADMS: ATTLOG from unknown device {$serialNumber}");
            return $this->ok();
        }

        if (! $device->is_active) {
            Log::info("ADMS: Ignoring ATTLOG from inactive device {$serialNumber}");
            return $this->ok();
        }

        $device->update(['last_sync_at' => now()]);

        $body = $request->getContent();
        $lines = array_filter(explode("\n", $body), fn ($line) => trim($line) !== '');

        $created = 0;
        $skipped = 0;

        foreach ($lines as $line) {
            try {
                $parts = preg_split('/\t/', trim($line));

                if (count($parts) < 2) {
                    $skipped++;
                    continue;
                }

                $deviceUserId = trim($parts[0]);
                $punchTime    = trim($parts[1]);
                $status       = isset($parts[2]) ? (int) trim($parts[2]) : 0;
                $verifyMode   = isset($parts[3]) ? (int) trim($parts[3]) : 0;

                // Parse punch time
                $parsedTime = Carbon::parse($punchTime);

                // Determine punch type from status code
                $punchType = match ($status) {
                    0       => 'check_in',
                    1       => 'check_out',
                    2       => 'break_out',
                    3       => 'break_in',
                    4       => 'overtime_in',
                    5       => 'overtime_out',
                    default => 'unknown',
                };

                // Resolve device_user_id to our SchoolUser or Student
                $resolution = $this->resolveDeviceUser($deviceUserId);

                // Prevent duplicate entries
                $exists = BiometricLog::where('device_id', $device->id)
                    ->where('device_user_id', $deviceUserId)
                    ->where('punch_time', $parsedTime)
                    ->exists();

                if ($exists) {
                    $skipped++;
                    continue;
                }

                BiometricLog::create([
                    'device_id'       => $device->id,
                    'device_user_id'  => $deviceUserId,
                    'school_user_id'  => $resolution['school_user_id'],
                    'student_id'      => $resolution['student_id'],
                    'punch_time'      => $parsedTime,
                    'punch_type'      => $punchType,
                    'is_processed'    => false,
                ]);

                $created++;
            } catch (\Throwable $e) {
                Log::error("ADMS: Failed to parse ATTLOG line from {$serialNumber}: {$e->getMessage()}", [
                    'line' => $line,
                ]);
                $skipped++;
            }
        }

        Log::info("ADMS: Processed ATTLOG from {$serialNumber}: {$created} created, {$skipped} skipped");

        return $this->ok();
    }

    /**
     * Handle operation log from device (user enrollments, etc.)
     * We log these but don't act on them currently.
     */
    private function handleOperationLog(Request $request, string $serialNumber): Response
    {
        Log::info("ADMS: OPERLOG from device {$serialNumber}", [
            'body_length' => strlen($request->getContent()),
        ]);

        return $this->ok();
    }

    /**
     * Resolve a device_user_id to a SchoolUser or Student.
     *
     * Checks SchoolUser.biometric_device_id first, then Student.biometric_device_id.
     * Returns array with nullable school_user_id and student_id.
     */
    private function resolveDeviceUser(string $deviceUserId): array
    {
        $result = [
            'school_user_id' => null,
            'student_id'     => null,
        ];

        // Check staff first
        $staffUser = SchoolUser::where('biometric_device_id', $deviceUserId)->first();
        if ($staffUser) {
            $result['school_user_id'] = $staffUser->id;
            return $result;
        }

        // Check students
        $student = Student::where('biometric_device_id', $deviceUserId)->first();
        if ($student) {
            $result['student_id'] = $student->id;
            return $result;
        }

        return $result;
    }

    /**
     * Return a simple "OK" response that ZKTeco devices expect.
     */
    private function ok(): Response
    {
        return new Response('OK', 200, [
            'Content-Type' => 'text/plain',
        ]);
    }
}
