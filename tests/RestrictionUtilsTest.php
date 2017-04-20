<?php 
namespace GoetasWebservices\XML\XSDReader\Tests;

use GoetasWebservices\XML\XSDReader\Utils\RestrictionUtils;
use GoetasWebservices\XML\XSDReader\Exception\RestrictionException;

class RestrictionUtilsTest extends BaseTest
{

    public function testEnumerationValid()
    {
        $value = 'A1';
        $output = RestrictionUtils::checkEnumeration($value, ['A1','B2','B3']);
        $this->assertEquals($value, $output);
    }
    
    public function testEnumerationInvalid()
    {
        $this->setExpectedException(RestrictionException::class, null, RestrictionException::ERROR_CODE_ENUMARATION);
        RestrictionUtils::checkEnumeration('C3', ['A1','B2','B3']);
    }
    
    public function testPatternValid()
    {
        $value = '123456';
        $output = RestrictionUtils::checkPattern($value, '[0-9]{6}');
        $this->assertEquals($value, $output);
    }
    
    public function testPatternInvalid()
    {
        $this->setExpectedException(RestrictionException::class, null, RestrictionException::ERROR_CODE_PATTERN);
        RestrictionUtils::checkPattern('1234', '[0-9]{6}');
    }
    
    public function testFractionDigitsValid() 
    {
        $value = '12.254';
        $output = RestrictionUtils::checkFractionDigits($value, 3);
        $this->assertEquals($value, $output);
        $this->assertTrue(is_numeric($output));
        
        $value = '12.2';
        $output = RestrictionUtils::checkFractionDigits($value, 3);
        $this->assertEquals($value, $output);
        $this->assertTrue(is_numeric($output));
        
        $value = '12';
        $output = RestrictionUtils::checkFractionDigits($value, 3);
        $this->assertEquals($value, $output);
        $this->assertTrue(is_numeric($output));
    }
    
    public function testFractionDigitsNaN() 
    {
        $this->setExpectedException(RestrictionException::class, null, RestrictionException::ERROR_CODE_VALUE);
        RestrictionUtils::checkFractionDigits('nan', 3);
    }
    
    public function testFractionDigitsInvalid() 
    {
        $this->setExpectedException(RestrictionException::class, null, RestrictionException::ERROR_CODE_FRACTION_DIGITS);
        RestrictionUtils::checkFractionDigits('12.2557', 3);
    }
    
    public function testTotalDigitsValid() 
    {
        $value = '125.254';
        $output = RestrictionUtils::checkTotalDigits($value, 3);
        $this->assertEquals($value, $output);
        $this->assertTrue(is_numeric($output));
        
        $value = '12.2';
        $output = RestrictionUtils::checkTotalDigits($value, 3);
        $this->assertEquals($value, $output);
        $this->assertTrue(is_numeric($output));
        
        $value = '2';
        $output = RestrictionUtils::checkTotalDigits($value, 3);
        $this->assertEquals($value, $output);
        $this->assertTrue(is_numeric($output));
    }
    
    public function testTotalDigitsNaN() 
    {
        $this->setExpectedException(RestrictionException::class, null, RestrictionException::ERROR_CODE_VALUE);
        RestrictionUtils::checkTotalDigits('nan', 3);
    }
    
    public function testTotalDigitsInvalid_1() 
    {
        $this->setExpectedException(RestrictionException::class, null, RestrictionException::ERROR_CODE_TOTAL_DIGITS);
        RestrictionUtils::checkTotalDigits('1287.2557', 3);
    }
    
    public function testTotalDigitsInvalid_2() 
    {
        $this->setExpectedException(RestrictionException::class, null, RestrictionException::ERROR_CODE_TOTAL_DIGITS);
        RestrictionUtils::checkTotalDigits('1287', 3);
    }
    
    public function testMaxExclusiveValid() 
    {
        $value = '999';
        $output = RestrictionUtils::checkMaxExclusive($value, 1000);
        $this->assertEquals($value, $output);
        $this->assertTrue(is_numeric($output));
        
        $value = '1';
        $output = RestrictionUtils::checkMaxExclusive($value, 1000);
        $this->assertEquals($value, $output);
        $this->assertTrue(is_numeric($output));
    }
    
