<?php
namespace GoetasWebservices\XML\XSDReader\Utils;

use GoetasWebservices\XML\XSDReader\Exception\RestrictionException;

class RestrictionUtils
{

    /**
     * Defines a list of acceptable values.
     *  
     * @param mixed $value
     * @param array $enumeration
     * @throws RestrictionException
     * @return mixed
     */
    public static function checkEnumeration($value, $enumeration)
    {
        if (!in_array($value, $enumeration)) {
            $values = implode(', ', $enumeration);
            throw new RestrictionException(
                    "The restriction enumeration with '$values' is not true", 
                    RestrictionException::ERROR_CODE_ENUMARATION,
                    $value,
                    $enumeration);
        }   
        return $value;
    }
    
    /**
     * Defines the exact sequence of characters that are acceptable.
     * 
     * @param mixed $value
     * @param string $pattern
     * @throws RestrictionException
     * @return mixed
     */
    public static function checkPattern($value, $pattern)
    {
        if (!preg_match("/^{$pattern}$/", $value)) {
            throw new RestrictionException(
                    "The restriction pattern with '$pattern' is not true", 
                    RestrictionException::ERROR_CODE_PATTERN,
                    $value,
                    $pattern);
        }
        return $value;
    }
    
    /**
     * Check is numeric valid
     * 
     * @param mixed $value
     * @return mixed
     * @throws RestrictionException
     */
    private static function getNumeric($value) 
    {
        if (!is_numeric($value)) {
            throw new RestrictionException(
                    "The '$value' is not a valid numeric", 
                    RestrictionException::ERROR_CODE_VALUE,
                    $value,
                    'is_numeric');
        }
        return $value + 0;
    }
    
    /**
     * Specifies the maximum number of decimal places allowed. Must be equal to or greater than zero.
     * 
     * @param float $value
     * @param int $fractionDigits
     * @throws RestrictionException
     * @return float
     */
    public static function checkFractionDigits($value, $fractionDigits) 
    {
        $numeric = static::getNumeric($value);
        if ($numeric < 0) {
            throw new RestrictionException(
                    "The '$numeric' must be equal to or greater than zero", 
                    RestrictionException::ERROR_CODE_GTE,
                    $numeric);
        }
        $count = 0;
        if ((int)$numeric != $numeric){
            $count = strlen($numeric) - strrpos($numeric, '.') - 1;
        }
        if ($count > $fractionDigits) {
            throw new RestrictionException(
                    "The restriction fraction digits with '$fractionDigits' is not true", 
                    RestrictionException::ERROR_CODE_FRACTION_DIGITS,
                    $value,
                    $fractionDigits);
        }
        return $numeric;
    }
    
    /**
     * Specifies the exact number of digits allowed. Must be greater than zero.
     * 
     * @param float $value
     * @param int $totalDigits
     * @throws RestrictionException
     * @return float
     */
    public static function checkTotalDigits($value, $totalDigits) 
    {
        $numeric = static::getNumeric($value);
        if ($numeric < 0) {
            throw new RestrictionException(
                    "The '$numeric' must be equal to or greater than zero", 
                    RestrictionException::ERROR_CODE_GTE,
                    $numeric);
        }
        $count = 0;
        if ($numeric === 0) {
            $count = 1;
        } else {
            $count = floor(log10(floor($numeric))) + 1;
        }
        
        if ($count > $totalDigits) {
            throw new RestrictionException(
                    "The restriction total digits with '$totalDigits' is not true", 
                    RestrictionException::ERROR_CODE_TOTAL_DIGITS,
                    $value,
                    $totalDigits);
        }
        return $numeric;
    }
    
    /**
     * Specifies the upper bounds for numeric values (the value must be less than this value)
     * 
     * @param mixed $value
     * @param int $maxExclusive
     * @throws RestrictionException
     * @return mixed
     */
    public static function checkMaxExclusive($value, $maxExclusive) 
    {
        $numeric = static::getNumeric($value);
        if ($numeric >= $maxExclusive) {
            throw new RestrictionException(
                    "The restriction max exclusive with '$maxExclusive' is not true", 
                    RestrictionException::ERROR_CODE_MAX_EXCLUSIVE,
                    $value,
                    $maxExclusive);
        }
        return $numeric;
    }
    
