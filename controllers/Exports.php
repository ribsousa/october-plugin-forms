<?php

    namespace Martin\Forms\Controllers;

    use BackendMenu, Response;
    use Backend\Classes\Controller;
    use League\Csv\Writer as CsvWriter;
    use SplTempFileObject;
    use Martin\Forms\Models\Record;

    class Exports extends Controller {

        public $requiredPermissions = ['martin.forms.access_exports'];

        public $implement = [
            'Backend.Behaviors.FormController',
        ];

        public $formConfig = 'config_form.yaml';

        public function __construct() {
            parent::__construct();
            BackendMenu::setContext('Martin.Forms', 'forms', 'exports');
        }

        public function index() {
            $this->pageTitle = e(trans('martin.forms::lang.controllers.exports.title'));
            $this->create('frontend');
        }

        public function csv() {

            $records = Record::orderBy('created_at');

            # FILTER GROUPS
            if(!empty($groups = post('Record.filter_groups'))) {
                $records->whereIn('group', $groups);
            }

            # FILTER DATE
            if(!empty($date_after = post('Record.filter_date_after'))) {
                $records->whereDate('created_at', '>=', $date_after);
            }

            # FILTER DATE
            if(!empty($date_before = post('Record.filter_date_before'))) {
                $records->whereDate('created_at', '<=', $date_before);
            }

            # FILTER DELETED
            if($deleted = post('Record.options_deleted')) {
                $records->withTrashed();
            }

            # CREATE CSV
            $csv = CsvWriter::createFromFileObject(new SplTempFileObject());

            # CSV HEADERS
            $headers = [e(trans('martin.forms::lang.controllers.records.columns.form_data')) . " >>>"];

            # METADATA HEADERS
            if($metadata = post('Record.options_metadata')) {
                $meta_headers = [
                    e(trans('martin.forms::lang.controllers.records.columns.id')),
                    e(trans('martin.forms::lang.controllers.records.columns.group')),
                    e(trans('martin.forms::lang.controllers.records.columns.ip')),
                    e(trans('martin.forms::lang.controllers.records.columns.created_at')),
                ];
                $headers = array_merge($meta_headers, $headers);
            }

            # ADD HEADERS
            $csv->insertOne($headers);

            # WRITE CSV LINES
            foreach($records->get()->toArray() as $row) {
                $data = (array) json_decode($row['form_data']);
                if($metadata = post('Record.options_metadata')) { array_unshift($data, $row['id'], $row['group'], $row['ip'], $row['created_at']); }
                $csv->insertOne($data);
            }

            # RETURN CSV
            $csv->output('records.csv');
            exit();

        }

    }

?>