<?php

namespace UptoSign\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for selector modules (UptosignListTargets subclasses).
 * Tests properties, url(), getSqlArrayForStats() without DB.
 */
class SelectorModulesTest extends TestCase
{
	/** @var object Saved $langs */
	private $savedLangs;

	/** @var object Saved $conf */
	private $savedConf;

	/** @var object Mock DB */
	private $mockDb;

	protected function setUp(): void
	{
		global $langs, $conf;
		$this->savedLangs = $langs;
		$this->savedConf = $conf;

		$langs = new class {
			public function trans($key, ...$args) { return $key; }
			public function transnoentities($key, ...$args) { return $key; }
			public function transnoentitiesnoconv($key, ...$args) { return $key; }
			public function load($module) {}
			public function loadLangs($modules) {}
		};

		if (!is_object($conf)) {
			$conf = new \stdClass();
		}
		if (!isset($conf->entity)) {
			$conf->entity = 1;
		}
		if (!isset($conf->global)) {
			$conf->global = new \stdClass();
		}
		if (!isset($conf->global->MAIN_UPLOAD_DOC)) {
			$conf->global->MAIN_UPLOAD_DOC = '2048';
		}

		// Minimal mock DB for constructors
		$this->mockDb = new class {
			public function escape($s) { return $s; }
			public function query($sql) { return false; }
			public function num_rows($r) { return 0; }
			public function fetch_object($r) { return null; }
			public function idate($d) { return ''; }
			public function prefix() { return 'llx_'; }
		};
	}

	protected function tearDown(): void
	{
		global $langs, $conf;
		$langs = $this->savedLangs;
		$conf = $this->savedConf;
	}

	// ========================================
	// uts_inputmanual module (manual input)
	// ========================================

	private function createInputManual(): \uptosignlist_uts_inputmanual
	{
		require_once dirname(__DIR__, 3) . '/core/modules/uptosignlist/uts_inputmanual.modules.php';
		return new \uptosignlist_uts_inputmanual($this->mockDb);
	}

	public function testInputManualProperties(): void
	{
		$mod = $this->createInputManual();
		$this->assertEquals('EmailsFromUser', $mod->name);
		$this->assertEquals('generic', $mod->picto);
		$this->assertEmpty($mod->require_module);
		$this->assertEquals(0, $mod->require_admin);
	}

	public function testInputManualGetSqlArrayForStats(): void
	{
		$mod = $this->createInputManual();
		$result = $mod->getSqlArrayForStats();
		$this->assertIsArray($result);
		$this->assertEmpty($result);
	}

	public function testInputManualGetNbOfRecipients(): void
	{
		$mod = $this->createInputManual();
		$this->assertEquals('', $mod->getNbOfRecipients());
	}

	public function testInputManualUrl(): void
	{
		$mod = $this->createInputManual();
		$this->assertEquals('', $mod->url(1));
	}

	public function testInputManualFormFilterReturnsTextarea(): void
	{
		$mod = $this->createInputManual();
		$html = $mod->formFilter();
		$this->assertStringContainsString('<textarea', $html);
		$this->assertStringContainsString('xinputuser', $html);
		$this->assertStringContainsString('rows="6"', $html);
		$this->assertStringContainsString('cols="80"', $html);
		$this->assertStringContainsString('placeholder=', $html);
	}

	// ========================================
	// uts_inputfile module (file import)
	// ========================================

	private function createInputFile(): \uptosignlist_uts_inputfile
	{
		require_once dirname(__DIR__, 3) . '/core/modules/uptosignlist/uts_inputfile.modules.php';
		return new \uptosignlist_uts_inputfile($this->mockDb);
	}

	public function testInputFileProperties(): void
	{
		$mod = $this->createInputFile();
		$this->assertEquals('EmailsFromFile', $mod->name);
		$this->assertEquals('generic', $mod->picto);
		$this->assertEmpty($mod->require_module);
		$this->assertEquals(0, $mod->require_admin);
	}

	public function testInputFileGetSqlArrayForStats(): void
	{
		$mod = $this->createInputFile();
		$result = $mod->getSqlArrayForStats();
		$this->assertIsArray($result);
		$this->assertEmpty($result);
	}

	public function testInputFileGetNbOfRecipients(): void
	{
		$mod = $this->createInputFile();
		$this->assertEquals('', $mod->getNbOfRecipients());
	}

	public function testInputFileFormFilterContainsFileInput(): void
	{
		$mod = $this->createInputFile();
		$html = $mod->formFilter();
		$this->assertStringContainsString('type="file"', $html);
		$this->assertStringContainsString('name="username"', $html);
	}

	// ========================================
	// uts_contacts module
	// ========================================

	private function createContacts(): \uptosignlist_uts_contacts
	{
		require_once dirname(__DIR__, 3) . '/core/modules/uptosignlist/uts_contacts.modules.php';
		return new \uptosignlist_uts_contacts($this->mockDb);
	}

