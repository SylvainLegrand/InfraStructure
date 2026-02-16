<?php

namespace UptoSign\Tests\IntegrationDolibarr;

/**
 * Integration tests for fetchAll with sorting, filtering, limits, offset
 * on UptoSign, UptoSignConfig, UptoSignList
 */
class UptoSignFetchFilterTest extends DolibarrRealTestCase
{
	// ============================================
	// UptoSign: fetchAll with params
	// ============================================

	public function testUptoSignFetchAllWithLimit(): void
	{
		$soc = $this->createTestSociete();

		for ($i = 1; $i <= 5; $i++) {
			$uptosign = new \UptoSign($this->db);
			$uptosign->ref = 'LIMIT-' . $i . '-' . uniqid();
			$uptosign->label = 'Limit test ' . $i;
			$uptosign->fk_soc = $soc->id;
			$uptosign->status = \UptoSign::STATUS_WAITING;
			$uptosign->entity = 1;
			$uptosign->object_type = 'propal';
			$uptosign->fk_object = $i;
			$uptosign->create($this->testUser);
		}

		$uptosign = new \UptoSign($this->db);
		$result = $uptosign->fetchAll('', '', 3);

		$this->assertIsArray($result);
		$this->assertCount(3, $result);
	}

	public function testUptoSignFetchAllWithLimitAndOffset(): void
	{
		$soc = $this->createTestSociete();

		for ($i = 1; $i <= 5; $i++) {
			$uptosign = new \UptoSign($this->db);
			$uptosign->ref = 'OFFSET-' . $i . '-' . uniqid();
			$uptosign->label = 'Offset test ' . $i;
			$uptosign->fk_soc = $soc->id;
			$uptosign->status = \UptoSign::STATUS_WAITING;
			$uptosign->entity = 1;
			$uptosign->object_type = 'propal';
			$uptosign->fk_object = $i;
			$uptosign->create($this->testUser);
		}

		// Offset only works when limit > 0 (plimit not applied when limit=0)
		$uptosign = new \UptoSign($this->db);
		$withOffset = $uptosign->fetchAll('ASC', 't.rowid', 100, 2);

		$this->assertIsArray($withOffset);
		// Should have 3 results (5 total - 2 offset)
		$this->assertCount(3, $withOffset);
	}

	public function testUptoSignFetchAllWithSortOrder(): void
	{
		$soc = $this->createTestSociete();

		for ($i = 1; $i <= 3; $i++) {
			$uptosign = new \UptoSign($this->db);
			$uptosign->ref = 'SORT-' . $i . '-' . uniqid();
			$uptosign->label = 'Sort test ' . $i;
			$uptosign->fk_soc = $soc->id;
			$uptosign->status = \UptoSign::STATUS_WAITING;
			$uptosign->entity = 1;
			$uptosign->object_type = 'propal';
			$uptosign->fk_object = $i;
			$uptosign->create($this->testUser);
		}

		$uptosign = new \UptoSign($this->db);
		$ascending = $uptosign->fetchAll('ASC', 't.rowid');

		$this->assertIsArray($ascending);
		$this->assertGreaterThanOrEqual(3, count($ascending));

		$ids = array_keys($ascending);
		for ($i = 1; $i < count($ids); $i++) {
			$this->assertGreaterThan($ids[$i - 1], $ids[$i]);
		}
	}

	public function testUptoSignFetchAllDescending(): void
	{
		$soc = $this->createTestSociete();

		for ($i = 1; $i <= 3; $i++) {
			$uptosign = new \UptoSign($this->db);
			$uptosign->ref = 'DESC-' . $i . '-' . uniqid();
			$uptosign->label = 'Desc test ' . $i;
			$uptosign->fk_soc = $soc->id;
			$uptosign->status = \UptoSign::STATUS_WAITING;
			$uptosign->entity = 1;
			$uptosign->object_type = 'propal';
			$uptosign->fk_object = $i;
			$uptosign->create($this->testUser);
		}

		$uptosign = new \UptoSign($this->db);
		$descending = $uptosign->fetchAll('DESC', 't.rowid');

		$this->assertIsArray($descending);
		$ids = array_keys($descending);
		for ($i = 1; $i < count($ids); $i++) {
			$this->assertLessThan($ids[$i - 1], $ids[$i]);
		}
	}

