<?php

namespace Dictionary;

use QT\Import\Contracts\Dictionary as DictionaryContract;

class Dictionary implements DictionaryContract
{
    /**
     * 表名，从model获取不需要配置
     *
     * @var string
     */
    protected $table = '';

    /**
     * 数据源 (需要配置)
     *
     * @var string
     */
    protected $model = '';

    /**
     * 字典
     *
     * @var array
     */
    protected $dict = [];

    /**
     * 字典是否加载
     *
     * @var boolean
     */
    protected $isDictLoad = false;

    /**
     * 额外字典,非主表内的字典字段
     *
     * @var array
     */
    protected $extraDictFields = [
        // table => [
        //    column => display_name
        // ]
    ];

    /**
     * 导入匹配字典字段信息，如果字段名不为字典对应字段，要自行设置
     * eq: 实际字段名[family1_relationship] -> 字典[relationship]
     *
     * @var array
     */
    protected $extraDictFieldMaps = [
        // column => dict_field
    ];

    /**
     * excel 下拉字典信息，需手动配置
     * eq:  字典对应字段
     *
     * @var array
     */
    protected $optionalDict = [
        // dict_field
    ];

    public function __construct(
        $model = '',
        $extraDictFields = [],
    ) {
        $this->model           = $model;
        $this->extraDictFields = $extraDictFields;
    }

    /**
     * 获取任务关联的字典信息
     *
     * @return Collection
     */
    public function getDictionaries(): array
    {
        return [];
        // todo 
        if ($this->isDictLoad) {
            return $this->dict;
        }

        // 默认加载当前导入表的全部字典
        $tables = [$this->getTable() => ['*']];
        // 加载额外字典
        foreach ($this->extraDictFields as $extraField => $value) {
            if (!is_array($value)) {
                $tables[$this->getTable()][] = $extraField;
            } else {
                $tables[$extraField] = array_keys($value);
            }
        }

        $this->isDictLoad = true;

        return $this->dict = array_merge(
            $this->dict,
            $this->loadDictionaries($tables)->toArray()
        );
    }

    /**
     * 获取指定table的字典
     *
     * @param string $table
     * @param array $fields
     * @return \Illuminate\Support\Collection
     */
    public function loadDictionaries($tables)
    {
        $query = TableField::with('dictionaries');
        foreach ($tables as $table => $columns) {
            if (empty($columns)) {
                continue;
            }

            $query->orWhere(function ($subQuery) use ($table, $columns) {
                $subQuery->where('table', $table);

                if (!in_array('*', $columns)) {
                    $subQuery->whereIn('field', $columns);
                }
            });
        }

        $globalDict = Dict::getGlobalDict();
        // 使用 \Illuminate\Support\Collection 替换 \Illuminate\Database\Eloquent\Collection
        // 不然后续继承 loadDictionaries 调用 merge 方法时会被视为model合并
        return collect($query->get())->mapWithKeys(function ($model) use ($globalDict) {
            // 检查字段是否使用了全局字典
            if (
                $model->uses_global === TableField::YES &&
                isset($globalDict[$model->field])
            ) {
                $model = $globalDict[$model->field];
            }

            return [$model->field => $model->mapWithCode()];
        });
    }

    /**
     * 获取任务关联的字典信息
     */
    public function getOptionalDictionaries(): array
    {
        return [];
        // todo 
        $dictionaries = $this->getDictionaries();
        if (empty($dictionaries) || empty($this->optionalDict)) {
            return [];
        }

        $optionalDict = [];
        foreach ($this->optionalDict as $key => $dict) {
            if (!isset($dictionaries[$dict])) {
                continue;
            }

            if (is_string($key)) {
                $optionalDict[$key] = $dictionaries[$dict];
            } else {
                $optionalDict[$dict] = $dictionaries[$dict];
            }
        }

        return $optionalDict;
    }

    /**
     * @param string $model
     * @param bool   $fresh
     * @return Builder
     * @throws Error
     */
    public function getModelQuery($model = '', $fresh = false)
    {
        $model = $model ?: $this->model;

        if (empty($model) || !class_exists($model)) {
            throw new Error('SYSTEM_FAILED', '无效的model');
        }

        if ($fresh) {
            return app($model)->query();
        }

        if (empty($this->query)) {
            $this->query = app($model)->query();
        }

        return $this->query;
    }

    /**
     * 获取表名
     *
     * @return string
     */
    public function getTable()
    {
        if (!empty($this->table)) {
            return $this->table;
        }

        return $this->table = $this->getModelQuery()->getModel()->getTable();
    }
}
