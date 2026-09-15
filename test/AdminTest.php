<?php
use PHPUnit\Framework\TestCase;

class AdminTest extends TestCase
{
    public function test_admin_cannot_delete_self()
    {
        $result = AppLogic::canDeleteUser('admin-1', 'admin-1', 'system_admin');
        $this->assertFalse($result['allowed']);
        $this->assertEquals('cannot delete self', $result['reason']);
    }

    public function test_consumer_cannot_delete_anyone()
    {
        $result = AppLogic::canDeleteUser('consumer-1', 'user-2', 'consumer');
        $this->assertFalse($result['allowed']);
        $this->assertEquals('not an admin', $result['reason']);
        
        // But admin CAN delete others
        $ok = AppLogic::canDeleteUser('admin-1', 'user-2', 'system_admin');
        $this->assertTrue($ok['allowed']);
    }
}
