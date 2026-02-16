<?php

namespace UptoSign\Tests\IntegrationDolibarr;

/**
 * Integration tests for common methods across UptoSign, UptoSignConfig, UptoSignList:
 * getNomUrl, info, initAsSpecimen, getNextNumRef, getKanbanView, getTooltipContentArray
 */
class UptoSignCommonMethodsTest extends DolibarrRealTestCase
{
	// ============================================
	// UptoSign: getNomUrl
	// ============================================

	public function testUptoSignGetNomUrlBasic(): void
	{
		$soc = $this->createTestSociete();

		$uptosign = new \UptoSign($this->db);
		$uptosign->ref = 'NOMURL-' . uniqid();
		$uptosign->label = 'Test getNomUrl';
		$uptosign->fk_soc = $soc->id;
		$uptosign->status = \UptoSign::STATUS_WAITING;
		$uptosign->entity = 1;
		$uptosign->object_type = 'propal';
		$uptosign->fk_object = 1;
		$uptosign->create($this->testUser);
		$uptosign->fetch($uptosign->id);

		$result = $uptosign->getNomUrl();

		$this->assertIsString($result);
		$this->assertStringContainsString($uptosign->ref, $result);
	}

	public function testUptoSignGetNomUrlWithPicto(): void
	{
		$soc = $this->createTestSociete();

		$uptosign = new \UptoSign($this->db);
		$uptosign->ref = 'PICTO-' . uniqid();
		$uptosign->label = 'Test with picto';
		$uptosign->fk_soc = $soc->id;
		$uptosign->status = \UptoSign::STATUS_SIGNED;
		$uptosign->entity = 1;
		$uptosign->object_type = 'propal';
		$uptosign->fk_object = 1;
		$uptosign->create($this->testUser);
		$uptosign->fetch($uptosign->id);

		$result = $uptosign->getNomUrl(1);

		$this->assertIsString($result);
		$this->assertStringContainsString($uptosign->ref, $result);
		$this->assertStringContainsString('<a ', $result);
	}

	public function testUptoSignGetNomUrlNoLink(): void
	{
		$soc = $this->createTestSociete();

		$uptosign = new \UptoSign($this->db);
		$uptosign->ref = 'NOLINK-' . uniqid();
		$uptosign->label = 'Test nolink';
		$uptosign->fk_soc = $soc->id;
		$uptosign->status = \UptoSign::STATUS_WAITING;
		$uptosign->entity = 1;
		$uptosign->object_type = 'propal';
		$uptosign->fk_object = 1;
		$uptosign->create($this->testUser);
		$uptosign->fetch($uptosign->id);

		$result = $uptosign->getNomUrl(0, 'nolink');

		$this->assertIsString($result);
		$this->assertStringContainsString('<span', $result);
		$this->assertStringNotContainsString('<a ', $result);
	}

	public function testUptoSignGetNomUrlWithTooltip(): void
	{
		$soc = $this->createTestSociete();

		$uptosign = new \UptoSign($this->db);
		$uptosign->ref = 'TOOLTIP-' . uniqid();
		$uptosign->label = 'Test tooltip';
		$uptosign->fk_soc = $soc->id;
		$uptosign->status = \UptoSign::STATUS_WAITING;
		$uptosign->entity = 1;
		$uptosign->object_type = 'propal';
		$uptosign->fk_object = 1;
		$uptosign->create($this->testUser);
		$uptosign->fetch($uptosign->id);

		// notooltip = 1
		$result = $uptosign->getNomUrl(1, '', 1);

		$this->assertIsString($result);
		$this->assertStringNotContainsString('classfortooltip', $result);
	}

