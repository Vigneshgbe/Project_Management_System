<?php
/**
 * Input Component
 * Usage: Input::create(['name' => 'email', 'type' => 'email', 'label' => 'Email Address'])
 */

require_once __DIR__ . '/Component.php';

class Input extends Component {
    
    public function render() {
        $type = $this->prop('type', 'text');
        $name = $this->prop('name', '');
        $label = $this->prop('label');
        $placeholder = $this->prop('placeholder', '');
        $value = $this->prop('value', '');
        $required = $this->prop('required', false);
        $disabled = $this->prop('disabled', false);
        $readonly = $this->prop('readonly', false);
        $error = $this->prop('error');
        $help = $this->prop('help');
        $icon = $this->prop('icon');
        $id = $this->prop('id', 'input-' . $name);
        
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
        
        // Input group wrapper if icon present
        if ($icon) {
            $html .= '<div class="input-group">';
            $html .= '<span class="input-group-text"><i class="' . $this->escape($icon) . '"></i></span>';
        }
        
        // Build input classes
        $inputClasses = ['form-control'];
        if ($error) {
            $inputClasses[] = 'is-invalid';
        }
        
        // Build input attributes
        $attrs = 'type="' . $this->escape($type) . '" ';
        $attrs .= 'class="' . implode(' ', $inputClasses) . '" ';
        $attrs .= 'id="' . $this->escape($id) . '" ';
        $attrs .= 'name="' . $this->escape($name) . '" ';
        
        if ($placeholder) {
            $attrs .= 'placeholder="' . $this->escape($placeholder) . '" ';
        }
        
        if ($value !== '') {
            $attrs .= 'value="' . $this->escape($value) . '" ';
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
        
        // Add any additional HTML attributes
        foreach (['min', 'max', 'step', 'pattern', 'maxlength'] as $attr) {
            if ($this->hasProp($attr)) {
                $attrs .= $attr . '="' . $this->escape($this->prop($attr)) . '" ';
            }
        }
        
        $html .= '<input ' . $attrs . '>';
        
        // Close input group if icon present
        if ($icon) {
            $html .= '</div>';
        }
        
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