    public function testMaxExclusiveInvalid_1() 
    {
        $this->setExpectedException(RestrictionException::class, null, RestrictionException::ERROR_CODE_MAX_EXCLUSIVE);
        RestrictionUtils::checkMaxExclusive('1000', 1000);
    }
    
    public function testMaxExclusiveInvalid_2() 
    {
        $this->setExpectedException(RestrictionException::class, null, RestrictionException::ERROR_CODE_MAX_EXCLUSIVE);
        RestrictionUtils::checkMaxExclusive('1002', 1000);
    }
        
    public function testMinExclusiveValid() 
    {
        $value = '999';
        $output = RestrictionUtils::checkMinExclusive($value, 900);
        $this->assertEquals($value, $output);
        $this->assertTrue(is_numeric($output));
        
        $value = '1';
        $output = RestrictionUtils::checkMinExclusive($value, 0);
        $this->assertEquals($value, $output);
        $this->assertTrue(is_numeric($output));
    }
    
    public function testMinExclusiveInvalid_1() 
    {
        $this->setExpectedException(RestrictionException::class, null, RestrictionException::ERROR_CODE_MIN_EXCLUSIVE);
        RestrictionUtils::checkMinExclusive('1000', 1000);
    }
    
    public function testMinExclusiveInvalid_2() 
    {
        $this->setExpectedException(RestrictionException::class, null, RestrictionException::ERROR_CODE_MIN_EXCLUSIVE);
        RestrictionUtils::checkMinExclusive('1002', 1003);
    }
    
    public function testMaxInclusiveValid() 
    {
        $value = '1000';
        $output = RestrictionUtils::checkMaxInclusive($value, 1000);
        $this->assertEquals($value, $output);
        $this->assertTrue(is_numeric($output));
        
        $value = '1';
        $output = RestrictionUtils::checkMaxInclusive($value, 1000);
        $this->assertEquals($value, $output);
        $this->assertTrue(is_numeric($output));
    }
    
    public function testMaxInclusiveInvalid_1() 
    {
        $this->setExpectedException(RestrictionException::class, null, RestrictionException::ERROR_CODE_MAX_INCLUSIVE);
        RestrictionUtils::checkMaxInclusive('1001', 1000);
    }
    
    public function testMaxInclusiveInvalid_2() 
    {
        $this->setExpectedException(RestrictionException::class, null, RestrictionException::ERROR_CODE_MAX_INCLUSIVE);
        RestrictionUtils::checkMaxInclusive('1002', 1000);
    }
    
    public function testMinInclusiveValid() 
    {
        $value = '1000';
        $output = RestrictionUtils::checkMinInclusive($value, 1000);
        $this->assertEquals($value, $output);
        $this->assertTrue(is_numeric($output));
        
        $value = '1';
        $output = RestrictionUtils::checkMinInclusive($value, 0);
        $this->assertEquals($value, $output);
        $this->assertTrue(is_numeric($output));
    }
    
    public function testMinInclusiveInvalid_1() 
    {
        $this->setExpectedException(RestrictionException::class, null, RestrictionException::ERROR_CODE_MIN_INCLUSIVE);
        RestrictionUtils::checkMinInclusive('5', 1000);
    }
    
    public function testMinInclusiveInvalid_2() 
    {
        $this->setExpectedException(RestrictionException::class, null, RestrictionException::ERROR_CODE_MIN_INCLUSIVE);
        RestrictionUtils::checkMinInclusive('900', 1000);
    }
        
    public function testLengthValid() 
    {
        $value = '1000';
        $output = RestrictionUtils::checkLength($value, 1000, 'int');
        $this->assertEquals($value, $output);
        
        $value = '2000';
        $output = RestrictionUtils::checkLength($value, 2000, 'integer');
        $this->assertEquals($value, $output);

        $value = '1000.1';
        $output = RestrictionUtils::checkLength($value, 1000.1, 'float');
        $this->assertEquals($value, $output);
        
        $value = '1000';
        $output = RestrictionUtils::checkLength($value, 4);
        $this->assertEquals($value, $output);
        
        $value = 'my text';
        $output = RestrictionUtils::checkLength($value, 7);
        $this->assertEquals($value, $output);
        
        $value = ['A1', 'B2', 'B3'];
        $output = RestrictionUtils::checkLength($value, 3);
        $this->assertEquals($value, $output);
    }    
    
