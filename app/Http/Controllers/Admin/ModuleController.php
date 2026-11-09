<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\Modules\ModuleRegistry;
use App\Support\Modules\ModuleSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ModuleController extends Controller
{
    public function __construct(protected ModuleSettings $modules) {}

    /** Overview: every module with its on/off switch and an Edit button. */
    public function index(): View
    {
        $definitions = collect(ModuleRegistry::definitions())->map(function (array $module, string $key) {
            $module['key'] = $key;
            $module['enabled'] = $this->modules->enabled($key);

            return $module;
        });

        return view('admin.modules.index', [
            'groups' => $definitions->groupBy('group'),
            'enabledCount' => $definitions->where('enabled', true)->count(),
            'total' => $definitions->count(),
        ]);
    }

    /** Full-page editor for one module's settings. */
    public function edit(string $module): View
    {
        abort_unless(ModuleRegistry::exists($module), 404);

        $definition = ModuleRegistry::definitions()[$module];
        $definition['key'] = $module;
        $definition['enabled'] = $this->modules->enabled($module);
        $definition['values'] = collect($definition['fields'])
            ->mapWithKeys(fn ($f) => [$f['key'] => $this->modules->value($module, $f['key'])])
            ->all();

        return view('admin.modules.edit', ['module' => $definition]);
    }

    /** Overview form: saves the on/off switches. Settings are only touched when a module's fields are submitted. */
    public function update(Request $request): RedirectResponse
    {
        $rules = [];

        foreach (ModuleRegistry::definitions() as $key => $module) {
            $rules = array_merge($rules, $this->rulesFor($key, $module));
        }

        $submitted = $request->validate($rules)['modules'] ?? [];

        if ($error = $this->datesError($submitted)) {
            return back()->withInput()->with('error', $error);
        }

        $on = fn (string $key) => filter_var($submitted[$key]['enabled'] ?? false, FILTER_VALIDATE_BOOLEAN);

        // A shop that accepts orders needs at least one way to pay.
        if ($on('accepting_orders') && ! $on('payment_cod') && ! $on('payment_stripe')) {
            return back()->withInput()->with('error', 'Keep at least one payment method switched on (Cash on delivery or Card), or pause orders instead.');
        }

        $values = [];

        foreach (ModuleRegistry::definitions() as $key => $module) {
            $values["module.{$key}.enabled"] = $on($key);

            if (isset($submitted[$key]['fields']) || isset($submitted[$key]['upload']) || isset($submitted[$key]['clear'])) {
                $values += $this->fieldValues($request, $key, $module, $submitted[$key]);
            }
        }

        $this->modules->save($values);

        return redirect()->route('admin.modules.index')->with('success', 'Modules updated — changes are live on the shop now.');
    }

    /** Saves one module (its switch and all its settings) from its own edit screen. */
    public function updateModule(Request $request, string $module): RedirectResponse
    {
        abort_unless(ModuleRegistry::exists($module), 404);

        $definition = ModuleRegistry::definitions()[$module];
        $submitted = $request->validate($this->rulesFor($module, $definition))['modules'] ?? [];

        if ($error = $this->datesError($submitted)) {
            return back()->withInput()->with('error', $error);
        }

        $enabled = filter_var($submitted[$module]['enabled'] ?? false, FILTER_VALIDATE_BOOLEAN);

        // Don't let the last payment method be switched off while the shop is taking orders.
        if (! $enabled && in_array($module, ['payment_cod', 'payment_stripe'], true)) {
            $other = $module === 'payment_cod' ? 'payment_stripe' : 'payment_cod';

            if ($this->modules->enabled('accepting_orders') && ! $this->modules->enabled($other)) {
                return back()->withInput()->with('error', 'Keep at least one payment method switched on (Cash on delivery or Card), or pause orders instead.');
            }
        }

        $values = ["module.{$module}.enabled" => $enabled] + $this->fieldValues($request, $module, $definition, $submitted[$module] ?? []);

        $this->modules->save($values);

        return redirect()->route('admin.modules.edit', $module)->with('success', $definition['title'].' saved — it is live on the shop now.');
    }

    /** @return array<string, array<int, mixed>> */
    protected function rulesFor(string $key, array $module): array
    {
        $rules = ["modules.{$key}.enabled" => ['nullable', 'boolean']];

        foreach ($module['fields'] as $field) {
            if ($field['kind'] === 'image') {
                $rules["modules.{$key}.upload.{$field['key']}"] = ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'];
                $rules["modules.{$key}.clear.{$field['key']}"] = ['nullable', 'boolean'];

                continue;
            }

            $rules["modules.{$key}.fields.{$field['key']}"] = match ($field['kind']) {
                'toggle' => ['nullable', 'boolean'],
                'number' => ['nullable', 'numeric', 'min:'.($field['min'] ?? 0), 'max:'.($field['max'] ?? 1000000)],
                'textarea' => ['nullable', 'string', 'max:'.($field['max'] ?? 1000)],
                'select' => ['nullable', Rule::in(array_keys($field['options'] ?? []))],
                'date' => ['nullable', 'date_format:Y-m-d'],
                default => ['nullable', 'string', 'max:'.($field['max'] ?? 255)],
            };
        }

        return $rules;
    }

    protected function datesError(array $submitted): ?string
    {
        $start = $submitted['promo_popup']['fields']['start_date'] ?? null;
        $end = $submitted['promo_popup']['fields']['end_date'] ?? null;

        return $start && $end && $end < $start
            ? 'Promo pop-up: the end date must be on or after the start date.'
            : null;
    }

    /**
     * Flat "module.key.field" => value map for one module's submitted settings.
     * Image fields keep their current picture unless a new file is uploaded or "remove" is ticked.
     *
     * @param  array<string, mixed>  $submittedModule
     * @return array<string, mixed>
     */
    protected function fieldValues(Request $request, string $key, array $module, array $submittedModule): array
    {
        $values = [];

        foreach ($module['fields'] as $field) {
            $name = "module.{$key}.{$field['key']}";

            if ($field['kind'] === 'image') {
                $current = (string) $this->modules->value($key, $field['key'], '');
                $upload = $request->file("modules.{$key}.upload.{$field['key']}");
                $clear = filter_var($submittedModule['clear'][$field['key']] ?? false, FILTER_VALIDATE_BOOLEAN);

                if ($upload) {
                    $values[$name] = $upload->store('modules', 'public');
                } elseif ($clear) {
                    $values[$name] = '';
                } else {
                    continue; // keep the picture that is already there
                }

                if (str_starts_with($current, 'modules/')) {
                    Storage::disk('public')->delete($current);
                }

                continue;
            }

            $raw = $submittedModule['fields'][$field['key']] ?? null;
            $values[$name] = match ($field['kind']) {
                'toggle' => filter_var($raw, FILTER_VALIDATE_BOOLEAN),
                default => strip_tags((string) $raw),
            };
        }

        return $values;
    }
}