    /**
     * Specifies the upper bounds for numeric values (the value must be less than or equal to this value)
     * 
     * @param mixed $value
     * @param int $maxInclusive
     * @throws RestrictionException
     * @return mixed
     */
    public static function checkMaxInclusive($value, $maxInclusive) 
    {
        $numeric = static::getNumeric($value);
        if ($numeric > $maxInclusive) {
            throw new RestrictionException(
                    "The restriction max inclusive with '$maxInclusive' is not true", 
                    RestrictionException::ERROR_CODE_MAX_INCLUSIVE,
                    $value,
                    $maxInclusive);
        }
        return $numeric;
    }
    
    /**
     * Retrive the number of characters or list items allowed. 
     * 
     * @param mixed $value
     * @param string $nativeType
     * @throws RestrictionException
     * @return int
     */
    private static function getLength($value, $nativeType=null) 
    {
        $valueLength = 0;
        if (in_array($nativeType, ['int','float','integer']) && is_numeric($value)) {
            $valueLength = static::getNumeric($value);
        } else
        if (is_array($value)) {
            $valueLength = count($value);
        } else {
            $valueLength = strlen($value);
        }
        if ($valueLength < 0) {
            throw new RestrictionException(
                    "The '$value' must be equal to or greater than zero", 
                    RestrictionException::ERROR_CODE_GTE,
                    $value);
        }
        return $valueLength;
    }    
    
    /**
     * Specifies the exact number of characters or list items allowed. Must be equal to or greater than zero.
     * 
     * @param mixed $value
     * @param int $length
     * @param string $nativeType
     * @throws RestrictionException
     * @return mixed
     */
    public static function checkLength($value, $length, $nativeType=null) 
    {
        if (static::getLength($value, $nativeType) != $length) {
            throw new RestrictionException(
                    "The restriction length with '$length' is not true", 
                    RestrictionException::ERROR_CODE_LENGTH,
                    $value,
                    $length);
        }
        return $value;
    }    
    
    /**
     * Specifies the maximum number of characters or list items allowed. Must be equal to or greater than zero
     * 
     * @param mixed $value
     * @param int $maxLength
     * @param string $nativeType
     * @throws RestrictionException
     * @return mixed
     */
    public static function checkMaxLength($value, $maxLength, $nativeType=null) 
    {
        if (static::getLength($value, $nativeType) > $maxLength) {
            throw new RestrictionException(
                    "The restriction max length with '$maxLength' is not true", 
                    RestrictionException::ERROR_CODE_MAX_LENGTH,
                    $value,
                    $maxLength);
        }
        return $value;
    }
    
    /**
     * Specifies the minimum number of characters or list items allowed. Must be equal to or greater than zero
     * 
     * @param mixed $value
     * @param int $minLength
     * @param string $nativeType
     * @throws RestrictionException
     * @return mixed
     */
    public static function checkMinLength($value, $minLength, $nativeType=null) 
    {
        if (static::getLength($value, $nativeType) < $minLength) {
            throw new RestrictionException(
                    "The restriction min length with '$minLength' is not true", 
                    RestrictionException::ERROR_CODE_MIN_LENGTH,
                    $value,
                    $minLength);
        }
        return $value;
    }
    
    /**
     * Specifies the lower bounds for numeric values (the value must be greater than this value)
     * 
     * @param mixed $value
     * @param int $minExclusive
     * @throws RestrictionException
     * @return mixed
     */
    public static function checkMinExclusive($value, $minExclusive) 
    {
        $numeric = static::getNumeric($value);
        if ($numeric <= $minExclusive) {
            throw new RestrictionException(
                    "The restriction min exclusive with '$minExclusive' is not true", 
                    RestrictionException::ERROR_CODE_MIN_EXCLUSIVE,
                    $value,
                    $minExclusive);
        }
        return $numeric;
    }
    
    /**
     * Specifies the lower bounds for numeric values (the value must be greater than or equal to this value)
     * 
     * @param mixed $value
     * @param int $minInclusive
     * @throws RestrictionException
     * @return mixed
     */
    public static function checkMinInclusive($value, $minInclusive) 
    {
        $numeric = static::getNumeric($value);
        if ($numeric < $minInclusive) {
            throw new RestrictionException(
                    "The restriction min inclusive with '$minInclusive' is not true", 
                    RestrictionException::ERROR_CODE_MIN_INCLUSIVE,
                    $value,
                    $minInclusive);
        }
        return $numeric;
    }

    /**
     * Specifies how white space (line feeds, tabs, spaces, and carriage returns) is handled
     * 
     * @param mixed $value
     * @param string $whiteSpace
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
