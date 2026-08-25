<?php

namespace App\Http\Controllers;

use App\Models\BiometricDevice;
use App\Models\BiometricDeviceEnrollment;
use App\Models\Business;
use App\Models\Employee;
use App\Http\RequestResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BiometricDeviceController extends Controller
{
    public function fetch(Business $business)
    {
        $devices = BiometricDevice::where('business_id', $business->id)
            ->with('location:id,name')
            ->withCount('enrollments')
            ->orderByDesc('id')
            ->get()
            ->map(fn (BiometricDevice $d) => [
                'id' => $d->id,
                'name' => $d->name,
                'vendor' => $d->vendor,
                'serial_number' => $d->serial_number,
                'location' => $d->location?->name,
                'is_active' => $d->is_active,
                'last_seen_at' => $d->last_seen_at?->diffForHumans(),
                'enrollments_count' => $d->enrollments_count,
                'webhook_url' => $d->webhookUrl(),
            ]);

        return RequestResponse::ok('Devices fetched successfully.', $devices);
    }

    public function store(Request $request, Business $business)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:150|unique:biometric_devices,name,NULL,id,business_id,' . $business->id,
            'vendor' => 'required|in:zkteco,hikvision,other',
            'location_id' => ['nullable', 'integer', Rule::exists('locations', 'id')->where('business_id', $business->id)],
            'serial_number' => 'nullable|string|max:100',
        ]);

        $device = BiometricDevice::create([
            'business_id' => $business->id,
            'location_id' => $validated['location_id'] ?? null,
            'name' => $validated['name'],
            'vendor' => $validated['vendor'],
            'serial_number' => $validated['serial_number'] ?? null,
            'webhook_token' => BiometricDevice::generateWebhookToken(),
            'is_active' => true,
        ]);

        return RequestResponse::created('Device registered successfully.', [
            'id' => $device->id,
            'webhook_url' => $device->webhookUrl(),
        ]);
    }

    public function update(Request $request, Business $business, BiometricDevice $device)
    {
        if ((int) $device->business_id !== (int) $business->id) {
            return RequestResponse::badRequest('Device not found for this business.', 404);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:150|unique:biometric_devices,name,' . $device->id . ',id,business_id,' . $business->id,
            'vendor' => 'required|in:zkteco,hikvision,other',
            'location_id' => ['nullable', 'integer', Rule::exists('locations', 'id')->where('business_id', $business->id)],
            'serial_number' => 'nullable|string|max:100',
        ]);

        $device->update($validated);

        return RequestResponse::ok('Device updated successfully.');
    }

    public function toggleActive(Business $business, BiometricDevice $device)
    {
        if ((int) $device->business_id !== (int) $business->id) {
            return RequestResponse::badRequest('Device not found for this business.', 404);
        }

        $device->update(['is_active' => !$device->is_active]);

        return RequestResponse::ok($device->is_active ? 'Device activated.' : 'Device deactivated.', [
            'is_active' => $device->is_active,
        ]);
    }

    public function regenerateToken(Business $business, BiometricDevice $device)
    {
        if ((int) $device->business_id !== (int) $business->id) {
            return RequestResponse::badRequest('Device not found for this business.', 404);
        }

        $device->update(['webhook_token' => BiometricDevice::generateWebhookToken()]);

        return RequestResponse::ok('Webhook URL rotated - update it on the device.', [
            'webhook_url' => $device->webhookUrl(),
        ]);
    }

    public function destroy(Business $business, BiometricDevice $device)
    {
        if ((int) $device->business_id !== (int) $business->id) {
            return RequestResponse::badRequest('Device not found for this business.', 404);
        }

        $device->delete();

        return RequestResponse::ok('Device removed successfully.');
    }

    public function enrollments(Business $business, BiometricDevice $device)
    {
        if ((int) $device->business_id !== (int) $business->id) {
            return RequestResponse::badRequest('Device not found for this business.', 404);
        }

        $enrollments = BiometricDeviceEnrollment::where('biometric_device_id', $device->id)
            ->with('employee.user:id,name')
            ->orderBy('device_pin')
            ->get()
            ->map(fn (BiometricDeviceEnrollment $e) => [
                'id' => $e->id,
                'device_pin' => $e->device_pin,
                'employee_id' => $e->employee_id,
                'employee_name' => $e->employee?->user?->name ?? $e->employee?->employee_code,
            ]);

        return RequestResponse::ok('Enrollments fetched successfully.', $enrollments);
    }

    public function storeEnrollment(Request $request, Business $business, BiometricDevice $device)
    {
        if ((int) $device->business_id !== (int) $business->id) {
            return RequestResponse::badRequest('Device not found for this business.', 404);
        }

        $validated = $request->validate([
            'device_pin' => 'required|string|max:191',
            'employee_id' => [
                'required',
                'integer',
                Rule::exists('employees', 'id')->where('business_id', $business->id),
            ],
        ]);

        $enrollment = BiometricDeviceEnrollment::updateOrCreate(
            ['biometric_device_id' => $device->id, 'device_pin' => $validated['device_pin']],
            ['employee_id' => $validated['employee_id']]
        );

        return RequestResponse::created('Employee mapped to device PIN.', $enrollment);
    }

    public function destroyEnrollment(Business $business, BiometricDevice $device, BiometricDeviceEnrollment $enrollment)
    {
        if ((int) $device->business_id !== (int) $business->id || (int) $enrollment->biometric_device_id !== (int) $device->id) {
            return RequestResponse::badRequest('Enrollment not found for this device.', 404);
        }

        $enrollment->delete();

        return RequestResponse::ok('Mapping removed successfully.');
    }
}
