<?php
/**
 * Base Component Class
 * All UI components extend this class
 * Inspired by React component architecture
 */

abstract class Component {
    protected $props = [];
    protected $children = [];
    
    /**
     * Constructor
     * @param array $props Component properties (like React props)
     * @param mixed $children Child components or content
     */
    public function __construct($props = [], $children = null) {
        $this->props = $props;
        $this->children = is_array($children) ? $children : [$children];
    }
    
    /**
     * Get prop value
     * @param string $key Prop key
     * @param mixed $default Default value if prop doesn't exist
     * @return mixed
     */
    protected function prop($key, $default = null) {
        return $this->props[$key] ?? $default;
    }
    
    /**
     * Check if prop exists
     * @param string $key Prop key
     * @return bool
     */
    protected function hasProp($key) {
        return isset($this->props[$key]);
    }
    
    /**
     * Render children components or content
     * @return string
     */
    protected function renderChildren() {
        $output = '';
        foreach ($this->children as $child) {
            if ($child instanceof Component) {
                $output .= $child->render();
            } else {
                $output .= $child;
            }
        }
        return $output;
    }
    
    /**
     * Sanitize output for XSS protection
     * @param string $value Value to sanitize
     * @return string
     */
    protected function escape($value) {
        return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Build HTML attributes from props
     * @param array $attributes Attribute names to include
     * @return string
     */
    protected function buildAttributes($attributes = []) {
        $html = '';
        foreach ($attributes as $attr) {
            if ($this->hasProp($attr)) {
                $value = $this->prop($attr);
                if (is_bool($value)) {
                    if ($value) {
                        $html .= ' ' . $attr;
                    }
                } else {
                    $html .= ' ' . $attr . '="' . $this->escape($value) . '"';
                }
            }
        }
        return $html;
    }
    
    /**
     * Abstract render method - must be implemented by child classes
     * @return string HTML output
     */
    abstract public function render();
    
    /**
     * Magic method to output component as string
     * @return string
     */
    public function __toString() {
        try {
            return $this->render();
        } catch (Exception $e) {
            return "<!-- Component Error: " . $e->getMessage() . " -->";
        }
    }
    
    /**
     * Static factory method for cleaner syntax
     * @param array $props Component properties
     * @param mixed $children Child components
     * @return static
     */
    public static function create($props = [], $children = null) {
        return new static($props, $children);
    }
}
