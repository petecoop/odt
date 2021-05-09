<?php

namespace Petecoop\ODT\Compilers;

use DOMDocument;
use Symfony\Component\DomCrawler\Crawler;

class TableCompiler implements Compiler
{
    private string $name;
    private string $key;
    private array $values;

    private Crawler $crawler;
    private Crawler $table;
    private Crawler $header;
    private Crawler $column;
    private Crawler $row;

    private string $columnTemplate;
    private string $headerTemplate;
    private string $cellTemplate;
    private string $cellContentTemplate;

    private array $columnStyles = [];

    public function __construct(string $name, string $key, array $values = [])
    {
        $this->name = $name;
        $this->key = $key;
        $this->values = $values;
    }

    public function compile(string $value): string
    {
        $this->setup($value);
        $this->addColumn('name', 'Name');

        return $this->getOutput();
    }

    private function setup(string $value)
    {
        $this->dom = new DOMDocument();
        $this->dom->loadXML($value);
        $this->crawler = new Crawler($this->dom);

        $this->table = $this->crawler->filter('table|table[table|name="'.$this->name.'"]');
        $this->header = $this->table->filter('table|table-row')->first();
        $this->column = $this->table->children('table|table-column')->first();
        $this->row = $this->table->children('table|table-row')->last();

        $this->columnTemplate = $this->column->outerHtml();
        $this->columnStyleTemplate = $this->column->attr('table:style-name');
        $this->columnTarget = $this->column->siblings()->first();
        $this->headerTemplate = $this->header->children('table|table-cell')->first()->outerHtml();
        $cell = $this->row->children('table|table-cell')->first();
        $this->cellTemplate = $cell->outerHtml();
        $this->cellContentTemplate = $cell->html();
    }

    private function getOutput()
    {
        return $this->crawler->outerHtml();
    }

    private function addColumn(string $key, $options)
    {
        $count = $this->row->children('table|table-cell')->count();

        $headerNode = $this->header->getNode(0);
        $rowNode = $this->row->getNode(0);

        if (!is_array($options)) {
            $options = [
                'title' => $options,
                'flex' => 1,
            ];
        }

        $style = $this->createColumnStyle($options['flex']);

        if ($count == 1) {
            // if it's the first row then replace
            $this->table->getNode(0)->replaceChild($this->createColumn($style), $this->column->getNode(0));
            $headerCell = $this->header->children('table|table-cell')->first();
            $headerNode->replaceChild($this->createHeader($options['title']), $headerCell->getNode(0));
            $rowCell = $this->row->children('table|table-cell')->first();
            $rowNode->replaceChild($this->createCell($key, true), $rowCell->getNode(0));
        } else {
            $this->table->getNode(0)->insertBefore(
                $this->createColumn($style),
                $this->columnTarget->getNode(0)
            );
            $headerNode->appendChild($this->createHeader($options['title']));
            $rowNode->appendChild($this->createCell($key));
        }
    }

    private function createColumn(?string $style = null)
    {
        $column = $this->columnTemplate;

        if ($style) {
            $column = str_replace($this->columnStyleTemplate, $style, $column);
        }

        return $this->createFragment($column);
    }

    private function createColumnStyle(int $flex = 1): string
    {
        if (!empty($this->columnStyles[$flex])) {
            return $this->columnStyles[$flex];
        }

        $name = 'TableFlex' . $flex;

        $styleParent = $this->crawler->filter('office|automatic-styles')->first();
        $count = $styleParent->filter('style|style[style|name="'.$name.'"]')->count();
        if ($count) {
            $this->columnStyles[$flex] = $name;
            return $name;
        }

        $styleParent->getNode(0)->appendChild($this->createFragment(
            '<style:style style:name="'.$name.'" style:family="table-column">' .
                '<style:table-column-properties style:rel-column-width="'.$flex.'000*" />' .
            '</style:style>'
        ));

        $this->columnStyles[$flex] = $name;

        return $name;
    }

    private function createHeader(string $title)
    {
        $header = str_replace('header', $title, $this->headerTemplate);

        return $this->createFragment($header);
    }

    private function createCell(string $key, bool $firstCell = false)
    {
        $itemKey = '$' . $this->key . '_item';
        $value = "{{ {$itemKey}['{$key}'] ?? '' }}";

        if ($firstCell) {
            $value = '@beforerow@foreach($'.$this->key.' as '.$itemKey.')@endbeforerow'
                . $value
                . '@afterrow@endforeach@endafterrow';
        }

        $content = str_replace('values', $value, $this->cellContentTemplate);

        $cell = str_replace($this->cellContentTemplate, $content, $this->cellTemplate);
        return $this->createFragment($cell);
    }

    private function createFragment($xml)
    {
        libxml_use_internal_errors(true);
        $fragment = $this->dom->createDocumentFragment();
        $fragment->appendXML($xml);
        libxml_use_internal_errors(false);

        return $fragment;
    }
}
