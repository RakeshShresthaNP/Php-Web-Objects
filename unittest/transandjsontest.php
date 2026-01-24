<?php
declare(strict_types = 1);

require_once 'db.php';
require_once '../myapp/app/models/model.php';

echo "--- ORM METHOD COVERAGE TEST ---\n<br>";

it("Stress: Atomic Transaction", function () {
    $m = new model('users');

    $status = $m->transaction(function () {
        $u = new model('users');
        $u->name = "Transaction Test User";
        $u->email = "test@example.com";
        $userId = $u->save();

        $o = new model('orders');
        $o->user_id = $userId;
        $o->order_ref = 'tt123';
        $o->total_amount = 500;
        $o->save();

        return true;
    });

    echo "   - Transaction: Atomic User + Order creation " . ($status ? "Passed" : "Failed") . ".\n<br>";
});

// 2. Define our test model
class UserProfile extends model
{

    protected array $casts = [
        'settings' => 'json',
        'tags' => 'json'
    ];
}

echo "<h2>Running JSON Cast Tests</h2>";

// --- THE TESTS ---

it('decodes json string from database into array', function () {
    $m = new UserProfile('users');
    $rawDbRow = [
        'id' => 1,
        'settings' => '{"theme":"dark","zoom":1.2}', // String from DB
        'name' => 'John'
    ];

    $m->assign($rawDbRow);

    if (! is_array($m->settings))
        throw new Exception("Expected settings to be an array");
    if ($m->settings['theme'] !== 'dark')
        throw new Exception("Expected theme to be dark");
});

it('encodes array into json string for database storage', function () {
    $m = new UserProfile('users');
    $m->settings = [
        'notifications' => false,
        'retry' => 3
    ];

    // Using reflection to test the protected mapValues method
    $reflector = new ReflectionClass($m);
    $method = $reflector->getMethod('mapValues');
    $method->setAccessible(true);

    $data = $m->getData();
    $mapped = $method->invokeArgs($m, [
        &$data
    ]);

    // $mapped[0] is settings because getData returns the array keys
    $jsonString = $mapped[0];

    if (! is_string($jsonString))
        throw new Exception("Expected settings to be cast to string");
    if (json_decode($jsonString) === null)
        throw new Exception("Resulting string is not valid JSON");
});

it('ignores casting when property is not in $casts', function () {
    $m = new UserProfile('users');
    $rawDbRow = [
        'name' => '{"this_is_not_json":true}'
    ]; // A string that looks like JSON

    $m->assign($rawDbRow);

    if (! is_string($m->name))
        throw new Exception("Expected name to remain a string");
});

it('handles null values in json cast columns', function () {
    $m = new UserProfile('users');
    $rawDbRow = [
        'settings' => null
    ];

    $m->assign($rawDbRow);

    if ($m->settings !== null)
        throw new Exception("Expected settings to be null");
});

it('prevents double-encoding if property is already a json string', function () {
    $m = new class('users') extends model {

        protected array $casts = [
            'meta' => 'json'
        ];
    };

    // Manually set a string that is already JSON
    $jsonString = '{"is_valid":true}';
    $m->meta = $jsonString;

    $reflector = new ReflectionClass($m);
    $method = $reflector->getMethod('mapValues');
    $method->setAccessible(true);

    $data = $m->getData();
    $mapped = $method->invokeArgs($m, [
        &$data
    ]);

    // If double-encoded, the result would be '"{\"is_valid\":true}"'
    if ($mapped[0] !== $jsonString) {
        throw new Exception("Double encoding detected! Expected: $jsonString, Got: " . $mapped[0]);
    }
});

echo "\n<br>--- ALL METHODS VERIFIED ---\n<br>";