<?php
namespace EzeeTools\Helpers\Utils;

use EzeeTools\Error;

class ArrayUtils
{
  /**
   * Checks if multiple keys exist in the array
   *
   * @param mixed[] $keys     Representing keys to check
   * @param mixed[] $haystack Representing an array to be checked
   * @return boolean true     If all keys are found, false otherwise
   */
    public static function keysExist(array $keys, array $haystack): bool
    {
        $allKeysExist = true;
        foreach ($keys as $key) {
            if (!array_key_exists($key, $haystack)) {
                $allKeysExist = false;
                break;
            }
        }
        return $allKeysExist;
    }

  /**
   * Method adds the data to the array in the specific keymap
   *
   * @param string $str_keymap Representing map in the format key:key:[]
   *    When [] is passed, it will add the data to the array without specific key
   *    When string is passed, it will add data to the key, or create one if does not exist
   * @param mixed[] Array     Representing an array source
   * @param mixed $data       Representing data to be added to the particular array
   * @return mixed[] Array    with combined data
   */
    public static function addByKeymap(string $keymap, array &$source, $data): array
    {
      // Analyze keymap
        $keys = explode(":", $keymap, 2);

      // Check if only one element exists
        if (count($keys) == 1) {
          // Check if this is for pushing the data
            if ($keymap === '[]') {
                // Push into source
                $source[] = $data;
            } else {
              // Standard key. Check if it exists in the source
                if (!array_key_exists($keymap, $source)) {
                    // Does not exist yet. Create new key with data
                    $source[$keymap] = $data;
                } else {
                  // Key exists. Check if this is an array
                    if (is_array($source[$keymap])) {
                        $source[$keymap][] = $data;
                    } else {
                        $source[$keymap] = $data;
                    }
                }
            }
        } else {
          // More than one element exists. Check if key is for creating an array
            if (!array_key_exists($keys[0], $source)) {
                if ($keys[0] === '[]') {
                    // Create an array and pass it to the source
                    $source = [];
                    self::addByKeymap($keys[1], $source, $data);
                } else {
                    $source[$keys[0]] = [];
                }
            }
            self::addByKeymap($keys[1], $source[$keys[0]], $data);
        }

        return $source;
    }

  /**
   * Method gets the data based on the keymap given
   *
   * @param string $keymap        Representing keymap
   * @param mixed[] $source       Representing source data
   * @return mixed representing the data inside the given keymap, null otherwise
   */
    public static function getByKeymap(string $keymap, array $source)
    {
      // Deal with the keymap
        $keys = explode(":", $keymap, 2);

        $returnData = null;

        if (array_key_exists($keys[0], $source)) {
            if (count($keys) == 1) {
                $returnData = $source[$keys[0]];
            } else {
                // Check if child source is an array
                $childSource = $source[$keys[0]];
                if (!is_array($childSource)) return null;
                $returnData = self::getByKeymap($keys[1], $childSource);
            }
        }

        return $returnData;
    }

    /**
     * Sets the value on the given keymap
     *
     * @param string $keymap
     * @param array $source
     * @param mixed $value
     * @return array
     */
    public static function setByKeymap(string $keymap, array &$source, $value): array
    {
        list($firstKey, $remainingKey) = self::explode(':', $keymap, 2);

        if (!array_key_exists($firstKey, $source)) {
            // Key does not exist. Check if there is a remaining key to follow
            if ($remainingKey) {
                // Create key on the source and nest the setting method
                $source[$firstKey] = [];
                return self::setByKeymap($remainingKey, $source[$firstKey], $value);
            }
            // No remaining key set
            $source[$firstKey] = $value;
            return $source;
        }

        // Once key is confirmed, we simply nest the source
        if ($remainingKey) {
            return self::setByKeymap($remainingKey, $source[$firstKey], $value);
        }

        // No remaining key
        $source[$firstKey] = $value;
        return $source;
    }

    /**
     * Get an element of the collection based on key
     *
     * @param mixed $index
     * @param array $source
     * @return mixed|null
     */
    public static function getByKey($index, array $source)
    {
      return $source[$index] ?? null;
    }

    /**
     * Returns multiple keys
     *
     * @param string[] $keys
     * @param array $source
     * @param boolean $strict
     * @return array
     */
    public static function getByKeys(array $keys, array $source, bool $strict = false): array
    {
        $returnData = [];
        foreach ($keys as $key) {
            $returnData[$key] = self::getByKeymap($key, $source);
        }
        return $returnData;
    }

  /**
   * Method removes the relevant key from the array based on the keymap.
   *
   * @param string $keymap representing keymap (see above for examples)
   * @param mixed[] $source
   * @return boolean true if the requested data has been removed
   */
    public static function removeByKeymap(string $keymap, array &$source): bool
    {
        return self::removeByKeymapArray(self::keymapToArray($keymap), $source);
    }

