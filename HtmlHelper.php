<?php
// ----------------------------------------------------------------------------
// Features:	html輔助類別
// Author:		Neil
// Related:
// Log: 
// ----------------------------------------------------------------------------
 /* 
 功能說明 : 
 input.select.textarea html 生成


 使用說明 :
 $htmlhelper = new HtmlHelper($default_values);

 $htmlhelper->input('text', ['name' => 'inputname']);
 $htmlhelper->select($options, ['name' => 'selectname']);
 */

// @todo html disabled and default value processing

class HtmlHelper {
  protected $values = array();

  public function __construct($values = array()) {
    $this->values = $values;
  }

  public function setValues($values)
  {
    $this->values = $values;
  }

  // public function getValues()
  // {
  //   return $this->values;
  // }

  public function input($type, $attributes = array(), $isMultiple = false) {
    $attributes['type'] = $type;
    if (($type == 'radio') || ($type == 'checkbox')) {
      if ($this->isOptionSelected($attributes['name'] ?? null,
                                  $attributes['value'] ?? null)) {
        $attributes['checked'] = true;
      }
    }

    return $this->tag('input', $attributes, $isMultiple);
  }

  public function select($options, $attributes = array()) {
    $multiple = $attributes['multiple'] ?? false;

    return
      $this->start('select', $attributes, $multiple) .
      $this->options($attributes['name'] ?? null, $options) .
      $this->end('select');
  }

  public function textarea($attributes = array()) {
    $name = $attributes['name'] ?? null;
    $value = $this->values[$name] ?? '';

    return $this->start('textarea', $attributes) .
           htmlentities($value) .
           $this->end('textarea');
  }

  public function tag($tag, $attributes = array(), $isMultiple = false) {
    return "<$tag {$this->attributes($attributes, $isMultiple)} />";
  }

  public function start($tag, $attributes = array(), $isMultiple = false) {
    $valueAttribute = (! (($tag == 'select')||($tag == 'textarea')));
    $attrs = $this->attributes($attributes, $isMultiple, $valueAttribute);

    return "<$tag $attrs>";
  }

  public function end($tag) {
    return "</$tag>";
  }

  protected function attributes($attributes, $isMultiple,
                                $valueAttribute = true) {
    $tmp = array();

    // 預設值處理不好用要再改
    // if ($valueAttribute && isset($attributes['name']) &&
    //     array_key_exists($attributes['name'], $this->values)) {
    //     $attributes['value'] = $this->values[$attributes['name']];
    //     var_dump($attributes['value']);
    // }

    foreach ($attributes as $k => $v) {
      if (is_bool($v)) {
        if ($v) {
          $tmp[] = $this->encode($k);
        }
      } else {
        $value = $this->encode($v);

        if ($isMultiple && ($k == 'name')) {
            $value .= '[]';
        }

        $tmp[] = "$k=\"$value\"";
      }
    }

    return implode(' ', $tmp);
  }

  protected function options($name, $options) {
    $tmp = array();

    foreach ($options as $k => $v) {
      $s = "<option  value=\"{$this->encode($k)}\"";
      if ($this->isOptionSelected($name, $k)) {
          $s .= ' selected';
      }
      $s .= ">{$this->encode($v)}</option>";
      $tmp[] = $s;
    }

    return implode('', $tmp);
  }

  protected function isOptionSelected($name, $value) {
    if (! isset($this->values[$name])) {
      return false;
    } else if (is_array($this->values[$name])) {
      return in_array($value, $this->values[$name]);
    } else {
      return $value == $this->values[$name];
    }
  }

  public function encode($s) {
    return htmlentities($s);
  }
}