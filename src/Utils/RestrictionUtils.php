<?php
namespace GoetasWebservices\XML\XSDReader\Utils;

class RestrictionUtils
{

    /**
     * Defines a list of acceptable values.
     *  
     * @param mixed $value
     * @param array $enumeration
     * @throws \InvalidArgumentException
     * @return mixed
     */
    public static function checkEnumeration($value, $enumeration)
    {
        if (!in_array($value, $enumeration)) {
            $values = implode(', ', $enumeration);
            throw new \InvalidArgumentException("The restriction enumeration with '$values' is not true");
        }   
        return $value;
    }
    
    /**
     * Defines the exact sequence of characters that are acceptable.
     * 
     * @param mixed $value
     * @param string $pattern
     * @throws \InvalidArgumentException
     * @return mixed
     */
    public static function checkPattern($value, $pattern)
    {
        if (!preg_match("/^{$pattern}$/", $value)) {
            throw new \InvalidArgumentException("The restriction pattern with '$pattern' is not true");
        }
        return $value;
    }
    
    /**
     * Specifies the maximum number of decimal places allowed. Must be equal to or greater than zero.
     * 
     * @param float $value
     * @param int $fractionDigits
     * @throws \InvalidArgumentException
     * @return float
     */
    public static function checkFractionDigits($value, $fractionDigits) 
    {
        if (!is_numeric($value)) {
            throw new \InvalidArgumentException("The '$value' is not a valid numeric");
        }
        if ($value < 0) {
            throw new \InvalidArgumentException("The '$value' must be equal to or greater than zero");
        }
        $count = 0;
        if ((int)$value != $value){
            $count = strlen($value) - strrpos($value, '.') - 1;
        }
        if ($count > $fractionDigits) {
            throw new \InvalidArgumentException("The restriction fraction digits with '$fractionDigits' is not true");
        }
        return $value;
    }
    
    /**
     * Specifies the exact number of digits allowed. Must be greater than zero.
     * 
     * @param float $value
     * @param int $totalDigits
     * @throws \InvalidArgumentException
     * @return float
     */
    public static function checkTotalDigits($value, $totalDigits) 
    {
        if (!is_numeric($value)) {
            throw new \InvalidArgumentException("The '$value' is not a valid numeric");
        }
        if ($value < 0) {
            throw new \InvalidArgumentException("The '$value' must be equal to or greater than zero");
        }
        $count = 0;
        if ($value === 0) {
            $count = 1;
        } else {
            $count = floor(log10(floor($value))) + 1;
        }
        
        if ($count > $totalDigits) {
            throw new \InvalidArgumentException("The restriction total digits with '$totalDigits' is not true");
        }
        return $value;
    }
    
    /**
     * Specifies the upper bounds for numeric values (the value must be less than this value)
     * 
     * @param mixed $value
     * @param int $maxExclusive
     * @throws \InvalidArgumentException
     * @return mixed
     */
    public static function checkMaxExclusive($value, $maxExclusive) 
    {
        if (!is_numeric($value)) {
            throw new \InvalidArgumentException("The '$value' is not a valid numeric");
        }
        if ($value >= $maxExclusive) {
            throw new \InvalidArgumentException("The restriction max exclusive with '$maxExclusive' is not true");
        }
        return $value;
    }
    
    /**
     * Specifies the upper bounds for numeric values (the value must be less than or equal to this value)
     * 
     * @param mixed $value
     * @param int $maxInclusive
     * @throws \InvalidArgumentException
     * @return mixed
     */
    public static function checkMaxInclusive($value, $maxInclusive) 
    {
        if (!is_numeric($value)) {
            throw new \InvalidArgumentException("The '$value' is not a valid numeric");
        }
        if ($value > $maxInclusive) {
            throw new \InvalidArgumentException("The restriction max inclusive with '$maxInclusive' is not true");
        }
        return $value;
    }
    
    /**
     * Retrive the number of characters or list items allowed. 
     * 
     * @param mixed $value
     * @throws \InvalidArgumentException
     * @return int
     */
    private static function getLength($value) 
    {
        $valueLength = 0;
        if (is_numeric($value)) {
            if ($value < 0) {
                throw new \InvalidArgumentException("The '$value' must be equal to or greater than zero");
            }
            $valueLength = $value;
        } else
        if (is_array($value)) {
            $valueLength = count($value);
        } else
        if (!is_numeric($value)) {
            $valueLength = strlen($value);
        }
        return $valueLength;
    }    
    
    /**
     * Specifies the exact number of characters or list items allowed. Must be equal to or greater than zero.
     * 
     * @param mixed $value
     * @param int $length
     * @throws \InvalidArgumentException
     * @return mixed
     */
    public static function checkLength($value, $length) 
    {
        if (static::getLength($value) != $length) {
            throw new \InvalidArgumentException("The restriction length with '$length' is not true");
        }
        return $value;
    }    
    
    /**
     * Specifies the maximum number of characters or list items allowed. Must be equal to or greater than zero
     * 
     * @param mixed $value
     * @param int $maxLength
     * @throws \InvalidArgumentException
     * @return mixed
     */
    public static function checkMaxLength($value, $maxLength) 
    {
        if (static::getLength($value) > $maxLength) {
            throw new \InvalidArgumentException("The restriction max length with '$maxLength' is not true");
        }
        return $value;
    }
    
    /**
     * Specifies the minimum number of characters or list items allowed. Must be equal to or greater than zero
     * 
     * @param mixed $value
     * @param int $minLength
     * @throws \InvalidArgumentException
     * @return mixed
     */
    public static function checkMinLength($value, $minLength) 
    {
        if (static::getLength($value) < $minLength) {
            throw new \InvalidArgumentException("The restriction min length with '$minLength' is not true");
        }
        return $value;
    }
    
    /**
     * Specifies the lower bounds for numeric values (the value must be greater than this value)
     * 
     * @param mixed $value
     * @param int $minExclusive
     * @throws \InvalidArgumentException
     * @return mixed
     */
    public static function checkMinExclusive($value, $minExclusive) 
    {
        if (!is_numeric($value)) {
            throw new \InvalidArgumentException("The '$value' is not a valid numeric");
        }
        if ($value <= $minExclusive) {
            throw new \InvalidArgumentException("The restriction min exclusive with '$minExclusive' is not true");
        }
        return $value;
    }
    
    /**
     * Specifies the lower bounds for numeric values (the value must be greater than or equal to this value)
     * 
     * @param mixed $value
     * @param int $minInclusive
     * @throws \InvalidArgumentException
     * @return mixed
     */
    public static function checkMinInclusive($value, $minInclusive) 
    {
        if (!is_numeric($value)) {
            throw new \InvalidArgumentException("The '$value' is not a valid numeric");
        }
        if ($value < $minInclusive) {
            throw new \InvalidArgumentException("The restriction min inclusive with '$minInclusive' is not true");
        }
        return $value;
    }

    /**
     * Specifies how white space (line feeds, tabs, spaces, and carriage returns) is handled
     * 
     * @param mixed $value
     * @param string $whiteSpace
     * @throws \InvalidArgumentException
     * @return mixed
     */
    public static function checkWhiteSpace($value, $whiteSpace) 
    {
        $output = $value;
        if ($whiteSpace == 'replace' || $whiteSpace == 'collapse') {
            $output = preg_replace('/\s/', ' ', $output);
        }
        if ($whiteSpace == 'collapse') {
            $output = preg_replace('/\s+/', ' ', $output);
        }
        return $output;
    }	

}
