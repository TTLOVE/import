<?php

namespace Import;

use Dictionary\Dictionary;
use QT\Import\Foundation\Import;

class ImportTask extends Import
{
    protected $fields = [
        'id_number'  => '身份证号',
        'name'       => '学生姓名',
        'class_name' => '报到班级',
    ];

    protected $rules = [
        // 'id_number'  => 'required',
        'id_number'  => 'required|min:100',
        'name'       => 'required',
        'class_name' => 'required',
    ];

    protected $fieldRemarks = [
        'class_name' => '举例：2020年初一(1)班请填写"初中2020级01班"',
    ];

    public function __construct()
    {
        parent::__construct();

        $this->dictionary = new Dictionary(extraDictFields:['aa'], model:'modelName');
    }

    protected function insertDB()
    {
        echo "\n\n";
        var_export($this->rows);
        echo "\n\n";
    }
}
