<?php

class pdf_to_html_group_to_paragraphs
{
    public static function process(int $page): void
    {
        var_dump(digi_pdf_to_html::$arrayPages[$page]);
        die();
    }
}