<?php
  class ArrayAutoMerge 
  {
      private $id_key;
      private $delimiter;
      
      function __construct($id_key="Id", $delimiter="_"){
          $this->id_key = $id_key;
          $this->delimiter = $delimiter;
      }
      
      // auto merge
      public function auto_merge($data) {
          $output = array();
          foreach ($data as $record) {
              $this->merge_expanded($output, $this->expand_record($record)); 
          }
          return $output;
      }
      
      // expand a record into a complex array structure
      private function expand_record($record) {
          $result = array();
          foreach (array_keys($record) as $field) {
              $path = explode($this->delimiter, $field);
              $this->set_field_value($result, $path, $record[$field]);
          }
          return $result;
      }


      // recursive function to set the value to a record field in the structure
      // it generates the array structure as well
      private function set_field_value(& $res, $path, $val) {
          if (count($path)==1) {
              $res[$path[0]] = $val;
          } else {
              if (!isset($res[$path[0]])) {
                  $res[$path[0]] = array(); 
              }
              $this->set_field_value($res[$path[0]], array_slice($path, 1), $val);
          }
      }


      // search the id value in a two-dimensional array
      private function search_for_id($array, $id_value) {
          foreach ($array as $key => $val) {
              if ($val[$this->id_key] === $id_value) {
                  return $key;
              }
          }
          return null;
      }


      // get the list of keys which values are arrays
      private function get_array_keys($array) {
          $result = array();
          foreach ($array as $key => $val) {
              if (is_array($val)) {
                  array_push($result, $key);
              }
          }
          return $result;
      }


      // all entries in the array are null 
      private function all_entries_null($array) {
          foreach ($array as $key => $val) {
              if ((!is_array($val) && !is_null($val)) || (is_array($val) && !$this->all_entries_null($val))) {
                  return false;
              } 
          }
          return true;
      }

      // merge one expanded record to the output
      private function merge_expanded(& $output, $array) {
          $idx = NULL;
          if (array_key_exists($this->id_key, $array)) {
              if (is_null($array[$this->id_key])) return;
              $idx = $this->search_for_id($output, $array[$this->id_key]);
          } else if ($this->all_entries_null($array)) {
              return;
          }

          if (is_null($idx)) {
              $newArray = array();
              foreach ($array as $key => $val) {
                  $newArray[$key] = is_array($val) ? array() : $val;
              }
              array_push($output, $newArray);
              end($output);
              $idx = key($output);
          }

          foreach ($this->get_array_keys($array) as $array_key) {
              $this->merge_expanded($output[$idx][$array_key], $array[$array_key]);
          }
      }
  }
?>