  /**
   * Removes data by keymap array
   *
   * @param array $keymap
   * @param array $source
   * @return boolean true if the data has been removed
   */
    private static function removeByKeymapArray(array $keymap, array &$source): bool
    {
        foreach ($keymap as $key => $value) {
          // Check if the value is an array
            if (is_array($value)) {
                // Nesting. Check if the value contains an array
                if (array_key_exists(0, $value) && is_array($value[0])) {
                  // We are removing key from array of arrays
                    foreach ($value[0] as $subKey => $subValue) {
                        // For each key we need to unset the variable from $source
                        foreach ($source[$key] as &$target) {
                            unset($target[$subValue]);
                        }
                        return true;
                    }
                } else {
                  // This is just a string so continue removing
                    return self::removeByKeymapArray($value, $source[$key]);
                }
            } elseif (is_string($value)) {
              // Value check if the value is a string
                unset($source[$value]);
            }
        }
        return true;
    }


  /**
   * These are examples of valid keymaps
   * * 'id'                               will create ['id']
   * * 'id,email'                         will create ['id', 'email']
   * * 'user:id'                          will create ['user' => ['id']]
   * * 'user:id,email'                    will create ['user' => ['id', 'email']]
   * * 'user:orders[id,status]'           will create ['user' => [ 'orders' => [[ 'id', 'status' ]]]]
   * * 'user:orders[details:id]'          will create ['user' => [ 'orders' => [[ 'details' => [ 'id' ]]]]]
   * * '[id,email]'                       will create [[ 'id', 'email' ]]
   * * 'user:id,email|order:number,type   will create ['user' => ['id', 'email'], 'order' => ['number', 'type']]
   * IMPORTANT: If targeting subarrays, they must be at the last place in the keymap string
   *
   * @param string $keymap
   * @return mixed[] representing array keymap
   */
    public static function keymapToArray(string $keymap): array
    {
      // Initiate keymap and reference array
        $processedKeymap = [];
        $reference =& $processedKeymap;

      // Now let's check if we have multiple maps that will be merged
        if (strpos($keymap, '|')) {
          // Multiple arrays. Rerun method
            foreach (explode("|", $keymap) as $singleKeymap) {
                $processedKeymap = array_merge($processedKeymap, self::keymapToArray($singleKeymap));
            }
          // Return combined keymaps
            return $processedKeymap;
        }

      // We need to extract subarray from the keymap
        list($beforeSubarray, $subarray) = self::extractSubarrayFromKeymap($keymap);

      // Once split, we want to check if we have a string before a subarray
        if ($beforeSubarray) {
          // Check if we have multiple keys
            $beforeSubarrayParts = explode(':', $beforeSubarray);

          // Initiate counter, ta capture last element
            $cnt = 0;

            foreach ($beforeSubarrayParts as $beforeSubarrayPart) {
              // We only support multiple arguments if they sit as the last argument
                if ($cnt == count($beforeSubarrayParts) - 1) {
                    // This is the last element. We want to check if we are targeting multiple attributes
                    $attributes = explode(',', $beforeSubarrayPart);

                    // Check if we have subarray
                    if ($subarray) {
                        $reference[$beforeSubarrayPart] = [];
                        $reference =& $reference[$beforeSubarrayPart];
                    } else {
                        foreach ($attributes as $attribute) {
                            $reference[] = $attribute;
                        }
                    }
                } else {
                  // Add attribute as a key of new array and reassign reference
                    $reference[$beforeSubarrayPart] = [];
                    $reference =& $reference[$beforeSubarrayPart];
                }

                $cnt++;
            }
        }

      // Handle subarrays if any and add them to the last
        if ($subarray) {
          // Repeat the process but pass current reference to the method
            $reference[] = self::keymapToArray($subarray);
        }

        return $processedKeymap;
    }


  /**
   * Checks if the given string contains subarray, which is represented by [...]
   *
   * @param string $keymap representing a keymap
   * @return boolean true if contains subarray, false otherwise
   */
    protected static function doesKeymapContainSubarray(string $keymap): bool
    {
        return preg_match("/\[.*\]/", $keymap);
    }

  /**
   * Extracts subarray from keymap
   *
   * @param string $keymap
   * @return mixed[] with extracted subarray
   */
    protected static function extractSubarrayFromKeymap(string $keymap): array
    {
      // Check if the keymap contains subarray
        if (self::doesKeymapContainSubarray($keymap)) {
          // We need to extract everything that is before the subarray and after it
            $beforeSubarray = substr($keymap, 0, strpos($keymap, '['));
            $subarray       = substr($keymap, strpos($keymap, '[') + 1, strlen($keymap) - strpos($keymap, '[') - 2);
        } else {
          // No subarrays found
            $beforeSubarray = $keymap;
            $subarray       = null;
        }

        return [
        $beforeSubarray = strlen($beforeSubarray) == 0 ? null : $beforeSubarray,
        $subarray       = strlen($subarray) == 0 ? null : $subarray
        ];
    }

