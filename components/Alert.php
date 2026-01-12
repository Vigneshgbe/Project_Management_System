<?php
/**
 * Alert Component
 * Usage: Alert::create(['variant' => 'success', 'dismissible' => true], 'Success message')
 */

require_once __DIR__ . '/Component.php';

class Alert extends Component {
    
    public function render() {
        $variant = $this->prop('variant', 'info'); // success, danger, warning, info, primary, secondary
        $dismissible = $this->prop('dismissible', false);
        $icon = $this->prop('icon');
        $title = $this->prop('title');
        
        // Build classes
        $classes = ['alert', 'alert-' . $variant];
        
        if ($dismissible) {
            $classes[] = 'alert-dismissible fade show';
        }
        
        if ($this->prop('class')) {
            $classes[] = $this->prop('class');
        }
        
        $html = '<div class="' . implode(' ', $classes) . '" role="alert">';
        
        // Icon
        if ($icon) {
            $html .= '<i class="' . $this->escape($icon) . ' me-2"></i>';
        } else {
            // Default icons based on variant
            $defaultIcons = [
                'success' => 'fas fa-check-circle',
                'danger' => 'fas fa-exclamation-circle',
                'warning' => 'fas fa-exclamation-triangle',
                'info' => 'fas fa-info-circle'
            ];
            if (isset($defaultIcons[$variant])) {
                $html .= '<i class="' . $defaultIcons[$variant] . ' me-2"></i>';
            }
        }
        
        // Title
        if ($title) {
            $html .= '<strong>' . $this->escape($title) . '</strong><br>';
        }
        
        // Content
        $html .= $this->renderChildren();
        
        // Dismiss button
        if ($dismissible) {
            $html .= '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
        }
        
        $html .= '</div>';
        
        return $html;
    }
}