	public function testUptoSignGetNomUrlWithMoreCss(): void
	{
		$soc = $this->createTestSociete();

		$uptosign = new \UptoSign($this->db);
		$uptosign->ref = 'CSS-' . uniqid();
		$uptosign->label = 'Test CSS';
		$uptosign->fk_soc = $soc->id;
		$uptosign->status = \UptoSign::STATUS_WAITING;
		$uptosign->entity = 1;
		$uptosign->object_type = 'propal';
		$uptosign->fk_object = 1;
		$uptosign->create($this->testUser);
		$uptosign->fetch($uptosign->id);

		$result = $uptosign->getNomUrl(0, '', 0, 'my-custom-css');

		$this->assertIsString($result);
		$this->assertStringContainsString('my-custom-css', $result);
	}

	public function testUptoSignGetNomUrlPictoOnly(): void
	{
		$soc = $this->createTestSociete();

		$uptosign = new \UptoSign($this->db);
		$uptosign->ref = 'PICTOONLY-' . uniqid();
		$uptosign->label = 'Test picto only';
		$uptosign->fk_soc = $soc->id;
		$uptosign->status = \UptoSign::STATUS_WAITING;
		$uptosign->entity = 1;
		$uptosign->object_type = 'propal';
		$uptosign->fk_object = 1;
		$uptosign->create($this->testUser);
		$uptosign->fetch($uptosign->id);

		// withpicto = 2 means picto only, no ref text in body
		$withPicto = $uptosign->getNomUrl(1);
		$pictoOnly = $uptosign->getNomUrl(2);

		$this->assertIsString($pictoOnly);
		$this->assertStringContainsString('<img', $pictoOnly);
		// withpicto=2 output should be shorter (no ref text in body)
		$this->assertLessThan(strlen($withPicto), strlen($pictoOnly));
	}

	// ============================================
	// UptoSign: info
	// ============================================

	public function testUptoSignInfo(): void
	{
		$soc = $this->createTestSociete();

		$uptosign = new \UptoSign($this->db);
		$uptosign->ref = 'INFO-' . uniqid();
		$uptosign->label = 'Test info';
		$uptosign->fk_soc = $soc->id;
		$uptosign->status = \UptoSign::STATUS_WAITING;
		$uptosign->entity = 1;
		$uptosign->object_type = 'propal';
		$uptosign->fk_object = 1;
		$uptosign->create($this->testUser);

		$fresh = new \UptoSign($this->db);
		$fresh->info($uptosign->id);

		$this->assertEquals($uptosign->id, $fresh->id);
		$this->assertNotEmpty($fresh->date_creation);
	}

	// ============================================
	// UptoSign: initAsSpecimen
	// ============================================

	public function testUptoSignInitAsSpecimen(): void
	{
		$uptosign = new \UptoSign($this->db);
		$uptosign->initAsSpecimen();

		$this->assertNotEmpty($uptosign->ref);
	}

	// ============================================
	// UptoSign: getNextNumRef
	// ============================================

	public function testUptoSignGetNextNumRef(): void
	{
		global $conf;
		$conf->global->UPTOSIGN_UPTOSIGN_ADDON = 'mod_uptosign_standard';

		$soc = $this->createTestSociete();

		$uptosign = new \UptoSign($this->db);
		$uptosign->ref = 'NUMREF-' . uniqid();
		$uptosign->label = 'Test numref';
		$uptosign->fk_soc = $soc->id;
		$uptosign->status = \UptoSign::STATUS_WAITING;
		$uptosign->entity = 1;
		$uptosign->object_type = 'propal';
		$uptosign->fk_object = 1;
		$uptosign->create($this->testUser);
		$uptosign->fetch($uptosign->id);

		$numref = $uptosign->getNextNumRef();

		$this->assertIsString($numref);
		$this->assertNotEmpty($numref, 'getNextNumRef should return a non-empty string');
	}

	// ============================================
	// UptoSignConfig: getNomUrl
	// ============================================

	public function testUptoSignConfigGetNomUrlBasic(): void
	{
		$config = new \UptoSignConfig($this->db);
		$config->label = 'CustomerSign';
		$config->sign_or_seal = 'sign';
		$config->model_pdf = 'propal:azur';
		$config->seal_coordinate = '100;100';
		$config->page_seal = 1;
		$config->status = \UptoSignConfig::STATUS_VALIDATED;
		$config->entity = 1;
		$config->create($this->testUser);
		$config->fetch($config->id);

		$result = $config->getNomUrl();

		$this->assertIsString($result);
		$this->assertStringContainsString($config->ref, $result);
	}

