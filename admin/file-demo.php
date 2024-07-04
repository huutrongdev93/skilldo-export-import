<?php
class ImportFileDemo {

    static function page() {}

    static function file(): void {

        if(Admin::is()) {

            $segment = Url::segment();

            $request = request();

            $page = $request->input('page');

            if(!empty($segment[2]) && $segment[2] == 'plugins' && $page == 'import-file-demo') {

                $file = $request->input('file-download');

                if (!empty($file)) {
                    $file = trim(Str::clear($file));

                    $filePath = Path::plugin(EXIM_NAME) . '/assets/excel/' . $file . '.xlsx';
                    if (file_exists($filePath)) {
                        response()->download($filePath, $file . '.xlsx');
                        die;
                    }

                    response()->setStatusCode(404)->send();
                }
            }
        }
    }
}

AdminMenu::add('import-file-demo', 'import-file-demo', 'import-file-demo', [
    'callback' => 'ImportFileDemo::page', 'hidden' => true
]);

add_action('template_redirect', 'ImportFileDemo::file');