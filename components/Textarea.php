<?php
/**
 * Textarea Component
 * Usage: Textarea::create(['name' => 'description', 'label' => 'Description', 'rows' => 5])
 */

require_once __DIR__ . '/Component.php';

class Textarea extends Component {
    
    public function render() {
        $name = $this->prop('name', '');
        $label = $this->prop('label');
        $placeholder = $this->prop('placeholder', '');
        $value = $this->prop('value', '');
        $rows = $this->prop('rows', 3);
        $required = $this->prop('required', false);
        $disabled = $this->prop('disabled', false);
        $readonly = $this->prop('readonly', false);
        $error = $this->prop('error');
        $help = $this->prop('help');
        $id = $this->prop('id', 'textarea-' . $name);
        
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
        
        // Build textarea classes
        $textareaClasses = ['form-control'];
        if ($error) {
            $textareaClasses[] = 'is-invalid';
        }
        
        // Build textarea attributes
        $attrs = 'class="' . implode(' ', $textareaClasses) . '" ';
        $attrs .= 'id="' . $this->escape($id) . '" ';
        $attrs .= 'name="' . $this->escape($name) . '" ';
        $attrs .= 'rows="' . $rows . '" ';
        
        if ($placeholder) {
            $attrs .= 'placeholder="' . $this->escape($placeholder) . '" ';
        }
        
        if ($required) {
            $attrs .= 'required ';
        }
        
        if ($disabled) {
            $attrs .= 'disabled ';
        }
        
        if ($readonly) {
            $attrs .= 'readonly ';
        }
        
        $html .= '<textarea ' . $attrs . '>' . $this->escape($value) . '</textarea>';
        
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