	public function testUptoSignConfigGetNomUrlWithPicto(): void
	{
		$config = new \UptoSignConfig($this->db);
		$config->label = 'CustomerSign';
		$config->sign_or_seal = 'sign';
		$config->model_pdf = 'propal:azur';
		$config->seal_coordinate = '100;100';
		$config->page_seal = 1;
		$config->status = \UptoSignConfig::STATUS_VALIDATED;
		$config->entity = 1;
		$config->create($this->testUser);
		$config->fetch($config->id);

		$result = $config->getNomUrl(1, '', 1);

		$this->assertIsString($result);
		$this->assertStringContainsString('<a ', $result);
	}

	public function testUptoSignConfigGetNomUrlNoLink(): void
	{
		$config = new \UptoSignConfig($this->db);
		$config->label = 'VendorSign';
		$config->sign_or_seal = 'sign';
		$config->model_pdf = 'commande:einstein';
		$config->seal_coordinate = '50;50';
		$config->page_seal = 1;
		$config->status = \UptoSignConfig::STATUS_DRAFT;
		$config->entity = 1;
		$config->create($this->testUser);
		$config->fetch($config->id);

		$result = $config->getNomUrl(0, 'nolink');

		$this->assertIsString($result);
		$this->assertStringContainsString('<span', $result);
		$this->assertStringNotContainsString('<a ', $result);
	}

	// ============================================
	// UptoSignConfig: info
	// ============================================

	public function testUptoSignConfigInfo(): void
	{
		$config = new \UptoSignConfig($this->db);
		$config->label = 'CustomerSign';
		$config->sign_or_seal = 'sign';
		$config->model_pdf = 'propal:azur';
		$config->seal_coordinate = '100;100';
		$config->page_seal = 1;
		$config->status = \UptoSignConfig::STATUS_VALIDATED;
		$config->entity = 1;
		$config->create($this->testUser);

		$fresh = new \UptoSignConfig($this->db);
		$fresh->info($config->id);

		$this->assertEquals($config->id, $fresh->id);
		$this->assertNotEmpty($fresh->date_creation);
	}

	// ============================================
	// UptoSignConfig: initAsSpecimen
	// ============================================

	public function testUptoSignConfigInitAsSpecimen(): void
	{
		$config = new \UptoSignConfig($this->db);
		$config->initAsSpecimen();

		// initAsSpecimenCommon() sets fields from $fields array; check label (not ref)
		$this->assertNotEmpty($config->label);
	}

	// ============================================
	// UptoSignList: getNomUrl
	// ============================================

	public function testUptoSignListGetNomUrlBasic(): void
	{
		$list = new \UptoSignList($this->db);
		$list->ref = 'NOMURL-' . uniqid();
		$list->label = 'Test getNomUrl';
		$list->status = \UptoSignList::STATUS_DRAFT;
		$list->entity = 1;
		$list->create($this->testUser);
		$list->fetch($list->id);

		$result = $list->getNomUrl();

		$this->assertIsString($result);
		$this->assertStringContainsString($list->ref, $result);
	}

	public function testUptoSignListGetNomUrlWithPicto(): void
	{
		$list = new \UptoSignList($this->db);
		$list->ref = 'PICTO-' . uniqid();
		$list->label = 'Test picto';
		$list->status = \UptoSignList::STATUS_VALIDATED;
		$list->entity = 1;
		$list->create($this->testUser);
		$list->fetch($list->id);

		$result = $list->getNomUrl(1, '', 1);

		$this->assertIsString($result);
		$this->assertStringContainsString('<a ', $result);
	}

