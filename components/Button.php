<?php
/**
 * Button Component
 * Usage: Button::create(['variant' => 'primary', 'size' => 'sm'], 'Click Me')
 */

require_once __DIR__ . '/Component.php';

class Button extends Component {
    
    public function render() {
        $variant = $this->prop('variant', 'primary'); // primary, secondary, success, danger, warning, info
        $size = $this->prop('size', ''); // sm, lg, or empty for default
        $outline = $this->prop('outline', false);
        $block = $this->prop('block', false);
        $disabled = $this->prop('disabled', false);
        $type = $this->prop('type', 'button');
        $icon = $this->prop('icon');
        $loading = $this->prop('loading', false);
        
        // Build CSS classes
        $classes = ['btn'];
        
        if ($outline) {
            $classes[] = 'btn-outline-' . $variant;
        } else {
            $classes[] = 'btn-' . $variant;
        }
        
        if ($size) {
            $classes[] = 'btn-' . $size;
        }
        
        if ($block) {
            $classes[] = 'w-100';
        }
        
        if ($this->prop('class')) {
            $classes[] = $this->prop('class');
        }
        
        // Build attributes
        $attrs = 'type="' . $type . '" class="' . implode(' ', $classes) . '"';
        
        if ($disabled || $loading) {
            $attrs .= ' disabled';
        }
        
        // Add other HTML attributes
        foreach (['id', 'name', 'value', 'onclick', 'data-bs-toggle', 'data-bs-target'] as $attr) {
            if ($this->hasProp($attr)) {
                $attrs .= ' ' . $attr . '="' . $this->escape($this->prop($attr)) . '"';
            }
        }
        
        // Build button content
        $content = '';
        
        if ($loading) {
            $content .= '<span class="spinner-border spinner-border-sm me-2" role="status"></span>';
        } elseif ($icon) {
            $content .= '<i class="' . $this->escape($icon) . ' me-2"></i>';
        }
        
        $content .= $this->renderChildren();
        
        return "<button {$attrs}>{$content}</button>";
    }
}
