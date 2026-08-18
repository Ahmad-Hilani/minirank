<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../auth.php';

class AuthTest extends TestCase
{
    private static $testDbPath;

    public static function setUpBeforeClass(): void
    {
        self::$testDbPath = sys_get_temp_dir() . '/minirank_test_' . uniqid() . '.db';
        putenv('DB_PATH=' . self::$testDbPath);
    }

    public static function tearDownAfterClass(): void
    {
        if (file_exists(self::$testDbPath)) {
            unlink(self::$testDbPath);
        }
    }

    protected function setUp(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION = [];
    }

    public function testCsrfTokenIsGenerated(): void
    {
        $token = csrf_token();
        $this->assertNotEmpty($token);
        $this->assertIsString($token);
    }

    public function testCsrfTokenIsConsistent(): void
    {
        $token1 = csrf_token();
        $token2 = csrf_token();
        $this->assertEquals($token1, $token2);
    }

    public function testCsrfTokenIsHex(): void
    {
        $token = csrf_token();
        $this->assertMatchesRegularExpression('/^[a-f0-9]+$/', $token);
    }

    public function testCsrfTokenLength(): void
    {
        $token = csrf_token();
        $this->assertEquals(64, strlen($token));
    }

    public function testPasswordHashing(): void
    {
        $password = 'testpassword123';
        $hash = password_hash($password, PASSWORD_DEFAULT);

        $this->assertNotEquals($password, $hash);
        $this->assertTrue(password_verify($password, $hash));
    }

    public function testPasswordVerifyFailsWithWrongPassword(): void
    {
        $hash = password_hash('correctpassword', PASSWORD_DEFAULT);
        $this->assertFalse(password_verify('wrongpassword', $hash));
    }
}