	public function testUptoSignListGetNomUrlNoLink(): void
	{
		$list = new \UptoSignList($this->db);
		$list->ref = 'NOLINK-' . uniqid();
		$list->label = 'Test nolink';
		$list->status = \UptoSignList::STATUS_DRAFT;
		$list->entity = 1;
		$list->create($this->testUser);
		$list->fetch($list->id);

		$result = $list->getNomUrl(0, 'nolink');

		$this->assertIsString($result);
		$this->assertStringContainsString('<span', $result);
		$this->assertStringNotContainsString('<a ', $result);
	}

	// ============================================
	// UptoSignList: getTooltipContentArray
	// ============================================

	public function testUptoSignListGetTooltipContentArray(): void
	{
		$list = new \UptoSignList($this->db);
		$list->ref = 'TOOLTIP-' . uniqid();
		$list->label = 'Test tooltip';
		$list->status = \UptoSignList::STATUS_DRAFT;
		$list->entity = 1;
		$list->create($this->testUser);
		$list->fetch($list->id);

		$result = $list->getTooltipContentArray(['id' => $list->id]);

		$this->assertIsArray($result);
		$this->assertArrayHasKey('picto', $result);
		$this->assertArrayHasKey('ref', $result);
	}

	// ============================================
	// UptoSignList: getKanbanView
	// ============================================

	public function testUptoSignListGetKanbanView(): void
	{
		$list = new \UptoSignList($this->db);
		$list->ref = 'KANBAN-' . uniqid();
		$list->label = 'Test kanban';
		$list->status = \UptoSignList::STATUS_DRAFT;
		$list->entity = 1;
		$list->create($this->testUser);
		$list->fetch($list->id);

		$result = $list->getKanbanView('', ['selected' => 0]);

		$this->assertIsString($result);
		$this->assertStringContainsString('info-box', $result);
		$this->assertStringContainsString($list->label, $result);
	}

	public function testUptoSignListGetKanbanViewSelected(): void
	{
		$list = new \UptoSignList($this->db);
		$list->ref = 'KANBANSEL-' . uniqid();
		$list->label = 'Test kanban selected';
		$list->status = \UptoSignList::STATUS_VALIDATED;
		$list->entity = 1;
		$list->create($this->testUser);
		$list->fetch($list->id);

		$result = $list->getKanbanView('', ['selected' => 1]);

		$this->assertIsString($result);
		$this->assertStringContainsString('checked="checked"', $result);
	}

	// ============================================
	// UptoSignList: info
	// ============================================

	public function testUptoSignListInfo(): void
	{
		$list = new \UptoSignList($this->db);
		$list->ref = 'INFO-' . uniqid();
		$list->label = 'Test info';
		$list->status = \UptoSignList::STATUS_DRAFT;
		$list->entity = 1;
		$list->create($this->testUser);

		$fresh = new \UptoSignList($this->db);
		$fresh->info($list->id);

		$this->assertEquals($list->id, $fresh->id);
		$this->assertNotEmpty($fresh->date_creation);
	}

	// ============================================
	// UptoSignList: initAsSpecimen
	// ============================================

	public function testUptoSignListInitAsSpecimen(): void
	{
		$list = new \UptoSignList($this->db);
		$list->initAsSpecimen();

		$this->assertNotEmpty($list->ref);
	}

	// ============================================
	// UptoSignList: getNextNumRef
	// ============================================

	public function testUptoSignListGetNextNumRef(): void
	{
		global $conf;
		$conf->global->UPTOSIGN_UPTOSIGNLIST_ADDON = 'mod_uptosignlist_standard';

		$list = new \UptoSignList($this->db);
		$list->ref = 'NUMREF-' . uniqid();
		$list->label = 'Test numref';
		$list->status = \UptoSignList::STATUS_DRAFT;
		$list->entity = 1;
		$list->create($this->testUser);
		$list->fetch($list->id);

		$numref = $list->getNextNumRef();

		$this->assertIsString($numref);
		$this->assertNotEmpty($numref, 'getNextNumRef should return a non-empty string');
	}
}
