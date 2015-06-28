<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Moodle - Filter for scanning document and producing a graph from a table.
 *
 * @subpackage graphtable
 * @copyright  2014 Daniel Thies
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

/**
 * Class to graph tables as bar graphs
 */
class filter_graphtable extends moodle_text_filter {

    public function filter($text, array $options = array()) {

        global $CFG, $DB;

        // Do a quick check for presence of tables.

        // Create a new dom object.
        $dom = new domDocument;
        $this->dom = $dom;
        $dom->formatOutput = true;

        // Load the html into the objecti.
        libxml_use_internal_errors(true);
        $dom->loadHTML(
            '<!DOCTYPE html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"/></head><body>' .
            $text);
        libxml_use_internal_errors(false);

        $dom->preserveWhiteSpace = false;
        $dom->strictErrorChecking = false;
        $dom->recover = true;

        $body = $dom->getElementsByTagName('body')->item(0);

        $tables = $dom->getElementsByTagName('table');
        foreach ($tables as $table) {
            $graph = $this->get_graph($table);
            $graph = $dom->importNode($graph, true);
            $table->parentNode->insertBefore($graph, $table);

        }

        $str = $dom->saveHTML($body);
        $str = str_replace("<body>", "", $str);
        $str = str_replace("</body>", "", $str);

        return $str;

    }

    function get_svg_graph($table) {

        $svg = '<svg width="200" height="200" style="float: left">';
        $svg .= '<line x1="35" y1="10" x2="35" y2="160" style="stroke:blue;stroke-width:2" />';
        $svg .= '<line x1="35" y1="160" x2="180" y2="160" style="stroke:blue;stroke-width:2" />';
        $svg .= '<line x1="30" y1="10" x2="35" y2="10" style="stroke:blue;stroke-width:2" />';
        $svg .= '<line x1="30" y1="60" x2="35" y2="60" style="stroke:blue;stroke-width:2" />';
        $svg .= '<line x1="30" y1="110" x2="35" y2="110" style="stroke:blue;stroke-width:2" />';

        $rows = $table->getElementsByTagName('tr');
        $x = 40;
        foreach ($rows as $row) {
            if ($row->getElementsByTagName('td') &&
                    $row->getElementsByTagName('td')->item(1)) {
                $height = 10*floor($row->getElementsByTagName('td')->item(1)->nodeValue);
                $y = 160 - $height;
                $svg .= "<rect x=\"$x\" y=\"$y\" width=\"10\" height=\"$height\" style=\"fill:blue\" />";
                $label = $row->getElementsByTagName('td')->item(0)->nodeValue;
                $svg .= "<text x=\"$x\" y=\"180\" style=\"fill:blue\">$label</text>";
                $x += 10;
                $x += 10;
            }
        }
        if ($table->getElementsByTagName('th') &&
                    $table->getElementsByTagName('th')->item(1)) {
             $label = $table->getElementsByTagName('th')->item(0)->nodeValue;
             $svg .= "<text x=\"50\" y=\"199\" style=\"fill:blue\">$label</text>";
             $label = $table->getElementsByTagName('th')->item(1)->nodeValue;
             $svg .= "<text x=\"80\" y=\"199\" style=\"fill:blue\" transform=\"rotate(270 20 200)\">$label</text>";
        }
        $svg .= '</svg>';

        // Create a new dom object.
        $dom = new domDocument;
        // Load the html into the objecti.
        libxml_use_internal_errors(true);
        $dom->loadHTML(
            '<!DOCTYPE html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"/></head><body>' .
            $svg, true);
        $svgnode = $dom->getElementsByTagName('svg')->item(0);
        return $svgnode;
    }

    function get_graph($table) {
        $dom = $table->ownerDocument;

        $columns = $table->getElementsByTagName('th')->length + 1;

        $script = 'reset; unset key; set xtics rotate out;';
        $script .= 'set style data histograms; set style histogram rowstacked;';
        $script .= 'set style fill solid 1.0 border lt -1; plot for [COL=3:' . $columns . ':1] "-" using COL:xticlabels(2)';
        $rows = $table->getElementsByTagName('tr');
        $rownumber = 0;

        foreach ($rows as $row) {
            $data = $row->getElementsByTagName('td');
            if (!empty($data->item(0))) {
                $script .= "\n" . $rownumber++ . " ";
            }
            foreach ($data as $datum) {
                $script .= $datum->textContent . " ";
            }
        }
        $script .= "\ne\n";

        $plugin = local_math_plugin::get('gnuplot');
        $span = $dom->createElement('span');
        $span->nodeValue = $script;

        $plugin->process($span);
        
        return $span;
    }

}
