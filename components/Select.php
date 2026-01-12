<?php
/**
 * Select Component
 * Usage: Select::create(['name' => 'status', 'label' => 'Status', 'options' => [...]])
 */

require_once __DIR__ . '/Component.php';

class Select extends Component {
    
    public function render() {
        $name = $this->prop('name', '');
        $label = $this->prop('label');
        $options = $this->prop('options', []); // [{value, label}] or ['key' => 'value']
        $value = $this->prop('value', '');
        $required = $this->prop('required', false);
        $disabled = $this->prop('disabled', false);
        $error = $this->prop('error');
        $help = $this->prop('help');
        $placeholder = $this->prop('placeholder', '-- Select --');
        $id = $this->prop('id', 'select-' . $name);
        
        $html = '<div class="mb-3">';
        
        // Label
        if ($label) {
            $html .= '<label for="' . $this->escape($id) . '" class="form-label">';
            $html .= $this->escape($label);
            if ($required) {
                $html .= ' <span class="text-danger">*</span>';
            }
            $html .= '</label>';
        }
        
        // Build select classes
        $selectClasses = ['form-select'];
        if ($error) {
            $selectClasses[] = 'is-invalid';
        }
        
        // Build select attributes
        $attrs = 'class="' . implode(' ', $selectClasses) . '" ';
        $attrs .= 'id="' . $this->escape($id) . '" ';
        $attrs .= 'name="' . $this->escape($name) . '" ';
        
        if ($required) {
            $attrs .= 'required ';
        }
        
        if ($disabled) {
            $attrs .= 'disabled ';
        }
        
        $html .= '<select ' . $attrs . '>';
        
        // Placeholder option
        if ($placeholder) {
            $html .= '<option value="">' . $this->escape($placeholder) . '</option>';
        }
        
        // Options
        foreach ($options as $key => $option) {
            if (is_array($option)) {
                // Option is ['value' => ..., 'label' => ...]
                $optValue = $option['value'] ?? $key;
                $optLabel = $option['label'] ?? $option['value'];
            } else {
                // Option is simple key => value
                $optValue = $key;
                $optLabel = $option;
            }
            
            $selected = ($value == $optValue) ? ' selected' : '';
            $html .= '<option value="' . $this->escape($optValue) . '"' . $selected . '>';
            $html .= $this->escape($optLabel);
            $html .= '</option>';
        }
        
        $html .= '</select>';
        
        // Error message
        if ($error) {
            $html .= '<div class="invalid-feedback">' . $this->escape($error) . '</div>';
        }
        
        // Help text
        if ($help) {
            $html .= '<div class="form-text">' . $this->escape($help) . '</div>';
        }
        
        $html .= '</div>';
        
        return $html;
    }
}
