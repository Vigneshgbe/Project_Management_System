<?php
/**
 * Modal Component
 * Usage: Modal::create(['id' => 'myModal', 'title' => 'Modal Title'], 'Modal content')
 */

require_once __DIR__ . '/Component.php';

class Modal extends Component {
    
    public function render() {
        $id = $this->prop('id', 'modal-' . uniqid());
        $title = $this->prop('title', '');
        $size = $this->prop('size', ''); // sm, lg, xl
        $centered = $this->prop('centered', false);
        $scrollable = $this->prop('scrollable', false);
        $footer = $this->prop('footer');
        $closeButton = $this->prop('closeButton', true);
        
        // Build dialog classes
        $dialogClasses = ['modal-dialog'];
        
        if ($size) {
            $dialogClasses[] = 'modal-' . $size;
        }
        
        if ($centered) {
            $dialogClasses[] = 'modal-dialog-centered';
        }
        
        if ($scrollable) {
            $dialogClasses[] = 'modal-dialog-scrollable';
        }
        
        $html = '<div class="modal fade" id="' . $this->escape($id) . '" tabindex="-1">';
        $html .= '<div class="' . implode(' ', $dialogClasses) . '">';
        $html .= '<div class="modal-content">';
        
        // Modal header
        if ($title || $closeButton) {
            $html .= '<div class="modal-header">';
            
            if ($title) {
                $html .= '<h5 class="modal-title">' . $this->escape($title) . '</h5>';
            }
            
            if ($closeButton) {
                $html .= '<button type="button" class="btn-close" data-bs-dismiss="modal"></button>';
            }
            
            $html .= '</div>';
        }
        
        // Modal body
        $html .= '<div class="modal-body">';
        $html .= $this->renderChildren();
        $html .= '</div>';
        
        // Modal footer
        if ($footer) {
            $html .= '<div class="modal-footer">';
            $html .= $footer;
            $html .= '</div>';
        }
        
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
        
        return $html;
    }
}
