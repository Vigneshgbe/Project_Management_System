<?php
/**
 * Badge Component
 * Usage: Badge::create(['variant' => 'success'], 'Active')
 */

require_once __DIR__ . '/Component.php';

class Badge extends Component {
    
    public function render() {
        $variant = $this->prop('variant', 'secondary');
        $pill = $this->prop('pill', false);
        $icon = $this->prop('icon');
        
        // Build classes
        $classes = ['badge', 'bg-' . $variant];
        
        if ($pill) {
            $classes[] = 'rounded-pill';
        }
        
        if ($this->prop('class')) {
            $classes[] = $this->prop('class');
        }
        
        $html = '<span class="' . implode(' ', $classes) . '">';
        
        if ($icon) {
            $html .= '<i class="' . $this->escape($icon) . ' me-1"></i>';
        }
        
        $html .= $this->renderChildren();
        $html .= '</span>';
        
        return $html;
    }
}