	public function testUptoSignFetchAllWithFilter(): void
	{
		$soc = $this->createTestSociete();

		$uptosign1 = new \UptoSign($this->db);
		$uptosign1->ref = 'FILTER-W-' . uniqid();
		$uptosign1->label = 'Filter waiting';
		$uptosign1->fk_soc = $soc->id;
		$uptosign1->status = \UptoSign::STATUS_WAITING;
		$uptosign1->entity = 1;
		$uptosign1->object_type = 'propal';
		$uptosign1->fk_object = 1;
		$uptosign1->create($this->testUser);

		$uptosign2 = new \UptoSign($this->db);
		$uptosign2->ref = 'FILTER-S-' . uniqid();
		$uptosign2->label = 'Filter signed';
		$uptosign2->fk_soc = $soc->id;
		$uptosign2->status = \UptoSign::STATUS_SIGNED;
		$uptosign2->entity = 1;
		$uptosign2->object_type = 'propal';
		$uptosign2->fk_object = 2;
		$uptosign2->create($this->testUser);

		// UptoSign fetchAll handles 't.status' safely (falls through to IN clause)
		$fetcher = new \UptoSign($this->db);
		$result = $fetcher->fetchAll('', '', 0, 0, ['t.status' => \UptoSign::STATUS_SIGNED]);

		$this->assertIsArray($result);
		$this->assertGreaterThanOrEqual(1, count($result));

		foreach ($result as $obj) {
			$this->assertEquals(\UptoSign::STATUS_SIGNED, $obj->status);
		}
	}

	// ============================================
	// UptoSignList: fetchAll with params
	// ============================================

	public function testUptoSignListFetchAllWithLimit(): void
	{
		for ($i = 1; $i <= 5; $i++) {
			$list = new \UptoSignList($this->db);
			$list->label = 'Limit list ' . $i;
			$list->status = \UptoSignList::STATUS_DRAFT;
			$list->entity = 1;
			$list->create($this->testUser);
		}

		$list = new \UptoSignList($this->db);
		$result = $list->fetchAll('', '', 2);

		$this->assertIsArray($result);
		$this->assertCount(2, $result);
	}

	public function testUptoSignListFetchAllWithSorting(): void
	{
		for ($i = 1; $i <= 3; $i++) {
			$list = new \UptoSignList($this->db);
			$list->label = 'Sort list ' . $i;
			$list->status = \UptoSignList::STATUS_DRAFT;
			$list->entity = 1;
			$list->create($this->testUser);
		}

		$list = new \UptoSignList($this->db);
		$result = $list->fetchAll('DESC', 't.rowid');

		$this->assertIsArray($result);
		$ids = array_keys($result);
		for ($i = 1; $i < count($ids); $i++) {
			$this->assertLessThan($ids[$i - 1], $ids[$i]);
		}
	}

	public function testUptoSignListFetchAllWithFilter(): void
	{
		$list1 = new \UptoSignList($this->db);
		$list1->label = 'Filter draft';
		$list1->status = \UptoSignList::STATUS_DRAFT;
		$list1->entity = 1;
		$list1->create($this->testUser);

		$list2 = new \UptoSignList($this->db);
		$list2->label = 'Filter validated';
		$list2->status = \UptoSignList::STATUS_DRAFT;
		$list2->entity = 1;
		$list2->create($this->testUser);
		$list2->validate($this->testUser);

		// Use field name without 't.' prefix (required by UptoSignList fetchAll)
		$fetcher = new \UptoSignList($this->db);
		$result = $fetcher->fetchAll('', '', 0, 0, ['status' => \UptoSignList::STATUS_VALIDATED]);

		$this->assertIsArray($result);
		$this->assertGreaterThanOrEqual(1, count($result));
		foreach ($result as $obj) {
			$this->assertEquals(\UptoSignList::STATUS_VALIDATED, $obj->status);
		}
	}

	// ============================================
	// UptoSignConfig: fetchAll with params
	// ============================================