	public function testContactsProperties(): void
	{
		$mod = $this->createContacts();
		$this->assertEquals('ContactCompanies', $mod->name);
		$this->assertEquals('contact', $mod->picto);
		$this->assertEquals(array('societe'), $mod->require_module);
		$this->assertEquals(0, $mod->require_admin);
		$this->assertEquals('isModEnabled("societe")', $mod->enabled);
	}

	public function testContactsGetSqlArrayForStats(): void
	{
		$mod = $this->createContacts();
		$result = $mod->getSqlArrayForStats();
		$this->assertIsArray($result);
		$this->assertNotEmpty($result);
		$this->assertStringContainsString('SELECT', $result[0]);
		$this->assertStringContainsString('socpeople', $result[0]);
	}

	public function testContactsUrlReturnsLink(): void
	{
		$mod = $this->createContacts();
		$result = $mod->url(42);
		$this->assertStringContainsString('contact/card.php?id=42', $result);
		$this->assertStringContainsString('<a href=', $result);
	}

	// ========================================
	// uts_members module (foundation members)
	// ========================================

	private function createMembers(): \uptosignlist_uts_members
	{
		require_once dirname(__DIR__, 3) . '/core/modules/uptosignlist/uts_members.modules.php';
		return new \uptosignlist_uts_members($this->mockDb);
	}

	public function testMembersProperties(): void
	{
		$mod = $this->createMembers();
		$this->assertEquals('FundationMembers', $mod->name);
		$this->assertEquals('user', $mod->picto);
		$this->assertEquals(array('adherent'), $mod->require_module);
		$this->assertEquals(0, $mod->require_admin);
		$this->assertEquals('isModEnabled("adherent")', $mod->enabled);
	}

	public function testMembersGetSqlArrayForStats(): void
	{
		$mod = $this->createMembers();
		$result = $mod->getSqlArrayForStats();
		$this->assertIsArray($result);
		$this->assertNotEmpty($result);
		$this->assertStringContainsString('SELECT', $result[0]);
		$this->assertStringContainsString('adherent', $result[0]);
	}

	public function testMembersUrlReturnsLink(): void
	{
		$mod = $this->createMembers();
		$result = $mod->url(99);
		$this->assertStringContainsString('adherents/card.php?rowid=99', $result);
		$this->assertStringContainsString('<a href=', $result);
	}

	// ========================================
	// uts_eventattendees module
	// ========================================

	private function createEventAttendees(): \uptosignlist_uts_eventattendees
	{
		require_once dirname(__DIR__, 3) . '/core/modules/uptosignlist/uts_eventattendees.modules.php';
		return new \uptosignlist_uts_eventattendees($this->mockDb);
	}

	public function testEventAttendeesProperties(): void
	{
		$mod = $this->createEventAttendees();
		$this->assertEquals('AttendeesOfOrganizedEvent', $mod->name);
		$this->assertEquals('conferenceorbooth', $mod->picto);
		$this->assertEmpty($mod->require_module);
		$this->assertEquals(0, $mod->require_admin);
		$this->assertEquals('isModEnabled("eventorganization")', $mod->enabled);
	}

	public function testEventAttendeesGetSqlArrayForStats(): void
	{
		$mod = $this->createEventAttendees();
		$result = $mod->getSqlArrayForStats();
		$this->assertIsArray($result);
		$this->assertEmpty($result);
	}

	public function testEventAttendeesUrlWithAttendeeSource(): void
	{
		$mod = $this->createEventAttendees();
		$result = $mod->url(7, 'project');
		$this->assertStringContainsString('conferenceorboothattendee_card.php?id=7', $result);
	}

	public function testEventAttendeesUrlWithUnknownSourceReturnsEmpty(): void
	{
		$mod = $this->createEventAttendees();
		$result = $mod->url(7, 'unknown');
		$this->assertEquals('', $result);
	}

	// ========================================
	// UptosignListTargets base class
	// ========================================

	public function testBaseClassGetDesc(): void
	{
		require_once dirname(__DIR__, 3) . '/core/modules/uptosignlist/modules_mailings.php';
		$base = new \UptosignListTargets($this->mockDb);
		$base->name = 'TestModule';
		$base->desc = 'Test description';

		$result = $base->getDesc();
		$this->assertIsString($result);
	}

	public function testBaseClassGetNbOfRecords(): void
	{
		require_once dirname(__DIR__, 3) . '/core/modules/uptosignlist/modules_mailings.php';
		$base = new \UptosignListTargets($this->mockDb);
		$this->assertEquals(0, $base->getNbOfRecords());
	}

	public function testBaseClassFormFilterReturnsEmpty(): void
	{
		require_once dirname(__DIR__, 3) . '/core/modules/uptosignlist/modules_mailings.php';
		$base = new \UptosignListTargets($this->mockDb);
		$this->assertEquals('', $base->formFilter());
	}
}
