<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Test;

class SebController extends Controller
{

    public function show(Test $test)
    {
        // URL por defecto del test
        $test_url = route('tests.show', $test);

        return view('seb.show', compact('test', 'test_url'));
    }


    public function download(Request $request, Test $test)
    {
        // Validación básica (aunque sea PoC, así evitamos errores)
        $data = $request->validate([
            'start_url'     => ['required', 'url'],
            'quit_password' => ['nullable', 'string'],
            'allow_reload'  => ['nullable', 'boolean'],
        ]);

        // Config SEB básica para pruebas
        $sebSettings = [
            'originatorVersion' => 'SEB_Laravel_Demo',
            'startURL'          => $data['start_url'],
            'allowQuit'         => true,
            'allowReload'       => (bool) ($data['allow_reload'] ?? false),
        ];

        if (!empty($data['quit_password'])) {
            // DEMO: texto plano. En producción deberías usar el campo hash de SEB.
            $sebSettings['quitPassword'] = $data['quit_password'];
        }

        // Convertir array → plist XML
        $xml = $this->arrayToPlist($sebSettings);

        $fileName = 'test-' . $test->id . '.seb';

        return response($xml)
            ->header('Content-Type', 'application/xml')
            ->header('Content-Disposition', 'attachment; filename="' . $fileName . '"');
    }

    protected function arrayToPlist(array $settings): string
    {
        $itemsXml = '';

        foreach ($settings as $key => $value) {
            $itemsXml .= "    <key>" . e($key) . "</key>\n";

            if (is_bool($value)) {
                $itemsXml .= '    ' . ($value ? '<true/>' : '<false/>') . "\n";
            } elseif (is_array($value)) {
                $itemsXml .= "    <array>\n";
                foreach ($value as $v) {
                    $itemsXml .= '        <string>' . e($v) . "</string>\n";
                }
                $itemsXml .= "    </array>\n";
            } else {
                $itemsXml .= '    <string>' . e((string) $value) . "</string>\n";
            }
        }

        $xml = <<<XML
        <?xml version="1.0" encoding="UTF-8"?>
        <!DOCTYPE plist PUBLIC "-//Apple//DTD PLIST 1.0//EN" "http://www.apple.com/DTDs/PropertyList-1.0.dtd">
        <plist version="1.0">
        <dict>
        $itemsXml</dict>
        </plist>

        XML;

        return $xml;
    }
}