	public function testUptoSignConfigFetchAllWithLimit(): void
	{
		for ($i = 1; $i <= 4; $i++) {
			$config = new \UptoSignConfig($this->db);
			$config->label = 'CustomerSign';
			$config->sign_or_seal = 'sign';
			$config->model_pdf = 'propal:model' . $i;
			$config->seal_coordinate = '100;100';
			$config->page_seal = 1;
			$config->status = \UptoSignConfig::STATUS_VALIDATED;
			$config->entity = 1;
			$config->create($this->testUser);
		}

		$config = new \UptoSignConfig($this->db);
		$result = $config->fetchAll('', '', 2);

		$this->assertIsArray($result);
		$this->assertCount(2, $result);
	}

	public function testUptoSignConfigFetchAllWithFilter(): void
	{
		$config1 = new \UptoSignConfig($this->db);
		$config1->label = 'CustomerSign';
		$config1->sign_or_seal = 'sign';
		$config1->model_pdf = 'propal:azur';
		$config1->seal_coordinate = '100;100';
		$config1->page_seal = 1;
		$config1->status = \UptoSignConfig::STATUS_VALIDATED;
		$config1->entity = 1;
		$config1->create($this->testUser);

		$config2 = new \UptoSignConfig($this->db);
		$config2->label = 'DocumentSeal';
		$config2->sign_or_seal = 'seal';
		$config2->model_pdf = 'facture:crabe';
		$config2->seal_coordinate = '200;200';
		$config2->page_seal = 1;
		$config2->status = \UptoSignConfig::STATUS_VALIDATED;
		$config2->entity = 1;
		$config2->create($this->testUser);

		// Filter by string field (now properly quoted)
		$fetcher = new \UptoSignConfig($this->db);
		$result = $fetcher->fetchAll('', '', 0, 0, ['sign_or_seal' => 'seal']);

		$this->assertIsArray($result);
		$this->assertGreaterThanOrEqual(1, count($result));
		foreach ($result as $obj) {
			$this->assertEquals('seal', $obj->sign_or_seal);
		}
	}

	public function testUptoSignConfigFetchAllSorted(): void
	{
		for ($i = 1; $i <= 3; $i++) {
			$config = new \UptoSignConfig($this->db);
			$config->label = 'Sort' . $i;
			$config->sign_or_seal = 'sign';
			$config->model_pdf = 'propal:model' . $i;
			$config->seal_coordinate = '100;100';
			$config->page_seal = 1;
			$config->status = \UptoSignConfig::STATUS_VALIDATED;
			$config->entity = 1;
			$config->create($this->testUser);
		}

		$config = new \UptoSignConfig($this->db);
		$result = $config->fetchAll('DESC', 't.rowid');

		$this->assertIsArray($result);
		$ids = array_keys($result);
		for ($i = 1; $i < count($ids); $i++) {
			$this->assertLessThan($ids[$i - 1], $ids[$i]);
		}
	}

	public function testUptoSignConfigFetchAllWithDisabled(): void
	{
		$config1 = new \UptoSignConfig($this->db);
		$config1->label = 'Active';
		$config1->sign_or_seal = 'sign';
		$config1->model_pdf = 'propal:azur';
		$config1->seal_coordinate = '100;100';
		$config1->page_seal = 1;
		$config1->status = \UptoSignConfig::STATUS_VALIDATED;
		$config1->entity = 1;
		$config1->create($this->testUser);

		$config2 = new \UptoSignConfig($this->db);
		$config2->label = 'Disabled';
		$config2->sign_or_seal = 'sign';
		$config2->model_pdf = 'propal:disabled';
		$config2->seal_coordinate = '100;100';
		$config2->page_seal = 1;
		$config2->status = \UptoSignConfig::STATUS_DISABLED;
		$config2->entity = 1;
		$config2->create($this->testUser);

		// Without disabled (default)
		$fetcher1 = new \UptoSignConfig($this->db);
		$withoutDisabled = $fetcher1->fetchAll();

		// With disabled
		$fetcher2 = new \UptoSignConfig($this->db);
		$withDisabled = $fetcher2->fetchAll('', '', 0, 0, [], 'AND', 1);

		$this->assertIsArray($withoutDisabled);
		$this->assertIsArray($withDisabled);
		$this->assertGreaterThan(count($withoutDisabled), count($withDisabled));
	}
}
