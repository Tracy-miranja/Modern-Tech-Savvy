<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Business;
use App\Models\Overtime;
use App\Models\Attendance;
use Illuminate\Http\Request;
use App\Http\RequestResponse;
use App\Traits\HandleTransactions;
use Illuminate\Support\Facades\Log;

class AttendanceController extends Controller
{
    use HandleTransactions;

    public function fetch(Request $request)
    {
        $business = Business::findBySlug(session('active_business_slug'));

        $dateInput = $request->input('date');

        if ($dateInput) {
            try {
                $date = Carbon::parse($dateInput, 'Africa/Nairobi')->format('Y-m-d');
            } catch (\Exception $e) {
                Log::error("Invalid date format: " . $dateInput . " - Error: " . $e->getMessage());
                return RequestResponse::badRequest('Invalid date format provided.');
            }
        } else {
            $date = now('Africa/Nairobi')->format('Y-m-d');
        }

        $attendances = Attendance::where('business_id', $business->id)
            ->whereDate('date', $date)
            ->with('employee')
            ->orderBy('date', 'desc')
            ->get();

        $attendanceTable = view('attendances._attendance_table', compact('attendances'))->render();
        return RequestResponse::ok('Attendance records fetched successfully.', $attendanceTable);
    }

    public function monthly(Request $request)
    {
        $business = Business::findBySlug(session('active_business_slug'));

        $year = now('Africa/Nairobi')->year;
        $month = $request->input('month')
            ? Carbon::createFromDate($year, intval($request->input('month')), 1, 'Africa/Nairobi')
            : now('Africa/Nairobi');

        $startDate = $month->copy()->startOfMonth();
        $endDate = $month->copy()->endOfMonth();
        $daysInMonth = $endDate->day;

        $startDate = $startDate->toDateString();
        $endDate = $endDate->toDateString();

        $attendances = Attendance::where('business_id', $business->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->with('employee')
            ->get();

        $attendanceData = [];
        foreach ($attendances as $attendance) {
            $employeeId = $attendance->employee_id;
            $day = $attendance->date->day;
            $attendanceData[$employeeId][$day] = $attendance;
        }

        $attendanceTable = view('attendances._monthly_attendance_table', [
            'attendanceData' => $attendanceData,
            'daysInMonth' => $daysInMonth,
            'month' => $month->format('F Y'),
        ])->render();

        return RequestResponse::ok('Ok.', $attendanceTable);
    }

    public function clockIn(Request $request)
    {
        return $this->handleTransaction(function () use ($request) {
            $user     = auth()->user();
            $business = Business::findBySlug(session('active_business_slug'));
            $today    = now('Africa/Nairobi')->toDateString();

            // Determine employee_id based on active role
            $employee_id = null;
            $active_role = session('active_role');
            $selfPunch   = ($active_role === 'business-employee');

            if ($selfPunch) {
                $employee = $user->employee;
                if (!$employee) {
                    return RequestResponse::badRequest('No employee record found for this user.');
                }
                $employee_id = $employee->id;

                if ($request->has('employee_id') && (int)$request->input('employee_id') !== (int)$employee_id) {
                    return RequestResponse::badRequest('Unauthorized: Employees can only clock in for themselves.');
                }
            } else {
                if (!in_array($active_role, ['business-admin','business-hr','business-finance'])) {
                    return RequestResponse::badRequest('Unauthorized: Only admins or employees can clock in.');
                }
                $validatedData = $request->validate([
                    'employee_id' => 'required|exists:employees,id',
                    'is_absent'   => 'sometimes|boolean',
                    'remarks'     => 'nullable|string|max:255',
                ]);
                $employee_id = (int)$validatedData['employee_id'];
            }

            // Enforce unique per day
            $existing = Attendance::where([
                'employee_id' => $employee_id,
                'business_id' => $business->id,
                'date'        => $today,
            ])->first();

            if ($existing) {
                if (!is_null($existing->clock_out)) {
                    return RequestResponse::badRequest('You have already completed today’s attendance.');
                }
                return RequestResponse::badRequest('You are already clocked in.');
            }

            // -------- NEW: capture punch meta (coords + MAC) --------
            $device_mac = $request->input('device_mac');      // nullable
            $punch_lat  = $request->input('latitude');        // nullable
            $punch_lng  = $request->input('longitude');       // nullable

            // Validation rules depend on flags (only when selfPunch)
            if ($selfPunch && $business->enforce_geofence) {
                $request->validate([
                    'latitude'  => 'required|numeric|between:-90,90',
                    'longitude' => 'required|numeric|between:-180,180',
                ], [
                    'latitude.required'  => 'Location is required to clock in.',
                    'longitude.required' => 'Location is required to clock in.',
                ]);

                $fences = $this->getAllowedGeofences($business);
                if (empty($fences)) {
                    return RequestResponse::badRequest('Geofence is enabled but no coordinates are configured.');
                }

                if (!$this->isWithinAnyFence((float)$punch_lat, (float)$punch_lng, $fences)) {
                    return RequestResponse::badRequest('You are outside the allowed clock-in area.');
                }
            }

            if ($selfPunch && $business->enforce_mac) {
                $request->validate([
                    'device_mac' => 'required|string|max:64',
                ], [
                    'device_mac.required' => 'A registered device is required to clock in.',
                ]);

                $employee = $user->employee; // already checked
                $registered = $employee->registered_device_mac;
                if (!$registered) {
                    return RequestResponse::badRequest('No registered device found. Contact HR to register your device.');
                }
                // Normalize compare (case-insensitive, strip separators)
                $norm = fn($m) => strtolower(preg_replace('/[^a-f0-9]/i', '', (string)$m));
                if ($norm($device_mac) !== $norm($registered)) {
                    return RequestResponse::badRequest('This device is not authorized for clock-ins.');
                }
            }
            // -------- END NEW --------

            Attendance::create([
                'employee_id'     => $employee_id,
                'business_id'     => $business->id,
                'date'            => $today,
                'clock_in'        => now('Africa/Nairobi')->format('H:i:s'),
                'is_absent'       => $request->input('is_absent', false),
                'remarks'         => $request->input('remarks'),
                'logged_by'       => $user->id,
                // NEW audit fields
                'device_mac'      => $device_mac,
                'punch_latitude'  => $punch_lat ? (float)$punch_lat : null,
                'punch_longitude' => $punch_lng ? (float)$punch_lng : null,
            ]);

            return RequestResponse::created('Clock-in successful.');
        });
    }

    public function clockIns(Request $request)
    {
        $business = Business::findBySlug(session('active_business_slug'));
        $date = now('Africa/Nairobi')->format('Y-m-d');

        $query = Attendance::where('business_id', $business->id)
            ->whereDate('date', $date)
            ->with(['employee', 'employee.user']);

        $clockins = $query->orderBy('created_at', 'desc')->get();
        $clockinsCards = view('attendances._clock_ins', compact('clockins'))->render();

        return RequestResponse::ok('Ok.', $clockinsCards ?: '<p>No clock-ins found for today.</p>');
    }

    public function clockOut(Request $request)
    {
        return $this->handleTransaction(function () use ($request) {
            $user     = auth()->user();
            $business = Business::findBySlug(session('active_business_slug'));
            $today    = now('Africa/Nairobi')->toDateString();

            // Determine employee_id
            $active_role = session('active_role');
            $selfPunch   = ($active_role === 'business-employee');
            $employee_id = null;

            if ($selfPunch) {
                $employee = $user->employee;
                if (!$employee) {
                    return RequestResponse::badRequest('No employee record found for this user.');
                }
                $employee_id = $employee->id;
                if ($request->has('employee') && (int)$request->input('employee') !== (int)$employee_id) {
                    return RequestResponse::badRequest('Unauthorized: Employees can only clock out themselves.');
                }
            } else {
                $validated = $request->validate([
                    'employee' => 'required|exists:employees,id',
                    'remarks'  => 'nullable|string|max:255',
                ]);
                $employee_id = (int)$validated['employee'];
            }

            $attendance = Attendance::where([
                'employee_id' => $employee_id,
                'business_id' => $business->id,
                'date'        => $today,
            ])->first();

            if (!$attendance || !$attendance->clock_in) {
                return RequestResponse::badRequest('You need to clock in first.');
            }
            if ($attendance->clock_out) {
                return RequestResponse::badRequest('You have already clocked out today.');
            }

            // NEW: capture meta (coords + MAC)
            $device_mac = $request->input('device_mac');
            $punch_lat  = $request->input('latitude');
            $punch_lng  = $request->input('longitude');

            if ($selfPunch && $business->enforce_geofence) {
                $request->validate([
                    'latitude'  => 'required|numeric|between:-90,90',
                    'longitude' => 'required|numeric|between:-180,180',
                ]);
                $fences = $this->getAllowedGeofences($business);
                if (empty($fences)) {
                    return RequestResponse::badRequest('Geofence is enabled but no coordinates are configured.');
                }
                if (!$this->isWithinAnyFence((float)$punch_lat, (float)$punch_lng, $fences)) {
                    return RequestResponse::badRequest('You are outside the allowed clock-out area.');
                }
            }

            if ($selfPunch && $business->enforce_mac) {
                $request->validate(['device_mac' => 'required|string|max:64']);
                $registered = optional($user->employee)->registered_device_mac;
                if (!$registered) {
                    return RequestResponse::badRequest('No registered device found. Contact HR to register your device.');
                }
                $norm = fn($m) => strtolower(preg_replace('/[^a-f0-9]/i', '', (string)$m));
                if ($norm($device_mac) !== $norm($registered)) {
                    return RequestResponse::badRequest('This device is not authorized for clock-outs.');
                }
            }

            $attendance->update([
                'clock_out'       => now('Africa/Nairobi')->format('H:i:s'),
                'overtime_hours'  => max(0, \Carbon\Carbon::parse($attendance->clock_in, 'Africa/Nairobi')->diffInHours(now('Africa/Nairobi')) - 8),
                'remarks'         => $request->input('remarks', $attendance->remarks),
                // record last punch meta
                'device_mac'      => $device_mac ?: $attendance->device_mac,
                'punch_latitude'  => $punch_lat ? (float)$punch_lat : $attendance->punch_latitude,
                'punch_longitude' => $punch_lng ? (float)$punch_lng : $attendance->punch_longitude,
            ]);

            return RequestResponse::created('Clock-out recorded successfully.');
        });
    }


    private function getOvertimeRate($business)
    {
        return $business->overtime_rate ?? 1.5;
    }

        // Default radius if not provided
    private const DEFAULT_RADIUS_M = 150;

    /** Haversine distance in meters */
    private function distanceMeters(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earth = 6371000; // meters
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat/2) * sin($dLat/2)
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2))
            * sin($dLng/2) * sin($dLng/2);
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        return $earth * $c;
    }

    /** Collect all allowed geofences for this business */
    private function getAllowedGeofences(\App\Models\Business $business): array
    {
        $fences = [];

        // 1) From locations table
        if (method_exists($business, 'locations')) {
            foreach ($business->locations()->whereNotNull('latitude')->whereNotNull('longitude')->get(['latitude','longitude','radius_m']) as $loc) {
                $fences[] = [
                    'lat' => (float) $loc->latitude,
                    'lng' => (float) $loc->longitude,
                    'radius_m' => $loc->radius_m ? (int)$loc->radius_m : self::DEFAULT_RADIUS_M,
                ];
            }
        }

        // 2) From business.extra_geofences JSON
        $extra = $business->extra_geofences;
        if (is_string($extra)) {
            $extra = json_decode($extra, true);
        }
        if (is_array($extra)) {
            foreach ($extra as $e) {
                if (isset($e['lat'], $e['lng'])) {
                    $fences[] = [
                        'lat' => (float) $e['lat'],
                        'lng' => (float) $e['lng'],
                        'radius_m' => isset($e['radius_m']) ? (int)$e['radius_m'] : self::DEFAULT_RADIUS_M,
                    ];
                }
            }
        }

        return $fences;
    }

    /** True if the given point is inside ANY allowed fence */
    private function isWithinAnyFence(float $lat, float $lng, array $fences): bool
    {
        foreach ($fences as $f) {
            $d = $this->distanceMeters($lat, $lng, $f['lat'], $f['lng']);
            if ($d <= max(1, (int)$f['radius_m'])) {
                return true;
            }
        }
        return false;
    
    }

    // Save business attendance settings (toggles + extra geofences)
    public function updateSettings(Request $request, $slug)
    {
        return $this->handleTransaction(function () use ($request, $slug) {
            $business = \App\Models\Business::findBySlug($slug);
            if (!$business) return RequestResponse::badRequest('Business not found.');

            $enforce_geofence = (int) $request->boolean('enforce_geofence');
            $enforce_mac      = (int) $request->boolean('enforce_mac');

            $extra = $request->input('extra_geofences');
            if ($extra && !is_array($extra)) {
                // Allow JSON string
                try {
                    $decoded = json_decode($extra, true, flags: JSON_THROW_ON_ERROR);
                    $extra = $decoded;
                } catch (\Throwable $e) {
                    return RequestResponse::badRequest('Invalid extra geofences JSON.');
                }
            }

            $business->enforce_geofence = $enforce_geofence;
            $business->enforce_mac      = $enforce_mac;
            $business->extra_geofences  = $extra ?: null;
            $business->save();

            // AttendanceController@updateSettings
                return response()->json([
                    'status'  => 'success',
                    'message' => 'Attendance settings saved.'
                ], 200);

        });
    }

    // Save a single location’s coordinates
    public function updateLocationCoords(Request $request, $slug, $locationId)
    {
        return $this->handleTransaction(function () use ($request, $slug, $locationId) {
            $business = \App\Models\Business::findBySlug($slug);
            if (!$business) return RequestResponse::badRequest('Business not found.');

            $validated = $request->validate([
                'latitude'  => 'nullable|numeric|between:-90,90',
                'longitude' => 'nullable|numeric|between:-180,180',
                'radius_m'  => 'nullable|integer|min:1|max:100000',
            ]);

            $location = $business->locations()->findOrFail($locationId);
            $location->latitude  = $validated['latitude']  ?? null;
            $location->longitude = $validated['longitude'] ?? null;
            $location->radius_m  = $validated['radius_m']  ?? null;
            $location->save();

            return RequestResponse::ok('Location coordinates saved.');
        });
    }

    // Save employee registered MAC
    public function updateEmployeeMac(Request $request, $employeeId)
    {
        return $this->handleTransaction(function () use ($request, $employeeId) {
            $employee = \App\Models\Employee::findOrFail($employeeId);
            $validated = $request->validate([
                'registered_device_mac' => 'nullable|string|max:64'
            ]);
            $employee->registered_device_mac = $validated['registered_device_mac'] ?: null;
            $employee->save();

            return RequestResponse::ok('Employee device saved.');
        });
    }


    // ADD THIS: Geocode proxy for search boxes (Nominatim/OSM)
    public function geocode(Request $request)
    {
        $q = trim((string)$request->get('q', ''));
        if ($q === '') return response()->json([]);

        $params = [
            'q' => $q,
            'format' => 'json',
            'addressdetails' => 1,
            'limit' => (int)$request->get('limit', 8),
            'countrycodes' => $request->get('countrycodes',''), // e.g. "ke"
        ];
        $url = 'https://nominatim.openstreetmap.org/search?' . http_build_query($params);
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERAGENT => 'YourApp/1.0 (admin@yourapp.example)',
            CURLOPT_TIMEOUT => 8,
        ]);
        $out = curl_exec($ch);
        if ($out === false) return response()->json([]);
        curl_close($ch);

        $data = json_decode($out, true) ?: [];
        $pruned = collect($data)->map(function($i){
            return [
                'display_name' => $i['display_name'] ?? '',
                'lat' => $i['lat'] ?? null,
                'lon' => $i['lon'] ?? null,
            ];
        })->all();

        return response()->json($pruned);
    }


}