    public function testLengthInvalid_1() 
    {
        $this->setExpectedException(RestrictionException::class, null, RestrictionException::ERROR_CODE_LENGTH);
        RestrictionUtils::checkLength('1000', 900, 'int');
    }
    
    public function testLengthInvalid_2() 
    {
        $this->setExpectedException(RestrictionException::class, null, RestrictionException::ERROR_CODE_LENGTH);
        RestrictionUtils::checkLength('2000', 1500, 'integer');
    }
    
    public function testLengthInvalid_3() 
    {
        $this->setExpectedException(RestrictionException::class, null, RestrictionException::ERROR_CODE_LENGTH);
        RestrictionUtils::checkLength('124.2', 124.1, 'float');
    }
    
    public function testLengthInvalid_4() 
    {
        $this->setExpectedException(RestrictionException::class, null, RestrictionException::ERROR_CODE_LENGTH);
        RestrictionUtils::checkLength('5000', 3);
    }
    
    public function testLengthInvalid_5() 
    {
        $this->setExpectedException(RestrictionException::class, null, RestrictionException::ERROR_CODE_LENGTH);
        RestrictionUtils::checkLength('my text', 8);
    }
    
    public function testLengthInvalid_6() 
    {
        $this->setExpectedException(RestrictionException::class, null, RestrictionException::ERROR_CODE_LENGTH);
        RestrictionUtils::checkLength(['A1', 'B2', 'B3'], 4);
    }
    
    public function testMaxLengthValid() 
    {
        $value = '999';
        $output = RestrictionUtils::checkMaxLength($value, 999, 'int');
        $this->assertEquals($value, $output);
        $output = RestrictionUtils::checkMaxLength($value, 1000, 'int');
        $this->assertEquals($value, $output);
        
        $value = '2000';
        $output = RestrictionUtils::checkMaxLength($value, 2000, 'integer');
        $this->assertEquals($value, $output);
        $output = RestrictionUtils::checkMaxLength($value, 2500, 'integer');
        $this->assertEquals($value, $output);

        $value = '1000';
        $output = RestrictionUtils::checkMaxLength($value, 1000, 'float');
        $this->assertEquals($value, $output);
        $output = RestrictionUtils::checkMaxLength($value, 1000.1, 'float');
        $this->assertEquals($value, $output);
        
        $value = '5000';
        $output = RestrictionUtils::checkMaxLength($value, 5);
        $this->assertEquals($value, $output);
        $output = RestrictionUtils::checkMaxLength($value, 9);
        $this->assertEquals($value, $output);
        
        $value = 'my text';
        $output = RestrictionUtils::checkMaxLength($value, 7);
        $this->assertEquals($value, $output);
        $output = RestrictionUtils::checkMaxLength($value, 10);
        $this->assertEquals($value, $output);
        
        $value = ['A1', 'B2', 'B3'];
        $output = RestrictionUtils::checkMaxLength($value, 3);
        $this->assertEquals($value, $output);
        $output = RestrictionUtils::checkMaxLength($value, 5);
        $this->assertEquals($value, $output);
    }    
    
    public function testMaxLengthInvalid_1() 
    {
        $this->setExpectedException(RestrictionException::class, null, RestrictionException::ERROR_CODE_MAX_LENGTH);
        RestrictionUtils::checkMaxLength('1000', 900, 'int');
    }
    
    public function testMaxLengthInvalid_2() 
    {
        $this->setExpectedException(RestrictionException::class, null, RestrictionException::ERROR_CODE_MAX_LENGTH);
        RestrictionUtils::checkMaxLength('2000', 1500, 'integer');
    }
    
    public function testMaxLengthInvalid_3() 
    {
        $this->setExpectedException(RestrictionException::class, null, RestrictionException::ERROR_CODE_MAX_LENGTH);
        RestrictionUtils::checkMaxLength('124.2', 124.1, 'float');
    }
    
    public function testMaxLengthInvalid_4() 
    {
        $this->setExpectedException(RestrictionException::class, null, RestrictionException::ERROR_CODE_MAX_LENGTH);
        RestrictionUtils::checkMaxLength('5000', 3);
    }
    
