<?php
/**
 * Table Component
 * Usage: Table::create(['columns' => [...], 'data' => [...]])
 */

require_once __DIR__ . '/Component.php';
require_once __DIR__ . '/Badge.php';

class Table extends Component {
    
    public function render() {
        $columns = $this->prop('columns', []); // [{key, label, render}]
        $data = $this->prop('data', []);
        $striped = $this->prop('striped', true);
        $hover = $this->prop('hover', true);
        $responsive = $this->prop('responsive', true);
        $bordered = $this->prop('bordered', false);
        $actions = $this->prop('actions'); // Callback function for action column
        
        // Build table classes
        $classes = ['table'];
        
        if ($striped) $classes[] = 'table-striped';
        if ($hover) $classes[] = 'table-hover';
        if ($bordered) $classes[] = 'table-bordered';
        
        $html = '';
        
        // Wrapper for responsive
        if ($responsive) {
            $html .= '<div class="table-responsive">';
        }
        
        $html .= '<table class="' . implode(' ', $classes) . '">';
        
        // Table header
        $html .= '<thead>';
        $html .= '<tr>';
        
        foreach ($columns as $column) {
            $label = $column['label'] ?? ucfirst($column['key']);
            $width = isset($column['width']) ? ' style="width: ' . $column['width'] . '"' : '';
            $html .= '<th' . $width . '>' . $this->escape($label) . '</th>';
        }
        
        if ($actions) {
            $html .= '<th style="width: 150px">Actions</th>';
        }
        
        $html .= '</tr>';
        $html .= '</thead>';
        
        // Table body
        $html .= '<tbody>';
        
        if (empty($data)) {
            $colSpan = count($columns) + ($actions ? 1 : 0);
            $html .= '<tr>';
            $html .= '<td colspan="' . $colSpan . '" class="text-center text-muted py-4">No data available</td>';
            $html .= '</tr>';
        } else {
            foreach ($data as $row) {
                $html .= '<tr>';
                
                foreach ($columns as $column) {
                    $key = $column['key'];
                    $value = $row[$key] ?? '';
                    
                    // Use custom render function if provided
                    if (isset($column['render']) && is_callable($column['render'])) {
                        $cellContent = $column['render']($value, $row);
                    } else {
                        $cellContent = $this->escape($value);
                    }
                    
                    $html .= '<td>' . $cellContent . '</td>';
                }
                
                // Actions column
                if ($actions && is_callable($actions)) {
                    $html .= '<td>' . $actions($row) . '</td>';
                }
                
                $html .= '</tr>';
            }
        }
        
        $html .= '</tbody>';
        $html .= '</table>';
        
        if ($responsive) {
            $html .= '</div>';
        }
        
        return $html;
    }
}
