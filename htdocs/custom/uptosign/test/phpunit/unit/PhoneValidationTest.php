<?php

namespace UptoSign\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for phone validation functions
 * (uptoSignFixMobile, uptoSignSearchMobile, uptoSignSearchMobileContact)
 */
class PhoneValidationTest extends TestCase
{
	public static function setUpBeforeClass(): void
	{
		if (!function_exists('uptoSignFixMobile')) {
			dol_include_once('/uptosign/lib/uptosign.lib.php');
		}
	}

	// --- uptoSignFixMobile ---

	public function testFixMobileEmptyReturnsEmpty(): void
	{
		$this->assertSame('', uptoSignFixMobile('', 'FR'));
	}

	public function testFixMobileInternationalFormatKept(): void
	{
		$this->assertSame('+33698744401', uptoSignFixMobile('+33698744401', 'FR'));
	}

	public function testFixMobileInternationalWithSpaces(): void
	{
		$this->assertSame('+33698744401', uptoSignFixMobile('+33 6 98 74 44 01', 'FR'));
	}

	public function testFixMobileFrenchLocalMobile6(): void
	{
		$this->assertSame('+33698744401', uptoSignFixMobile('0698744401', 'FR'));
	}

	public function testFixMobileFrenchLocalMobile7(): void
	{
		$this->assertSame('+33798744401', uptoSignFixMobile('0798744401', 'FR'));
	}

	public function testFixMobileFrenchLandlineReturnsEmpty(): void
	{
		// French landline numbers (starting with 01-05) should not be converted
		$this->assertSame('', uptoSignFixMobile('0123456789', 'FR'));
	}

	public function testFixMobileBelgianLocal(): void
	{
		$this->assertSame('+32470123456', uptoSignFixMobile('0470123456', 'BE'));
	}

	public function testFixMobileLuxembourgLocal(): void
	{
		$this->assertSame('+352621123456', uptoSignFixMobile('0621123456', 'LU'));
	}

	public function testFixMobileNumericCountryCode33(): void
	{
		// Country code as numeric (33 instead of FR)
		$this->assertSame('+33612345678', uptoSignFixMobile('0612345678', 33));
	}

	public function testFixMobileNumericCountryCode32(): void
	{
		$this->assertSame('+32470123456', uptoSignFixMobile('0470123456', 32));
	}

	public function testFixMobileNumericCountryCode352(): void
	{
		$this->assertSame('+352621123456', uptoSignFixMobile('0621123456', 352));
	}

	public function testFixMobileWithParenthesesZero(): void
	{
		// (0) is replaced by 0, giving +330612345678 which is valid international format
		$this->assertSame('+330612345678', uptoSignFixMobile('+33(0)612345678', 'FR'));
	}

	public function testFixMobileEmptyCountryUsesGlobalMysoc(): void
	{
		global $mysoc;
		$saved = $mysoc->country_code;
		$mysoc->country_code = 'FR';
		$result = uptoSignFixMobile('0612345678', '');
		$mysoc->country_code = $saved;
		$this->assertSame('+33612345678', $result);
	}

	public function testFixMobileInvalidFormatReturnsEmpty(): void
	{
		$this->assertSame('', uptoSignFixMobile('abc', 'FR'));
	}

	public function testFixMobileShortNumberReturnsEmpty(): void
	{
		$this->assertSame('', uptoSignFixMobile('06', 'FR'));
	}

	// --- uptoSignSearchMobile ---

	public function testSearchMobilePrefersMobile(): void
	{
		$result = uptoSignSearchMobile('+33612345678', '+33112345678', 'FR');
		$this->assertSame('+33612345678', $result);
	}

	public function testSearchMobileFallsToPro(): void
	{
		$result = uptoSignSearchMobile('', '+33612345678', 'FR');
		$this->assertSame('+33612345678', $result);
	}

	public function testSearchMobileBothEmptyReturnsEmpty(): void
	{
		$result = uptoSignSearchMobile('', '', 'FR');
		$this->assertSame('', $result);
	}

	public function testSearchMobileNullMobileFallsToPro(): void
	{
		$result = uptoSignSearchMobile(null, '0612345678', 'FR');
		$this->assertSame('+33612345678', $result);
	}

	// --- uptoSignSearchMobileContact ---

	public function testSearchMobileContactPrefersPhoneMobile(): void
	{
		$contact = new \stdClass();
		$contact->phone_mobile = '+33612345678';
		$contact->phone_pro = '+33112345678';
		$contact->country_code = 'FR';

		$result = uptoSignSearchMobileContact($contact);
		$this->assertSame('+33612345678', $result);
	}

	public function testSearchMobileContactFallsToPhonePro(): void
	{
		$contact = new \stdClass();
		$contact->phone_mobile = '';
		$contact->phone_pro = '+33612345678';
		$contact->country_code = 'FR';

		$result = uptoSignSearchMobileContact($contact);
		$this->assertSame('+33612345678', $result);
	}

	public function testSearchMobileContactNoPhoneReturnsEmpty(): void
	{
		$contact = new \stdClass();
		$contact->phone_mobile = '';
		$contact->phone_pro = '';
		$contact->country_code = 'FR';

		$result = uptoSignSearchMobileContact($contact);
		$this->assertSame('', $result);
	}

	public function testSearchMobileContactUsesPhonePerso(): void
	{
		$contact = new \stdClass();
		$contact->phone_mobile = '';
		$contact->phone_pro = '';
		$contact->phone_perso = '+33612345678';
		$contact->country_code = 'FR';

		$result = uptoSignSearchMobileContact($contact);
		$this->assertSame('+33612345678', $result);
	}

	public function testSearchMobileContactMissingFields(): void
	{
		// Contact with no phone fields at all
		$contact = new \stdClass();
		$contact->country_code = 'FR';

		$result = uptoSignSearchMobileContact($contact);
		$this->assertSame('', $result);
	}

	public function testSearchMobileContactInvalidNumbers(): void
	{
		$contact = new \stdClass();
		$contact->phone_mobile = 'invalid';
		$contact->phone_pro = 'also-invalid';
		$contact->country_code = 'FR';

		$result = uptoSignSearchMobileContact($contact);
		$this->assertSame('', $result);
	}

	// --- Edge cases for uptoSignFixMobile ---

	public function testFixMobileWithSpacesInternational(): void
	{
		$this->assertSame('+32470123456', uptoSignFixMobile('+32 470 12 34 56', 'BE'));
	}

	public function testFixMobileFrenchWithDots(): void
	{
		// Dots should be treated as non-numeric, result may vary
		$result = uptoSignFixMobile('06.12.34.56.78', 'FR');
		// Dots are not removed by the function, so the regex won't match
		$this->assertSame('', $result);
	}

	public function testFixMobileFrenchWithDashes(): void
	{
		$result = uptoSignFixMobile('06-12-34-56-78', 'FR');
		$this->assertSame('', $result);
	}

	public function testFixMobileAlreadyFormattedInternational(): void
	{
		$this->assertSame('+4917612345678', uptoSignFixMobile('+4917612345678', 'DE'));
	}

	public function testFixMobileUnknownCountryNoConversion(): void
	{
		// Number starts with 0 but country is unknown, no prefix to add
		$result = uptoSignFixMobile('0612345678', 'XX');
		$this->assertSame('', $result);
	}
}
