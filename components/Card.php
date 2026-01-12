<?php
/**
 * Card Component
 * Usage: Card::create(['title' => 'Card Title', 'footer' => 'Footer text'], 'Card body content')
 */

require_once __DIR__ . '/Component.php';

class Card extends Component {
    
    public function render() {
        $title = $this->prop('title');
        $subtitle = $this->prop('subtitle');
        $footer = $this->prop('footer');
        $headerClass = $this->prop('headerClass', '');
        $bodyClass = $this->prop('bodyClass', '');
        $shadow = $this->prop('shadow', true);
        $noPadding = $this->prop('noPadding', false);
        
        // Build card classes
        $classes = ['card'];
        if ($shadow) {
            $classes[] = 'shadow';
        }
        if ($this->prop('class')) {
            $classes[] = $this->prop('class');
        }
        
        $html = '<div class="' . implode(' ', $classes) . '">';
        
        // Card header
        if ($title || $this->prop('headerActions')) {
            $html .= '<div class="card-header ' . $headerClass . '">';
            
            if ($this->prop('headerActions')) {
                $html .= '<div class="d-flex justify-content-between align-items-center">';
            }
            
            if ($title) {
                $html .= '<h6 class="m-0 font-weight-bold text-primary">' . $this->escape($title) . '</h6>';
                if ($subtitle) {
                    $html .= '<p class="text-muted mb-0 mt-1">' . $this->escape($subtitle) . '</p>';
                }
            }
            
            if ($this->prop('headerActions')) {
                $html .= '<div>' . $this->prop('headerActions') . '</div>';
            }
            
            if ($this->prop('headerActions')) {
                $html .= '</div>';
            }
            
            $html .= '</div>';
        }
        
        // Card body
        $bodyClasses = $noPadding ? '' : 'card-body';
        if ($bodyClass) {
            $bodyClasses .= ' ' . $bodyClass;
        }
        
        $html .= '<div class="' . $bodyClasses . '">';
        $html .= $this->renderChildren();
        $html .= '</div>';
        
        // Card footer
        if ($footer) {
            $html .= '<div class="card-footer text-muted">' . $footer . '</div>';
        }
        
        $html .= '</div>';
        
        return $html;
    }
}
