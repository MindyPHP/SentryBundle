<?php
/**
 * 
 *
 * All rights reserved.
 * 
 * @author Falaleev Maxim
 * @email max@studio107.ru
 * @version 1.0
 * @company Studio107
 * @site http://studio107.ru
 * @date 04/01/14.01.2014 02:38
 */

namespace Tests\Orm;


use Tests\DatabaseTestCase;
use Tests\Models\User;

class SaveUpdateTest extends DatabaseTestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->initModels([new User]);
    }

    public function tearDown()
    {
        $this->dropModels([new User]);
    }

    public function testSave()
    {
        $model = new User();
        $model->username = 'Anton';
        $model->password = 'VeryGoodP@ssword';
        $this->assertEquals(0, User::objects()->count());
        $this->assertTrue($model->getIsNewRecord());

        $this->assertTrue($model->isValid());
        $this->assertNull($model->pk);
        $this->assertEquals('Anton', $model->username);
        $this->assertEquals('VeryGoodP@ssword', $model->password);

        $saved = $model->save();
        $this->assertTrue($saved);
        $this->assertEquals(1, User::objects()->count());
        $this->assertFalse($model->getIsNewRecord());
        $this->assertEquals(1, $model->pk);
        $this->assertEquals('Anton', $model->username);
        $this->assertEquals('VeryGoodP@ssword', $model->password);
    }

    public function testSaveSelectedField()
    {
        $model = new User();
        $model->username = 'Anton';
        $model->password = 'VeryGoodP@ssword';
        $this->assertEquals(0, User::objects()->count());
        $this->assertTrue($model->getIsNewRecord());
        $this->assertTrue($model->isValid());
        $this->assertNull($model->pk);
        $this->assertEquals('Anton', $model->username);
        $this->assertEquals('VeryGoodP@ssword', $model->password);

        $saved = $model->save(['username']);
        $this->assertTrue($saved);
        $this->assertEquals(1, User::objects()->count());
        $this->assertFalse($model->getIsNewRecord());
        $this->assertEquals(1, $model->pk);
        $this->assertEquals('Anton', $model->username);
        $this->assertEquals('VeryGoodP@ssword', $model->password);

        $model = User::objects()->get();
        $this->assertNull($model->password);
    }

    public function testUpdate()
    {
        $model = new User();
        $model->username = 'Anton';
        $model->password = 'VeryGoodP@ssword';
        $saved = $model->save();
        $this->assertEquals(1, $model->pk);
        $this->assertTrue($saved);

        $model = new User();
        $model->username = 'Max';
        $model->password = 'VeryGoodP@ssword';
        $saved = $model->save();
        $this->assertTrue($saved);
        $this->assertEquals(2, User::objects()->count());
        $this->assertFalse($model->getIsNewRecord());

        $this->assertEquals('Max', $model->username);
        $this->assertEquals('VeryGoodP@ssword', $model->password);

        $tmpFind = User::objects()->get(['id' => 1]);
        $this->assertEquals('Anton', $tmpFind->username);
        $this->assertEquals('VeryGoodP@ssword', $tmpFind->password);

        $updated = User::objects()->filter(['id' => 1])->update(['username' => 'Unknown']);
        $model = User::objects()->get(['pk' => 1]);
        $this->assertEquals('Unknown', $model->username);
        $this->assertEquals(1, $updated);

        $this->assertEquals(2, User::objects()->count());
        // Max already has username `Unknown`
        $updated = User::objects()->filter(['id__gte' => 0])->update(['username' => 'Unknown']);
        $this->assertEquals(1, $updated);

        $this->assertEquals(2, User::objects()->count());
        $updated = User::objects()->filter(['id__gte' => 0])->update(['username' => '123']);
        $this->assertEquals(2, $updated);
    }
}
