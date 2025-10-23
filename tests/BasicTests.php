<?php
/**
 * Tests unitaires basiques - Mon Espace Client Pro
 *
 * Pour exécuter : php tests/BasicTests.php
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/helpers/Security.php';
require_once __DIR__ . '/../app/helpers/Validator.php';
require_once __DIR__ . '/../app/helpers/FileManager.php';

class BasicTests {
    private $passed = 0;
    private $failed = 0;

    public function run() {
        echo "==================================\n";
        echo "Mon Espace Client Pro - Tests\n";
        echo "==================================\n\n";

        $this->testSecurity();
        $this->testValidator();
        $this->testFileManager();

        echo "\n==================================\n";
        echo "Résultats: {$this->passed} réussis, {$this->failed} échoués\n";
        echo "==================================\n";

        return $this->failed === 0;
    }

    private function testSecurity() {
        echo "Tests Security...\n";

        // Test hashing password
        $password = 'demo123';
        $hash = Security::hashPassword($password);
        $this->assert('Hash password', !empty($hash) && strlen($hash) > 50);
        $this->assert('Verify password', Security::verifyPassword($password, $hash));
        $this->assert('Verify wrong password', !Security::verifyPassword('wrong', $hash));

        // Test email validation
        $this->assert('Valid email', Security::validateEmail('test@example.com'));
        $this->assert('Invalid email', !Security::validateEmail('invalid-email'));

        // Test UUID generation
        $uuid = Security::generateUUID();
        $this->assert('UUID generation', preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $uuid));

        // Test CSRF token generation
        session_start();
        $token = Security::generateCSRFToken();
        $this->assert('CSRF token generation', !empty($token) && strlen($token) === 64);
        $this->assert('CSRF token verification', Security::verifyCSRFToken($token));
        $this->assert('CSRF token invalid', !Security::verifyCSRFToken('invalid'));

        // Test XSS protection
        $dirty = '<script>alert("XSS")</script>Test';
        $clean = Security::escape($dirty);
        $this->assert('XSS escape', strpos($clean, '<script>') === false);

        echo "\n";
    }

    private function testValidator() {
        echo "Tests Validator...\n";

        // Test required
        $validator = new Validator(['name' => 'John']);
        $validator->required('name');
        $this->assert('Required field valid', $validator->passes());

        $validator = new Validator(['name' => '']);
        $validator->required('name');
        $this->assert('Required field empty', $validator->fails());

        // Test email
        $validator = new Validator(['email' => 'test@example.com']);
        $validator->email('email');
        $this->assert('Email validation valid', $validator->passes());

        $validator = new Validator(['email' => 'invalid']);
        $validator->email('email');
        $this->assert('Email validation invalid', $validator->fails());

        // Test min length
        $validator = new Validator(['password' => '12345678']);
        $validator->min('password', 8);
        $this->assert('Min length valid', $validator->passes());

        $validator = new Validator(['password' => '123']);
        $validator->min('password', 8);
        $this->assert('Min length invalid', $validator->fails());

        // Test match
        $validator = new Validator(['password' => 'secret', 'password_confirm' => 'secret']);
        $validator->match('password', 'password_confirm');
        $this->assert('Match valid', $validator->passes());

        $validator = new Validator(['password' => 'secret', 'password_confirm' => 'different']);
        $validator->match('password', 'password_confirm');
        $this->assert('Match invalid', $validator->fails());

        echo "\n";
    }

    private function testFileManager() {
        echo "Tests FileManager...\n";

        // Test sanitize filename
        $unsafe = '../../etc/passwd<script>.pdf';
        $safe = FileManager::sanitizeFilename($unsafe);
        $this->assert('Sanitize filename', !strpos($safe, '..') && !strpos($safe, '<') && !strpos($safe, '/'));

        // Test file size formatting
        $formatted = FileManager::formatFileSize(1024);
        $this->assert('Format file size 1Ko', $formatted === '1 Ko');

        $formatted = FileManager::formatFileSize(1048576);
        $this->assert('Format file size 1Mo', $formatted === '1 Mo');

        // Test file icon
        $icon = FileManager::getFileIcon('pdf');
        $this->assert('File icon PDF', $icon === '📄');

        $icon = FileManager::getFileIcon('jpg');
        $this->assert('File icon JPG', $icon === '🖼️');

        // Test is image
        $this->assert('Is image JPG', FileManager::isImage('jpg'));
        $this->assert('Is not image PDF', !FileManager::isImage('pdf'));

        // Test is PDF
        $this->assert('Is PDF', FileManager::isPDF('pdf'));
        $this->assert('Is not PDF', !FileManager::isPDF('jpg'));

        echo "\n";
    }

    private function assert($name, $condition) {
        if ($condition) {
            echo "  ✓ {$name}\n";
            $this->passed++;
        } else {
            echo "  ✗ {$name}\n";
            $this->failed++;
        }
    }
}

// Exécuter les tests
$tests = new BasicTests();
$success = $tests->run();

exit($success ? 0 : 1);
