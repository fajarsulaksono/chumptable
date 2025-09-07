<?php

declare(strict_types=1);

namespace Chumptable\Datatable\Columns;

/**
 * Class DateColumn
 *
 * Column type for displaying dates in different formats.
 */
class DateColumn extends BaseColumn
{
    /**
     * Constants for the time representation.
     */
    public const DATE = 0;
    public const TIME = 1;
    public const DATE_TIME = 2;
    public const CUSTOM = 4;
    public const FORMATTED_DATE = 5;
    public const DAY_DATE = 6;

    /**
     * The format to show.
     *
     * @var int
     */
    private $format;

    /**
     * Custom format string if chosen.
     *
     * @var string
     */
    private $custom;

    /**
     * DateColumn constructor.
     *
     * @param string $name
     * @param int    $format
     * @param string $custom
     */
    public function __construct(string $name, int $format = self::DATE_TIME, string $custom = '')
    {
        parent::__construct($name);
        $this->format = $format;
        $this->custom = $custom;
    }

    /**
     * Run the column transformation.
     *
     * @param mixed $model The data to pass to the column,
     *                     could be a model or an array.
     * @return mixed
     */
    public function run($model)
    {
        $value = is_array($model) ? $model[$this->name] ?? null : $model->{$this->name} ?? null;

        if ($value === null) {
            return null;
        }

        // Handle string date values
        if (is_string($value)) {
            if ($this->custom) {
                return strftime($this->custom, strtotime($value));
            }
            return $value;
        }

        // Handle Carbon or DateTime instances
        switch ($this->format) {
            case self::DATE:
                return $value->toDateString();
            case self::TIME:
                return $value->toTimeString();
            case self::DATE_TIME:
                return $value->toDateTimeString();
            case self::CUSTOM:
                return $value->format($this->custom);
            case self::FORMATTED_DATE:
                return $value->toFormattedDateString();
            case self::DAY_DATE:
                return $value->toDayDateTimeString();
            default:
                return (string) $value;
        }
    }
}
