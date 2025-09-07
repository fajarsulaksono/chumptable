<?php

declare(strict_types=1);

namespace Chumptable\Datatable\Columns;

/**
 * Class TextColumn
 *
 * Column type that always renders a static text value.
 */
class TextColumn extends BaseColumn
{
    /**
     * The static text value for this column.
     *
     * @var string
     */
    private $text;

    /**
     * TextColumn constructor.
     *
     * @param string $name
     * @param string $text
     */
    public function __construct(string $name, string $text)
    {
        parent::__construct($name);
        $this->text = $text;
    }

    /**
     * Run the column transformation.
     *
     * @param mixed $model
     * @return string
     */
    public function run($model): string
    {
        return $this->text;
    }
}