    public function testMaxLengthInvalid_5() 
    {
        $this->setExpectedException(RestrictionException::class, null, RestrictionException::ERROR_CODE_MAX_LENGTH);
        RestrictionUtils::checkMaxLength('my text', 5);
    }
    
    public function testMaxLengthInvalid_6() 
    {
        $this->setExpectedException(RestrictionException::class, null, RestrictionException::ERROR_CODE_MAX_LENGTH);
        RestrictionUtils::checkMaxLength(['A1', 'B2', 'B3'], 2);
    }
    
    public function testMinLengthValid() 
    {
        $value = '999';
        $output = RestrictionUtils::checkMinLength($value, 999, 'int');
        $this->assertEquals($value, $output);
        $output = RestrictionUtils::checkMinLength($value, 500, 'int');
        $this->assertEquals($value, $output);
        
        $value = '2000';
        $output = RestrictionUtils::checkMinLength($value, 2000, 'integer');
        $this->assertEquals($value, $output);
        $output = RestrictionUtils::checkMinLength($value, 1500, 'integer');
        $this->assertEquals($value, $output);

        $value = '1000';
        $output = RestrictionUtils::checkMinLength($value, 1000, 'float');
        $this->assertEquals($value, $output);
        $output = RestrictionUtils::checkMinLength($value, 999.9, 'float');
        $this->assertEquals($value, $output);
        
        $value = '5000';
        $output = RestrictionUtils::checkMinLength($value, 4);
        $this->assertEquals($value, $output);
        $output = RestrictionUtils::checkMinLength($value, 1);
        $this->assertEquals($value, $output);
        
        $value = 'my text';
        $output = RestrictionUtils::checkMinLength($value, 7);
        $this->assertEquals($value, $output);
        $output = RestrictionUtils::checkMinLength($value, 5);
        $this->assertEquals($value, $output);
        
        $value = ['A1', 'B2', 'B3'];
        $output = RestrictionUtils::checkMinLength($value, 3);
        $this->assertEquals($value, $output);
        $output = RestrictionUtils::checkMinLength($value, 2);
        $this->assertEquals($value, $output);
    }    
    
    public function testMinLengthInvalid_1() 
    {
        $this->setExpectedException(RestrictionException::class, null, RestrictionException::ERROR_CODE_MIN_LENGTH);
        RestrictionUtils::checkMinLength('1000', 1001, 'int');
    }
    
    public function testMinLengthInvalid_2() 
    {
        $this->setExpectedException(RestrictionException::class, null, RestrictionException::ERROR_CODE_MIN_LENGTH);
        RestrictionUtils::checkMinLength('2000', 2500, 'integer');
    }
    
    public function testMinLengthInvalid_3() 
    {
        $this->setExpectedException(RestrictionException::class, null, RestrictionException::ERROR_CODE_MIN_LENGTH);
        RestrictionUtils::checkMinLength('124.2', 124.3, 'float');
    }
    
    public function testMinLengthInvalid_4() 
    {
        $this->setExpectedException(RestrictionException::class, null, RestrictionException::ERROR_CODE_MIN_LENGTH);
        RestrictionUtils::checkMinLength('5000', 5);
    }
    
    public function testMinLengthInvalid_5() 
    {
        $this->setExpectedException(RestrictionException::class, null, RestrictionException::ERROR_CODE_MIN_LENGTH);
        RestrictionUtils::checkMinLength('my text', 8);
    }
    
    public function testMinLengthInvalid_6() 
    {
        $this->setExpectedException(RestrictionException::class, null, RestrictionException::ERROR_CODE_MIN_LENGTH);
        RestrictionUtils::checkMinLength(['A1', 'B2', 'B3'], 4);
    }
    
    public function testWhiteSpace() 
    {
        $value = "Lorem ipsum \n\n dolor sit amet, consectetur adipiscing elit. \tFusce mattis augue   eu congue posuere.";
        
        $output = RestrictionUtils::checkWhiteSpace($value, 'replace');
        $this->assertEquals($output, 'Lorem ipsum    dolor sit amet, consectetur adipiscing elit.  Fusce mattis augue   eu congue posuere.');
        $output = RestrictionUtils::checkWhiteSpace($value, 'collapse');
        $this->assertEquals($output, 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Fusce mattis augue eu congue posuere.');
    }	

}