  /**
   * Splice is a combination of get and remove methods
   *
   * @param string $keymap representing keymap
   * @param mixed[] $source
   * @return mixed representing the data inside the given keymap, null otherwise
   */
    public static function spliceData($keymap, array &$source)
    {
        $returnData = self::getByKeymap($keymap, $source);
        self::removeByKeymap($keymap, $source);
        return $returnData;
    }

  /**
   * Method groups multiple arrays by an array map.
   *
   * @param mixed[] $source Array containing array of arrays to be grouped
   * @param mixed[] $arr_map containing map of how the array supposed to be grouped
   * @return mixed[] representing grouped array
   * @todo describe map structure
   */
    public static function sortByKeymapArray(array $source, array $map): array
    {
      // Create grouped array
        $grouped = [];
      // Iterate over source which is an array of arrays
        foreach ($source as $record) {
          // Run grouping
            self::sortAndAddDataByKeymapArray($record, $grouped, $map);
        }
        return $grouped;
    }

  /**
   * Method is used by sort_by_map method to group the data by the array map
   *
   * @param mixed[] $source Array   representing source data
   * @param mixed[] $grouped Array  referencing array holding grouped data
   * @param mixed[] $map Array      representing the grouping map
   * @return void                   with sorted array
   * @throws \Ezee\Error\InvalidArgumentError   If invalid argument is passed to the map
   */
    private static function sortAndAddDataByKeymapArray(array $source, array &$grouped, array $map): void
    {
      // Loop through the map to see values
        foreach ($map as $mapKey => $mapValue) {
          // Check the type of the key and values
            if (is_string($mapKey)) {
                // First, let's check if the $mapKey is a grouping key
                if (preg_match("/^\:.*/", $mapKey)) {
                  // We are dealing with a grouping key. Check if it exists inside the grouped array
                    $groupingKey = preg_replace("/^\:/", "", $mapKey);
                    if (!array_key_exists($groupingKey, $source)) {
                      // Key does not exist. Possible not been taken from the DB
                      continue;
                    }
                    if (!array_key_exists($source[$groupingKey], $grouped)) {
                        $grouped[$source[$groupingKey]] = [];
                    }
                  // Rerun grouping
                    self::sortAndAddDataByKeymapArray($source, $grouped[$source[$groupingKey]], $mapValue);
                } else {
                  // Not a grouping key
                    if (!array_key_exists($mapKey, $grouped)) {
                        $grouped[$mapKey] = [];
                    }
                  // Rerun grouping
                    self::sortAndAddDataByKeymapArray($source, $grouped[$mapKey], $mapValue);
                }
            } elseif (is_string($mapValue)) {
              // Standard value. We also need to check if the translation is set
                $keyParts = explode("|", $mapValue);
                if (count($keyParts) == 2) {
                    // We need to translate the key
                    $newKey = $keyParts[1];
                } else {
                    $newKey = $mapValue;
                }

                if (array_key_exists($keyParts[0], $source)) {
                  $grouped[$newKey] = $source[$keyParts[0]];
                }
            } elseif (is_int($mapKey)) {
              // We just need to push to the array as no specific key has been specified
                $grouped[] = [];
                $subgroup =& $grouped[count($grouped)-1];

              // Rerun grouping
                self::sortAndAddDataByKeymapArray($source, $subgroup, $mapValue);
            } else {
              // Unsupported type passed. Raise an exception
                throw new Error\InvalidArgumentError('Map can only contain string or array value. Got ' . gettype($mapValue));
            }
        }
    }

    public static function fromObject(\stdClass $object): array
    {
        return \json_decode(\json_encode($object), true);
    }

    public static function toObject($data): object
    {
      if (is_array($data)) {
        return (object) \json_decode(\json_encode($data));
      } elseif (is_object($data)) {
        return $data;
      }
      throw new Error\InvalidArgumentError(sprintf('%s must receive object or array. Got %s', __METHOD__, gettype($data)));
    }

    public static function explode(string $delimiter, string $string, int $parts = null): array
    {
      if (!$parts) {
        return explode($delimiter, $string);
      }

      $stringParts = explode($delimiter, $string, $parts);
      $returnData = [];
      for ($i = 0; $i < $parts; $i++) {
          $returnData[$i] = self::getByKey($i, $stringParts);
      }

      return $returnData;
    }

    public static function createNthWithContent(int $nth, $content): array
    {
      $array = [];
      for ($i = 1; $i <= $nth; $i++) {
        $array[] = $content;
      }
      return $array;
    }

    public static function unset(string $key, array &$array): void
    {
      if (isset($array[$key])) {
        unset($array[$key]);
      }
    }

    public static function firstKey(array $array)
    {
      $keys = array_keys($array);
      return array_shift($keys);
    }

    public static function first(array $array)
    {
      return self::getByKey(self::firstKey($array), $array);
    }

}
