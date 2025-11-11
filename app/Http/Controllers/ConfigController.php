<?php

namespace App\Http\Controllers;

use App\Models\DeviceType;
use App\Models\SystemSetting;
use Illuminate\Http\Request;

class ConfigController extends Controller
{
    public function index()
    {
        $settings = [
            'app_name' => SystemSetting::get('app_name', config('app.name')),
            'app_url' => SystemSetting::get('app_url', config('app.url')),
            'mail_from' => SystemSetting::get('mail_from_address', config('mail.from.address')),
            'mail_to' => SystemSetting::get('mail_to', config('mail.recipient_email') ?? env('MAIL_TO_ALERT')),
            'alert_threshold' => SystemSetting::get('alert_threshold', 5),
            'sensor_update_interval' => SystemSetting::get('sensor_update_interval', 2000),
        ];

        $deviceTypes = DeviceType::withCount('devices')
            ->orderBy('name')
            ->get();

        return view('config.index_config', [
            'settings' => $settings,
            'deviceTypes' => $deviceTypes,
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'app_name' => 'required|string|max:255',
            'app_url' => 'required|url',
            'mail_from' => 'required|email',
            'mail_to' => 'required|email',
            'alert_threshold' => 'required|numeric|min:0',
            'sensor_update_interval' => 'required|numeric|min:1000',
        ]);

        $definitions = [
            'app_name' => ['value' => $validated['app_name'], 'type' => 'string', 'group' => 'general'],
            'app_url' => ['value' => $validated['app_url'], 'type' => 'string', 'group' => 'general'],
            'mail_from_address' => ['value' => $validated['mail_from'], 'type' => 'string', 'group' => 'mail'],
            'mail_to' => ['value' => $validated['mail_to'], 'type' => 'string', 'group' => 'mail'],
            'alert_threshold' => ['value' => $validated['alert_threshold'], 'type' => 'integer', 'group' => 'alerts'],
            'sensor_update_interval' => ['value' => $validated['sensor_update_interval'], 'type' => 'integer', 'group' => 'alerts'],
        ];

        foreach ($definitions as $key => $definition) {
            SystemSetting::set(
                $key,
                $definition['value'],
                $definition['type'],
                $definition['group']
            );
        }

        SystemSetting::clearCache();

        config([
            'app.name' => $validated['app_name'],
            'app.url' => $validated['app_url'],
            'mail.from.address' => $validated['mail_from'],
            'mail.recipient_email' => $validated['mail_to'],
        ]);

        return back()->with('success', 'Configuración actualizada correctamente');
    }
}
